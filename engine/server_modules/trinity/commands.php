<?php
if (!defined('init_engine'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

class server_Commands
{
    public function __construct()
    {
        return true;
    }

    private function logDeliveryError($context, $message)
    {
        global $config;
        $root = isset($config['RootPath']) ? $config['RootPath'] : dirname(dirname(dirname(dirname(__FILE__))));
        $file = $root . '/cache/purchase_delivery_error.log';
        @file_put_contents($file, '[' . date('Y-m-d H:i:s') . '] ' . $context . ' | ' . $message . PHP_EOL, FILE_APPEND);
    }

    private function soap($command, $realmid)
    {
        global $CORE;
        $soapMsg = $CORE->ExecuteSoapCommand($command, $realmid);

        if (is_array($soapMsg) && isset($soapMsg['sent']) && $soapMsg['sent'] === true) {
            return true;
        }

        $message = is_array($soapMsg) && isset($soapMsg['message']) ? $soapMsg['message'] : 'Unknown SOAP error';
        $this->logDeliveryError('SOAP FAILED', $command . ' => ' . $message);
        return $message;
    }

    private function realmDb($realmid)
    {
        global $CORE;
        return $CORE->RealmDatabaseConnection($realmid);
    }

    private function getCharacter($charName, $realmid)
    {
        $db = $this->realmDb($realmid);
        if (!$db) {
            return false;
        }
        $stmt = $db->prepare("SELECT guid, name, online, level FROM `characters` WHERE `name` = :name LIMIT 1");
        $stmt->execute(array(':name' => $charName));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : false;
    }

    private function nextId(PDO $db, $table, $column)
    {
        // Only these core tables/columns are valid for direct mail delivery.
        // The SQL is intentionally static so no identifier can ever come from user input.
        if ($table === 'mail' && $column === 'id') {
            $stmt = $db->query('SELECT COALESCE(MAX(`id`), 0) + 1 AS next_id FROM `mail`');
        } elseif ($table === 'item_instance' && $column === 'guid') {
            $stmt = $db->query('SELECT COALESCE(MAX(`guid`), 0) + 1 AS next_id FROM `item_instance`');
        } else {
            $this->logDeliveryError('DIRECT DB NEXT ID FAILED', 'Invalid table/column pair: ' . $table . '.' . $column);
            return 0;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['next_id'];
    }

    private function directMailMoney($charName, $money, $subject, $realmid, $body = '')
    {
        $db = $this->realmDb($realmid);
        $char = $this->getCharacter($charName, $realmid);
        if (!$db || !$char) {
            return 'Direct DB delivery failed: character or realm database was not found.';
        }

        try {
            $mailId = $this->nextId($db, 'mail', 'id');
            $now = time();
            $expire = $now + (30 * 24 * 60 * 60);
            $stmt = $db->prepare("INSERT INTO `mail` (`id`, `messageType`, `stationery`, `mailTemplateId`, `sender`, `receiver`, `subject`, `body`, `has_items`, `expire_time`, `deliver_time`, `money`, `cod`, `checked`) VALUES (:id, 0, 41, 0, 0, :receiver, :subject, :body, 0, :expire, :deliver, :money, 0, 0)");
            $stmt->execute(array(
                ':id' => $mailId,
                ':receiver' => (int)$char['guid'],
                ':subject' => $subject,
                ':body' => $body !== '' ? $body : 'Premium Store Delivery',
                ':expire' => $expire,
                ':deliver' => $now,
                ':money' => (int)$money,
            ));
            $this->logDeliveryError('DIRECT DB MAIL MONEY OK', $charName . ' money=' . (int)$money . ' mail=' . $mailId);
            return true;
        } catch (Throwable $e) {
            $this->logDeliveryError('DIRECT DB MAIL MONEY FAILED', $e->getMessage());
            return 'Direct DB money delivery failed: ' . $e->getMessage();
        }
    }

    private function directMailItems($charName, $items, $subject, $realmid, $body = '')
    {
        $db = $this->realmDb($realmid);
        $char = $this->getCharacter($charName, $realmid);
        if (!$db || !$char) {
            return 'Direct DB delivery failed: character or realm database was not found.';
        }

        $entries = preg_split('/\s+/', trim((string)$items));
        $entries = array_values(array_filter($entries, function ($v) { return ctype_digit((string)$v) && (int)$v > 0; }));
        if (count($entries) === 0) {
            return true;
        }

        try {
            $db->beginTransaction();
            $mailId = $this->nextId($db, 'mail', 'id');
            $itemGuid = $this->nextId($db, 'item_instance', 'guid');
            $now = time();
            $expire = $now + (30 * 24 * 60 * 60);

            $mail = $db->prepare("INSERT INTO `mail` (`id`, `messageType`, `stationery`, `mailTemplateId`, `sender`, `receiver`, `subject`, `body`, `has_items`, `expire_time`, `deliver_time`, `money`, `cod`, `checked`) VALUES (:id, 0, 41, 0, 0, :receiver, :subject, :body, 1, :expire, :deliver, 0, 0, 0)");
            $mail->execute(array(':id' => $mailId, ':receiver' => (int)$char['guid'], ':subject' => $subject, ':body' => $body !== '' ? $body : 'Premium Store Delivery', ':expire' => $expire, ':deliver' => $now));

            $insItem = $db->prepare("INSERT INTO `item_instance` (`guid`, `itemEntry`, `owner_guid`, `creatorGuid`, `giftCreatorGuid`, `count`, `duration`, `charges`, `flags`, `enchantments`, `randomPropertyId`, `durability`, `playedTime`, `text`) VALUES (:guid, :entry, :owner, 0, 0, 1, 0, '', 0, '', 0, 0, 0, '')");
            $insMailItem = $db->prepare("INSERT INTO `mail_items` (`mail_id`, `item_guid`, `receiver`) VALUES (:mail, :item, :receiver)");

            foreach ($entries as $entry) {
                $insItem->execute(array(':guid' => $itemGuid, ':entry' => (int)$entry, ':owner' => (int)$char['guid']));
                $insMailItem->execute(array(':mail' => $mailId, ':item' => $itemGuid, ':receiver' => (int)$char['guid']));
                $itemGuid++;
            }

            $db->commit();
            $this->logDeliveryError('DIRECT DB MAIL ITEMS OK', $charName . ' items=' . implode(',', $entries) . ' mail=' . $mailId);
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->logDeliveryError('DIRECT DB MAIL ITEMS FAILED', $e->getMessage());
            return 'Direct DB item delivery failed: ' . $e->getMessage();
        }
    }


    private function directAtLoginFlag($charName, $realmid, $flag, $label)
    {
        $db = $this->realmDb($realmid);
        $char = $this->getCharacter($charName, $realmid);
        if (!$db || !$char) {
            return 'Direct DB ' . $label . ' failed: character or realm database was not found.';
        }
        if ((int)$char['online'] === 1) {
            return 'Your character is online. Please fully log out from the game, then try again.';
        }
        try {
            $stmt = $db->prepare("UPDATE `characters` SET `at_login` = (`at_login` | :flag) WHERE `guid` = :guid LIMIT 1");
            $ok = $stmt->execute(array(':flag' => (int)$flag, ':guid' => (int)$char['guid']));
            if (!$ok) {
                return 'Direct DB ' . $label . ' failed: database update was rejected.';
            }
            $this->logDeliveryError('DIRECT DB AT_LOGIN OK', $label . ' | ' . $charName . ' flag=' . (int)$flag);
            return true;
        } catch (Throwable $e) {
            $this->logDeliveryError('DIRECT DB AT_LOGIN FAILED', $label . ' | ' . $e->getMessage());
            return 'Direct DB ' . $label . ' failed: ' . $e->getMessage();
        }
    }

    public function CheckConnection($realmid)
    {
        return $this->soap('.server info', $realmid);
    }

    public function sendItems($charName, $items, $subject, $realmid)
    {
        $result = $this->soap('.send items ' . $charName . ' "' . $subject . '" "Premium Store Delivery" ' . trim($items), $realmid);
        if ($result === true) {
            return true;
        }
        // Fallback: direct DB mail. Items may require relog or mailbox refresh if the player is online.
        return $this->directMailItems($charName, $items, $subject, $realmid, 'Premium Store Delivery');
    }

    public function sendMoney($charName, $money, $subject, $realmid)
    {
        $result = $this->soap('.send money ' . $charName . ' "' . $subject . '" "Premium Store Delivery" ' . (int)$money, $realmid);
        if ($result === true) {
            return true;
        }
        // Fallback: direct DB mail. Money may require relog or mailbox refresh if the player is online.
        return $this->directMailMoney($charName, $money, $subject, $realmid, 'Premium Store Delivery');
    }

    public function levelTo($charName, $level, $realmid)
    {
        $result = $this->soap('.character level ' . $charName . ' ' . (int)$level, $realmid);
        if ($result === true) {
            return true;
        }

        // Safe fallback only when the character is offline. Online DB level edits can be overwritten by the worldserver.
        $db = $this->realmDb($realmid);
        $char = $this->getCharacter($charName, $realmid);
        if (!$db || !$char) {
            return $result;
        }
        if ((int)$char['online'] === 1) {
            return $result . ' | SOAP failed and direct DB level fallback was skipped because the character is online. Enable SOAP correctly or log the character out before buying levels.';
        }
        if ((int)$char['level'] >= (int)$level) {
            return true;
        }
        try {
            $stmt = $db->prepare("UPDATE `characters` SET `level` = :level, `xp` = 0 WHERE `name` = :name LIMIT 1");
            $stmt->execute(array(':level' => (int)$level, ':name' => $charName));
            $this->logDeliveryError('DIRECT DB LEVEL OK', $charName . ' level=' . (int)$level);
            return true;
        } catch (Throwable $e) {
            $this->logDeliveryError('DIRECT DB LEVEL FAILED', $e->getMessage());
            return 'Direct DB level fallback failed: ' . $e->getMessage();
        }
    }

    public function FactionChange($charName, $realmid)
    {
        $result = $this->soap('.character changefaction ' . $charName, $realmid);
        if ($result === true) {
            return true;
        }
        // AzerothCore/Trinity at_login flag: 64 = change faction on next login.
        return $this->directAtLoginFlag($charName, $realmid, 64, 'faction change');
    }

    public function RaceChange($charName, $realmid)
    {
        return $this->soap('.character changerace ' . $charName, $realmid);
    }

    public function Customize($charName, $realmid)
    {
        $result = $this->soap('.character customize ' . $charName, $realmid);
        if ($result === true) {
            return true;
        }
        // AzerothCore/Trinity at_login flag: 8 = recustomize on next login.
        return $this->directAtLoginFlag($charName, $realmid, 8, 'recustomization');
    }

    public function Revive($charName, $realmid)
    {
        return $this->soap('.revive ' . $charName, $realmid);
    }

    public function Teleport($charName, $x, $y, $z, $mapId, $realmid)
    {
        return $this->soap('.pteleport ' . $charName . ' ' . $x . ' ' . $y . ' ' . $z . ' ' . $mapId, $realmid);
    }

    public function RefundItem($entry, $charName, $realymid)
    {
        return $this->soap('.refunditem ' . $charName . ' ' . (int)$entry, $realymid);
    }

    public function __destruct()
    {
        return true;
    }
}
