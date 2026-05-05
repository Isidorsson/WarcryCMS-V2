<?php
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_PREV_USERS)) { $ERRORS->Add('You do not have permission to manage users.', 'manage_user'); $ERRORS->Check('/admin/index.php?page=users'); }

$uid = isset($_POST['uid']) ? (int)$_POST['uid'] : 0;
$realm = isset($_POST['realm']) ? (int)$_POST['realm'] : 1;
$action = isset($_POST['action']) ? $_POST['action'] : '';
if ($uid <= 0) { $ERRORS->Add('Invalid user id.', 'manage_user'); $ERRORS->Check('/admin/index.php?page=users'); }
if (!isset($realms_config[$realm])) { $realm = 1; }

function wc_redirect_user($uid,$realm){ header('Location: index.php?page=users&view=manage&uid='.(int)$uid.'&realm='.(int)$realm); exit; }
function wc_col_exists($pdo,$table,$col){ try { if(!preg_match('/^[A-Za-z0-9_]+$/',$table) || !preg_match('/^[A-Za-z0-9_]+$/',$col)) return false; $sql="SHOW COLUMNS FROM `".$table."` LIKE ".$pdo->quote($col); $q=$pdo->query($sql); return ($q && $q->rowCount()>0); } catch(Exception $e){ return false; } }

try {
    if ($action === 'save_profile') {
        $display = isset($_POST['displayName']) ? trim($_POST['displayName']) : '';
        if ($display === '' || strlen($display) > 32) { throw new Exception('Display name must be between 1 and 32 characters.'); }
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';
        if (!in_array($status, array('active','pending','disabled'))) { $status = 'active'; }
        $rank = isset($_POST['rank']) ? (int)$_POST['rank'] : 0;
        $silver = isset($_POST['silver']) ? max(0, (int)$_POST['silver']) : 0;
        $gold = isset($_POST['gold']) ? max(0, (int)$_POST['gold']) : 0;
        $selected = isset($_POST['selected_realm']) ? (int)$_POST['selected_realm'] : 1;
        if (!isset($realms_config[$selected])) { $selected = 1; }
        $country = strtoupper(substr(isset($_POST['country']) ? preg_replace('/[^a-zA-Z]/','',$_POST['country']) : 'US', 0, 2));
        if ($country === '') { $country = 'US'; }
        $gender = substr(isset($_POST['gender']) ? trim($_POST['gender']) : '', 0, 10);
        $q = $DB->prepare("UPDATE `account_data` SET `displayName`=:displayName, `status`=:status, `rank`=:rank, `silver`=:silver, `gold`=:gold, `selected_realm`=:selected_realm, `country`=:country, `gender`=:gender WHERE `id`=:id LIMIT 1");
        $q->execute(array(':displayName'=>$display, ':status'=>$status, ':rank'=>$rank, ':silver'=>$silver, ':gold'=>$gold, ':selected_realm'=>$selected, ':country'=>$country, ':gender'=>$gender, ':id'=>$uid));
        $ERRORS->Add('User CMS profile updated successfully.', 'manage_user', 'success');
        wc_redirect_user($uid,$selected);
    }
    if ($action === 'save_auth') {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new Exception('Invalid email address.'); }
        $expansion = isset($_POST['expansion']) ? max(0, min(4, (int)$_POST['expansion'])) : 2;
        $locked = isset($_POST['locked']) ? (int)$_POST['locked'] : 0;
        $mutetime = isset($_POST['mutetime']) ? max(0, (int)$_POST['mutetime']) : 0;
        $q = $AUTH_DB->prepare("UPDATE `account` SET `email`=:email, `expansion`=:expansion, `locked`=:locked, `mutetime`=:mutetime WHERE `id`=:id LIMIT 1");
        $q->execute(array(':email'=>$email, ':expansion'=>$expansion, ':locked'=>$locked, ':mutetime'=>$mutetime, ':id'=>$uid));
        $ERRORS->Add('Auth account updated successfully.', 'manage_user', 'success');
        wc_redirect_user($uid,$realm);
    }
    if ($action === 'save_gm') {
        $gmRealm = isset($_POST['gm_realm']) ? (int)$_POST['gm_realm'] : -1;
        $gmLevel = isset($_POST['gmlevel']) ? (int)$_POST['gmlevel'] : 0;
        $del = $AUTH_DB->prepare("DELETE FROM `account_access` WHERE `id`=:id AND `RealmID`=:realm");
        $del->execute(array(':id'=>$uid, ':realm'=>$gmRealm));
        if ($gmLevel > 0) {
            $ins = $AUTH_DB->prepare("INSERT INTO `account_access` (`id`,`gmlevel`,`RealmID`) VALUES (:id,:gm,:realm)");
            $ins->execute(array(':id'=>$uid, ':gm'=>$gmLevel, ':realm'=>$gmRealm));
        }
        $ERRORS->Add('GM access updated successfully.', 'manage_user', 'success');
        wc_redirect_user($uid,$realm);
    }
    if ($action === 'save_character') {
        $guid = isset($_POST['guid']) ? (int)$_POST['guid'] : 0;
        if ($guid <= 0) { throw new Exception('Invalid character guid.'); }
        $rdb = $CORE->RealmDatabaseConnection($realm);
        $verify = $rdb->prepare("SELECT `guid`,`account`,`online` FROM `characters` WHERE `guid`=:guid AND `account`=:account LIMIT 1");
        $verify->execute(array(':guid'=>$guid, ':account'=>$uid));
        $char = $verify->fetch();
        if (!$char) { throw new Exception('Character does not belong to this account.'); }
        if ((int)$char['online'] === 1) { throw new Exception('Character is online. Log out before editing level, money or position.'); }
        $level = isset($_POST['level']) ? max(1, min(80, (int)$_POST['level'])) : 1;
        $money = isset($_POST['money']) ? max(0, (int)$_POST['money']) : 0;
        $map = isset($_POST['map']) ? (int)$_POST['map'] : 0;
        $x = isset($_POST['position_x']) ? (float)$_POST['position_x'] : 0;
        $y = isset($_POST['position_y']) ? (float)$_POST['position_y'] : 0;
        $z = isset($_POST['position_z']) ? (float)$_POST['position_z'] : 0;
        $q = $rdb->prepare("UPDATE `characters` SET `level`=:level, `money`=:money, `map`=:map, `position_x`=:x, `position_y`=:y, `position_z`=:z WHERE `guid`=:guid AND `account`=:account LIMIT 1");
        $q->execute(array(':level'=>$level, ':money'=>$money, ':map'=>$map, ':x'=>$x, ':y'=>$y, ':z'=>$z, ':guid'=>$guid, ':account'=>$uid));
        $ERRORS->Add('Character updated successfully.', 'manage_user', 'success');
        wc_redirect_user($uid,$realm);
    }
    throw new Exception('Invalid action.');
} catch (Exception $e) {
    $ERRORS->Add($e->getMessage(), 'manage_user');
    wc_redirect_user($uid,$realm);
}
