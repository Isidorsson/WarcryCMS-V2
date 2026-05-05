<?php
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
if (function_exists('warcry_csrf_verify') && !warcry_csrf_verify()) {
    $ERRORS->NewInstance('site_settings');
    $ERRORS->Add('Security token expired. Please try again.');
    $ERRORS->Check('/index.php?page=settings');
    exit;
}
$ERRORS->NewInstance('site_settings');
if (isset($_POST['security_action']) && $_POST['security_action'] === 'admin_panel_code') {
    $a = isset($_POST['admin_panel_code_new']) ? trim((string)$_POST['admin_panel_code_new']) : '';
    $b = isset($_POST['admin_panel_code_confirm']) ? trim((string)$_POST['admin_panel_code_confirm']) : '';
    if ($a === '' || $a !== $b || strlen($a) < 4) {
        $ERRORS->Add('ACP security code was not changed. Codes must match and be at least 4 characters.');
        $ERRORS->Check('/index.php?page=settings');
        exit;
    }
    if (function_exists('warcry_admin_save_panel_code') && warcry_admin_save_panel_code($a)) {
        unset($_SESSION['WARCRY_ADMIN_PANEL_UNLOCKED']);
        header('Location: '.$config['BaseURL'].'/admin/login.php?code=acp_code_updated');
        exit;
    }
    $ERRORS->Add('Could not write configuration/Admin_panel.php. Check file permissions.');
    $ERRORS->Check('/index.php?page=settings');
    exit;
}
$DB->query("CREATE TABLE IF NOT EXISTS `site_settings` (`name` varchar(64) NOT NULL, `value` text NOT NULL, PRIMARY KEY (`name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
function wc_setting_save($DB, $name, $value) { $q=$DB->prepare("REPLACE INTO `site_settings` (`name`,`value`) VALUES (?,?)"); $q->execute(array($name, $value)); }
$siteName = isset($_POST['site_name']) ? trim($_POST['site_name']) : 'Warcry';
$realmlist = isset($_POST['realmlist']) ? trim($_POST['realmlist']) : 'logon.project-reborn.com';
$footer = isset($_POST['footer_copyright']) ? trim($_POST['footer_copyright']) : '';
$homeWelcomeTitle = isset($_POST['home_welcome_title']) ? trim($_POST['home_welcome_title']) : 'Welcome to Warcry CMS';
$homeWelcomeText = isset($_POST['home_welcome_text']) ? trim($_POST['home_welcome_text']) : '';
if ($siteName === '') $siteName = 'Warcry';
if ($realmlist === '') $realmlist = 'logon.project-reborn.com';
if ($footer === '') $footer = 'Copyright &copy; <b>WarcryCMS</b>&trade; 2026. All Rights Reserved.';
if ($homeWelcomeTitle === '') $homeWelcomeTitle = 'Welcome to Warcry CMS';
if ($homeWelcomeText === '') $homeWelcomeText = "We are a growing server with 2 realms 1 blizzlike and 1 fun realm instant 255 with much custom content.\nIf you are looking forward to join our team or have any questions, please join our Discord channel or create a topic on the forum!";
wc_setting_save($DB, 'site_name', $siteName);
wc_setting_save($DB, 'realmlist', $realmlist);
wc_setting_save($DB, 'footer_copyright', $footer);
wc_setting_save($DB, 'copyright', $footer);
wc_setting_save($DB, 'home_welcome_title', $homeWelcomeTitle);
wc_setting_save($DB, 'home_welcome_text', $homeWelcomeText);
if (isset($_FILES['favicon']) && is_uploaded_file($_FILES['favicon']['tmp_name'])) {
    $allowed = array('ico'=>'image/x-icon','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp');
    $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
    $sizeOk = (int)$_FILES['favicon']['size'] <= 1048576;
    $mime = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $fi ? (string)finfo_file($fi, $_FILES['favicon']['tmp_name']) : '';
        if ($fi) finfo_close($fi);
    }
    if (isset($allowed[$ext]) && $sizeOk && ($mime === '' || $mime === $allowed[$ext] || ($ext === 'ico' && in_array($mime, array('image/x-icon','image/vnd.microsoft.icon','application/octet-stream'))))) {
        $dir = $config['RootPath'].'/uploads/settings';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        @file_put_contents($dir.'/.htaccess', "Options -Indexes
<FilesMatch \"\\.(php|phtml|phar|php[0-9]|cgi|pl|py|sh|bash|exe|dll|com|bat|cmd)$$\">
Require all denied
</FilesMatch>
");
        $file = 'favicon.'.$ext;
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $dir.'/'.$file)) {
            wc_setting_save($DB, 'favicon_path', 'uploads/settings/'.$file);
            wc_setting_save($DB, 'favicon', 'uploads/settings/'.$file);
        }
    }
}
header('Location: '.$config['BaseURL'].'/admin/index.php?page=settings'); exit;
