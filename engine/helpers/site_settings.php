<?php
if (!function_exists('warcry_site_setting')) {
    function warcry_site_setting($name, $default = '') {
        if (!isset($GLOBALS['DB']) || !$GLOBALS['DB']) return $default;
        try {
            $GLOBALS['DB']->query("CREATE TABLE IF NOT EXISTS `site_settings` (`name` varchar(64) NOT NULL, `value` text NOT NULL, PRIMARY KEY (`name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
            $q = $GLOBALS['DB']->prepare("SELECT `value` FROM `site_settings` WHERE `name`=? LIMIT 1");
            $q->execute(array($name));
            $v = $q->fetchColumn();
            return ($v === false) ? $default : $v;
        } catch (Exception $e) { return $default; }
    }
}
?>
