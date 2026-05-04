<?php
if (!defined('init_engine'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

class server_Account
{
    static private function tableColumns()
    {
        global $AUTH_DB;
        static $cols = null;
        if ($cols !== null) return $cols;
        $cols = array();
        try {
            $q = $AUTH_DB->query("SHOW COLUMNS FROM `account`");
            while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
                $cols[strtolower($r['Field'])] = isset($r['Type']) ? strtolower($r['Type']) : true;
            }
        } catch (Exception $e) {}
        return $cols;
    }

    static public function isAzerothCoreSchema()
    {
        $cols = self::tableColumns();
        return isset($cols['salt']) && isset($cols['verifier']) && !isset($cols['sha_pass_hash']);
    }

    static private function columnStoresBinary($column)
    {
        $cols = self::tableColumns();
        $key = strtolower($column);
        if (!isset($cols[$key]) || !is_string($cols[$key])) return false;
        return (strpos($cols[$key], 'binary') !== false || strpos($cols[$key], 'blob') !== false);
    }

    static private function columnMaxLength($column)
    {
        $cols = self::tableColumns();
        $key = strtolower($column);
        if (!isset($cols[$key]) || !is_string($cols[$key])) return 0;
        if (preg_match('/\((\d+)\)/', $cols[$key], $m)) return (int)$m[1];
        return 0;
    }

    static private function columnShouldStoreHex($column)
    {
        // AzerothCore SRP6 values are 32 raw bytes or 64 HEX characters.
        // Some imported auth.account tables use VARCHAR(40), so HEX(64) fails with
        // SQL 1406 "Data too long". In that case we store raw 32 bytes instead.
        if (self::columnStoresBinary($column)) return false;
        $len = self::columnMaxLength($column);
        if ($len > 0 && $len < 64) return false;
        return true;
    }

    static private function normalizeStoredSrpValue($value)
    {
        if ($value === null) return '';
        $value = (string)$value;
        if (strlen($value) === 64 && ctype_xdigit($value)) {
            return strtolower($value);
        }
        return strtolower(bin2hex($value));
    }

    static public function makeHash($user, $pass)
    {
        return sha1(strtoupper(trim($user)) . ':' . strtoupper(trim($pass)));
    }

    static public function makeSessionHashFromRow($row)
    {
        $id = isset($row['id']) ? $row['id'] : '';
        $username = isset($row['username']) ? strtoupper($row['username']) : '';
        $email = isset($row['email']) ? strtolower($row['email']) : '';
        return sha1('AZEROTHCORE_WEB_SESSION:' . $id . ':' . $username . ':' . $email);
    }

    static private function srp6Verifier($username, $password, $salt)
    {
        if (!function_exists('gmp_init')) {
            throw new Exception('PHP GMP extension is required to create AzerothCore SRP6 accounts. Enable extension=gmp in php.ini and restart WAMP.');
        }
        $N = gmp_init('894B645E89E1535BBDAD5B8B290650530801B18EBFBF5E8FAB3C82872A3E9BB7', 16);
        $g = gmp_init(7, 10);
        $h1 = sha1(strtoupper(trim($username)) . ':' . strtoupper(trim($password)), true);
        $h2 = sha1($salt . $h1, true);
        $x = gmp_import($h2, 1, GMP_LSW_FIRST);
        $v = gmp_powm($g, $x, $N);
        $verifier = gmp_export($v, 1, GMP_LSW_FIRST);
        // AzerothCore verifier must be exactly 32 raw bytes for BINARY(32)/VARBINARY(32).
        if (strlen($verifier) > 32) {
            $verifier = substr($verifier, 0, 32);
        }
        return str_pad($verifier, 32, "\0", STR_PAD_RIGHT);
    }

    static public function verifyPassword($row, $password)
    {
        if (self::isAzerothCoreSchema()) {
            if (!isset($row['salt']) || !isset($row['verifier'])) return false;
            $storedSalt = (strlen($row['salt']) === 64 && ctype_xdigit($row['salt'])) ? hex2bin($row['salt']) : $row['salt'];
            $calc = self::srp6Verifier($row['username'], $password, $storedSalt);
            return hash_equals(self::normalizeStoredSrpValue($row['verifier']), strtolower(bin2hex($calc)));
        }
        $passcheck = self::makeHash($row['username'], $password);
        return isset($row['sha_pass_hash']) && strtolower($row['sha_pass_hash']) === strtolower($passcheck);
    }

    static public function userCheck($ACP = false)
    {
        global $CURUSER, $AUTH_DB, $DB;
        if (!isset($_SESSION['uid']) || !isset($_SESSION['pass'])) return;
        $id = (int)$_SESSION['uid'];
        if (!$id || strlen($_SESSION['pass']) != 40) return;

        $res = $AUTH_DB->prepare('SELECT * FROM `account` WHERE `id` = :id LIMIT 1');
        $res->bindParam(':id', $id, PDO::PARAM_INT);
        $res->execute();
        $row = $res->fetch(PDO::FETCH_ASSOC);
        unset($res);
        if (!$row) { $_SESSION = array(); return; }

        if (self::isAzerothCoreSchema()) {
            if (strtolower($_SESSION['pass']) !== strtolower(self::makeSessionHashFromRow($row))) { $_SESSION = array(); return; }
        } else {
            if (!isset($row['sha_pass_hash']) || strtolower($_SESSION['pass']) !== strtolower($row['sha_pass_hash'])) { $_SESSION = array(); return; }
        }

        if ($ACP) {
            $perms = new Permissions($row['id']);
            if (!$perms->IsAllowedToUseACP()) { $_SESSION = array(); return; }
            $CURUSER->setPermissionsObject($perms);
        }

        $ss = new Secure(); $ss->cb = true; $ss->cib = 2;
        if (!$ss->check()) { unset($ss); $_SESSION = array(); return; }
        unset($ss);

        $res = $DB->prepare('SELECT * FROM `account_data` WHERE `id` = :id LIMIT 1');
        $res->bindParam(':id', $id, PDO::PARAM_INT);
        $res->execute();
        $webRow = $res->fetch(PDO::FETCH_ASSOC);
        unset($res);

        $newRow = array(
            'id' => $row['id'],
            'username' => $row['username'],
            'shapasshash' => self::isAzerothCoreSchema() ? self::makeSessionHashFromRow($row) : (isset($row['sha_pass_hash']) ? $row['sha_pass_hash'] : ''),
            'lastip' => isset($row['last_ip']) ? $row['last_ip'] : '',
            'lastlogin' => isset($row['last_login']) ? $row['last_login'] : '',
            'flags' => isset($row['expansion']) ? $row['expansion'] : 2,
            'email' => isset($row['email']) ? $row['email'] : '',
            'joindate' => isset($row['joindate']) ? $row['joindate'] : '',
            'recruiter' => isset($row['recruiter']) ? $row['recruiter'] : 0,
        );
        if ($webRow) $newRow = array_merge($newRow, $webRow);
        $CURUSER->setrecord($newRow);
        if (!isset($_SESSION['logged'])) $_SESSION['logged'] = '1';
    }

    static public function RememberMeCheck()
    {
        global $AUTH_DB, $DB, $CURUSER;
        $rememberMeCookie = isset($_COOKIE['rmm_wcw']) ? $_COOKIE['rmm_wcw'] : false;
        if ($rememberMeCookie && !$CURUSER->isOnline()) {
            $cookieData = explode('-', $rememberMeCookie, 2);
            if (count($cookieData) === 2) {
                $cookieUser = strtoupper($cookieData[0]);
                $cookieHash = $cookieData[1];
                $res = $AUTH_DB->prepare('SELECT * FROM `account` WHERE `username` = :username LIMIT 1');
                $res->bindParam(':username', $cookieUser, PDO::PARAM_STR);
                $res->execute();
                if ($acc = $res->fetch(PDO::FETCH_ASSOC)) {
                    $saltRes = $DB->prepare('SELECT `salt` FROM `account_data` WHERE `id` = :acc LIMIT 1');
                    $saltRes->bindParam(':acc', $acc['id'], PDO::PARAM_INT);
                    $saltRes->execute();
                    if ($web = $saltRes->fetch(PDO::FETCH_ASSOC)) {
                        $baseHash = self::isAzerothCoreSchema() ? self::makeSessionHashFromRow($acc) : (isset($acc['sha_pass_hash']) ? $acc['sha_pass_hash'] : '');
                        if ($web['salt'] != '' && hash_equals(sha1($baseHash . $web['salt']), $cookieHash)) {
                            $CURUSER->setLoggedIn($acc['id'], $baseHash);
                        }
                    }
                }
            }
        }
    }

    static public function getLastRegisterError()
    {
        return isset($GLOBALS['WARCRY_LAST_REGISTER_ERROR']) ? $GLOBALS['WARCRY_LAST_REGISTER_ERROR'] : '';
    }

    static private function setLastRegisterError($message)
    {
        $GLOBALS['WARCRY_LAST_REGISTER_ERROR'] = $message;
        $logFile = dirname(__FILE__) . '/../../../cache/azerothcore_register_error.log';
        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    }

    static private function addInsertValue(&$fields, &$params, &$binds, $cols, $column, $value, $pdoType = PDO::PARAM_STR)
    {
        if (isset($cols[strtolower($column)])) {
            $key = ':' . $column;
            $fields[] = '`' . $column . '`';
            $params[] = $key;
            $binds[$key] = array($value, $pdoType);
        }
    }

    static public function register($username, $password, $email, $expansion = 2, $recruiter = 0)
    {
        global $AUTH_DB, $CORE, $SECURITY;
        $GLOBALS['WARCRY_LAST_REGISTER_ERROR'] = '';

        try {
            $dateTime = $CORE->getTime(true);
            $joindate = $dateTime->format('Y-m-d H:i:s');
            $lastip = $SECURITY->getip();
            $username = strtoupper(trim($username));
            $email = trim($email);
            $cols = self::tableColumns();

            if (self::isAzerothCoreSchema()) {
                $saltRaw = random_bytes(32);
                $verifierRaw = self::srp6Verifier($username, $password, $saltRaw);

                // AzerothCore schemas are different depending on version/import:
                // - BINARY/VARBINARY/BLOB columns expect raw 32-byte values.
                // - VARCHAR/CHAR/TEXT columns with length >= 64 can store 64-char HEX.
                // - VARCHAR(40) / short legacy imports must receive raw 32 bytes, otherwise
                //   MySQL throws SQLSTATE 22001 / 1406 "Data too long for column verifier".
                $saltUsesHex = self::columnShouldStoreHex('salt');
                $verifierUsesHex = self::columnShouldStoreHex('verifier');
                $salt = $saltUsesHex ? strtoupper(bin2hex($saltRaw)) : $saltRaw;
                $verifier = $verifierUsesHex ? strtoupper(bin2hex($verifierRaw)) : $verifierRaw;
                $saltParamType = $saltUsesHex ? PDO::PARAM_STR : PDO::PARAM_LOB;
                $verifierParamType = $verifierUsesHex ? PDO::PARAM_STR : PDO::PARAM_LOB;

                $fields = array();
                $params = array();
                $binds = array();

                self::addInsertValue($fields, $params, $binds, $cols, 'username', $username);
                self::addInsertValue($fields, $params, $binds, $cols, 'salt', $salt, $saltParamType);
                self::addInsertValue($fields, $params, $binds, $cols, 'verifier', $verifier, $verifierParamType);
                self::addInsertValue($fields, $params, $binds, $cols, 'session_key', null, PDO::PARAM_NULL);
                self::addInsertValue($fields, $params, $binds, $cols, 'totp_secret', null, PDO::PARAM_NULL);
                self::addInsertValue($fields, $params, $binds, $cols, 'email', $email);
                self::addInsertValue($fields, $params, $binds, $cols, 'reg_mail', $email);
                self::addInsertValue($fields, $params, $binds, $cols, 'joindate', $joindate);
                self::addInsertValue($fields, $params, $binds, $cols, 'last_ip', $lastip);
                self::addInsertValue($fields, $params, $binds, $cols, 'last_attempt_ip', $lastip);
                self::addInsertValue($fields, $params, $binds, $cols, 'failed_logins', 0, PDO::PARAM_INT);
                self::addInsertValue($fields, $params, $binds, $cols, 'locked', 0, PDO::PARAM_INT);
                self::addInsertValue($fields, $params, $binds, $cols, 'lock_country', '00');
                self::addInsertValue($fields, $params, $binds, $cols, 'last_login', null, PDO::PARAM_NULL);
                self::addInsertValue($fields, $params, $binds, $cols, 'online', 0, PDO::PARAM_INT);
                self::addInsertValue($fields, $params, $binds, $cols, 'expansion', (int)$expansion, PDO::PARAM_INT);
                self::addInsertValue($fields, $params, $binds, $cols, 'Flags', (int)$expansion, PDO::PARAM_INT);
                self::addInsertValue($fields, $params, $binds, $cols, 'mutetime', 0, PDO::PARAM_INT);
                self::addInsertValue($fields, $params, $binds, $cols, 'mutereason', '');
                self::addInsertValue($fields, $params, $binds, $cols, 'muteby', '');
                self::addInsertValue($fields, $params, $binds, $cols, 'locale', 0, PDO::PARAM_INT);
                self::addInsertValue($fields, $params, $binds, $cols, 'os', '');
                self::addInsertValue($fields, $params, $binds, $cols, 'recruiter', (int)$recruiter, PDO::PARAM_INT);
                self::addInsertValue($fields, $params, $binds, $cols, 'totaltime', 0, PDO::PARAM_INT);

                if (!isset($cols['username']) || !isset($cols['salt']) || !isset($cols['verifier'])) {
                    self::setLastRegisterError('AzerothCore auth.account table is missing username, salt, or verifier columns. Detected columns: ' . implode(', ', array_keys($cols)));
                    return false;
                }

                $sql = 'INSERT INTO `account` (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $params) . ')';
                $insert = $AUTH_DB->prepare($sql);
                foreach ($binds as $key => $data) {
                    if ($data[1] === PDO::PARAM_NULL) {
                        $insert->bindValue($key, null, PDO::PARAM_NULL);
                    } else {
                        $insert->bindValue($key, $data[0], $data[1]);
                    }
                }
            } else {
                $shapasshash = self::makeHash($username, $password);
                $insert = $AUTH_DB->prepare('INSERT INTO `account` (`username`, `sha_pass_hash`, `email`, `joindate`, `last_ip`, `expansion`, `recruiter`) VALUES (:username, :passhash, :email, :joindate, :lastip, :flags, :recruiter)');
                $insert->bindParam(':username', $username, PDO::PARAM_STR);
                $insert->bindParam(':passhash', $shapasshash, PDO::PARAM_STR);
                $insert->bindParam(':email', $email, PDO::PARAM_STR);
                $insert->bindParam(':joindate', $joindate, PDO::PARAM_STR);
                $insert->bindParam(':lastip', $lastip, PDO::PARAM_STR);
                $insert->bindParam(':flags', $expansion, PDO::PARAM_INT);
                $insert->bindParam(':recruiter', $recruiter, PDO::PARAM_INT);
            }

            if ($insert->execute()) {
                return $AUTH_DB->lastInsertId();
            }

            $info = $insert->errorInfo();
            self::setLastRegisterError('Auth account insert failed: ' . implode(' | ', $info) . ' | salt type=' . (isset($cols['salt']) ? $cols['salt'] : 'missing') . ' len=' . strlen($salt) . ' | verifier type=' . (isset($cols['verifier']) ? $cols['verifier'] : 'missing') . ' len=' . strlen($verifier));
            return false;
        } catch (Exception $e) {
            self::setLastRegisterError('Auth account insert exception: ' . $e->getMessage());
            return false;
        }
    }

}
