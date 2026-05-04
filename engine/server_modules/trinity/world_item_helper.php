<?php
if (!defined('init_engine') && !defined('init_ajax') && !defined('init_executes') && !defined('init_pages')) {
    header('HTTP/1.0 404 not found');
    exit;
}

if (!function_exists('warcry_world_db')) {
    function warcry_world_db() {
        global $config, $auth_config, $PDO_config, $realms_config;
        static $db = null;
        if ($db instanceof PDO) {
            return $db;
        }
        $host = isset($auth_config['DatabaseHost']) ? $auth_config['DatabaseHost'] : (isset($config['DatabaseHost']) ? $config['DatabaseHost'] : '127.0.0.1');
        $user = isset($auth_config['DatabaseUser']) ? $auth_config['DatabaseUser'] : (isset($config['DatabaseUser']) ? $config['DatabaseUser'] : 'root');
        $pass = isset($auth_config['DatabasePass']) ? $auth_config['DatabasePass'] : (isset($config['DatabasePass']) ? $config['DatabasePass'] : '');
        $encoding = isset($auth_config['DatabaseEncoding']) ? $auth_config['DatabaseEncoding'] : 'utf8';
        $dbname = 'world';
        if (isset($config['WorldDatabaseName']) && $config['WorldDatabaseName'] !== '') {
            $dbname = $config['WorldDatabaseName'];
        } elseif (isset($GLOBALS['world_config']['DatabaseName']) && $GLOBALS['world_config']['DatabaseName'] !== '') {
            $dbname = $GLOBALS['world_config']['DatabaseName'];
        } elseif (isset($realms_config[1]['world_database']) && $realms_config[1]['world_database'] !== '') {
            $dbname = $realms_config[1]['world_database'];
        }
        try {
            $db = new PDO('mysql:dbname='.$dbname.';host='.$host.';', $user, $pass, null);
            $db->setAttribute(PDO::ATTR_ERRMODE, isset($PDO_config['errorHandler']) ? $PDO_config['errorHandler'] : PDO::ERRMODE_WARNING);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->query("SET NAMES '".$encoding."'");
            return $db;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('warcry_store_ensure_icon_column')) {
    function warcry_store_ensure_icon_column() {
        global $DB;
        static $done = false;
        if ($done) return;
        $done = true;
        try {
            $check = $DB->query("SHOW COLUMNS FROM `store_items` LIKE 'icon'");
            if (!$check || $check->rowCount() == 0) {
                $DB->exec("ALTER TABLE `store_items` ADD COLUMN `icon` VARCHAR(100) NOT NULL DEFAULT 'inv_misc_questionmark' AFTER `displayid`");
            }
        } catch (Throwable $e) {
            // Keep the CMS working even if the DB user has no ALTER permission.
        }
    }
}

if (!function_exists('warcry_clean_icon_name')) {
    function warcry_clean_icon_name($icon) {
        $icon = strtolower(trim((string)$icon));
        $icon = preg_replace('/[^a-z0-9_]/', '', $icon);
        return $icon !== '' ? $icon : 'inv_misc_questionmark';
    }
}

if (!function_exists('warcry_known_icon_from_displayid')) {
    function warcry_known_icon_from_displayid($displayid, $entry = 0) {
        $entry = (int)$entry;
        $displayid = (int)$displayid;
        $knownByEntry = array(
            32837 => 'inv_weapon_glave_01',
            32838 => 'inv_weapon_glave_01',
        );
        if (isset($knownByEntry[$entry])) return $knownByEntry[$entry];
        $knownByDisplay = array(
            45479 => 'inv_weapon_glave_01',
            45481 => 'inv_weapon_glave_01',
        );
        if (isset($knownByDisplay[$displayid])) return $knownByDisplay[$displayid];
        return 'inv_misc_questionmark';
    }
}

if (!function_exists('warcry_fetch_wowhead_icon')) {
    function warcry_fetch_wowhead_icon($entry) {
        global $config;
        $entry = (int)$entry;
        if ($entry <= 0) return '';
        $cacheDir = isset($config['RootPath']) ? $config['RootPath'].'/cache' : sys_get_temp_dir();
        $cacheFile = $cacheDir . '/wowhead_icon_' . $entry . '.txt';
        if (is_file($cacheFile)) {
            $cached = trim((string)@file_get_contents($cacheFile));
            if ($cached !== '') return warcry_clean_icon_name($cached);
        }
        $url = 'https://www.wowhead.com/wotlk/item=' . $entry . '&xml';
        $ctx = stream_context_create(array('http' => array('timeout' => 4, 'user_agent' => 'WarcryCMS/1.0')));
        $xml = @file_get_contents($url, false, $ctx);
        if ($xml && preg_match('~<icon[^>]*>([^<]+)</icon>~i', $xml, $m)) {
            $icon = warcry_clean_icon_name($m[1]);
            @file_put_contents($cacheFile, $icon);
            return $icon;
        }
        return '';
    }
}

if (!function_exists('warcry_get_world_item')) {
    function warcry_get_world_item($entry) {
        $entry = (int)$entry;
        if ($entry <= 0) return false;
        $db = warcry_world_db();
        if (!$db) return false;
        $stmt = $db->prepare("SELECT `entry`, `class`, `subclass`, `name`, `displayid`, `Quality`, `Flags`, `InventoryType`, `AllowableClass`, `ItemLevel`, `description` FROM `item_template` WHERE `entry` = :entry LIMIT 1");
        $stmt->execute(array(':entry' => $entry));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        $icon = warcry_fetch_wowhead_icon($entry);
        if ($icon === '') $icon = warcry_known_icon_from_displayid((int)$row['displayid'], $entry);
        $row['icon'] = warcry_clean_icon_name($icon);
        return $row;
    }
}

if (!function_exists('warcry_get_store_item_icon')) {
    function warcry_get_store_item_icon(array $row) {
        if (isset($row['icon']) && trim($row['icon']) !== '') {
            return warcry_clean_icon_name($row['icon']);
        }
        $entry = isset($row['entry']) ? (int)$row['entry'] : 0;
        $displayid = isset($row['displayid']) ? (int)$row['displayid'] : 0;
        $icon = warcry_fetch_wowhead_icon($entry);
        if ($icon === '') $icon = warcry_known_icon_from_displayid($displayid, $entry);
        return warcry_clean_icon_name($icon);
    }
}

if (!function_exists('warcry_repair_store_item_from_world')) {
    function warcry_repair_store_item_from_world($storeIdOrEntry, $isEntry = false) {
        global $DB;
        warcry_store_ensure_icon_column();
        $value = (int)$storeIdOrEntry;
        if ($value <= 0) return false;
        $world = warcry_get_world_item($value);
        if (!$world && !$isEntry) {
            $stmt = $DB->prepare("SELECT `entry` FROM `store_items` WHERE `id` = :id LIMIT 1");
            $stmt->execute(array(':id' => $value));
            $store = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($store) $world = warcry_get_world_item((int)$store['entry']);
        }
        if (!$world) return false;
        $sql = "UPDATE `store_items` SET `name`=:name, `class`=:class, `subclass`=:subclass, `ItemLevel`=:itemlevel, `Quality`=:quality, `InventoryType`=:invtype, `displayid`=:displayid, `icon`=:icon, `Flags`=:flags, `AllowableClass`=:allowable, `description`=:description WHERE ".($isEntry ? "`entry`=:wherev" : "`id`=:wherev");
        $upd = $DB->prepare($sql);
        return $upd->execute(array(
            ':name' => $world['name'], ':class' => (int)$world['class'], ':subclass' => (int)$world['subclass'], ':itemlevel' => (int)$world['ItemLevel'], ':quality' => (int)$world['Quality'], ':invtype' => (int)$world['InventoryType'], ':displayid' => (int)$world['displayid'], ':icon' => $world['icon'], ':flags' => (string)$world['Flags'], ':allowable' => (int)$world['AllowableClass'], ':description' => (string)$world['description'], ':wherev' => $value
        ));
    }
}

if (!function_exists('warcry_realm_match_sql')) {
    function warcry_realm_match_sql($column = '`realm`') {
        return "($column = 'all' OR $column = '-1' OR $column = :realmId OR $column = :realmName OR FIND_IN_SET(:realmIdCsv, REPLACE($column, ' ', '')) > 0)";
    }
}

if (!function_exists('warcry_bind_realm_match')) {
    function warcry_bind_realm_match(PDOStatement $stmt, $realmId) {
        global $realms_config;
        $realmId = (string)((int)$realmId > 0 ? (int)$realmId : 1);
        $realmName = isset($realms_config[(int)$realmId]['name']) ? $realms_config[(int)$realmId]['name'] : 'AzerothCore';
        $stmt->bindValue(':realmId', $realmId, PDO::PARAM_STR);
        $stmt->bindValue(':realmName', $realmName, PDO::PARAM_STR);
        $stmt->bindValue(':realmIdCsv', $realmId, PDO::PARAM_STR);
    }
}

if (!function_exists('warcry_item_icon_img')) {
    function warcry_item_icon_img($entry, $displayid = 0, $icon = '') {
        $icon = warcry_clean_icon_name($icon !== '' ? $icon : warcry_known_icon_from_displayid($displayid, $entry));
        return '<img src="https://wow.zamimg.com/images/wow/icons/large/'.$icon.'.jpg" alt="item" class="item-icon-img" onerror="this.src=\'https://wow.zamimg.com/images/wow/icons/large/inv_misc_questionmark.jpg\';">';
    }
}

if (!function_exists('warcry_item_icon_html')) {
    function warcry_item_icon_html($entry, $displayid = 0, $icon = '', $link = true) {
        $img = warcry_item_icon_img($entry, $displayid, $icon);
        if (!$link) return $img;
        $entry = (int)$entry;
        return '<a class="item-icon-link" href="https://www.wowhead.com/wotlk/item='.$entry.'" rel="item='.$entry.'" target="_blank">'.$img.'</a>';
    }
}
