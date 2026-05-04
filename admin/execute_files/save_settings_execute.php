<?php
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
$DB->query("CREATE TABLE IF NOT EXISTS `site_settings` (`name` varchar(64) NOT NULL, `value` text NOT NULL, PRIMARY KEY (`name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
function setting_save($DB, $name, $value) { $q=$DB->prepare("REPLACE INTO `site_settings` (`name`,`value`) VALUES (?,?)"); $q->execute(array($name, $value)); }
$siteName = isset($_POST['site_name']) ? trim($_POST['site_name']) : 'Warcry';
$copyright = isset($_POST['copyright']) ? trim($_POST['copyright']) : '';
$homeWelcomeTitle = isset($_POST['home_welcome_title']) ? trim($_POST['home_welcome_title']) : 'Welcome to Warcry CMS';
$homeWelcomeText = isset($_POST['home_welcome_text']) ? trim($_POST['home_welcome_text']) : '';
if ($siteName === '') $siteName = 'Warcry';
if ($copyright === '') $copyright = 'Copyright &copy; <b>Warcry CMS</b>&trade; 2026. All Rights Reserved.';
if ($homeWelcomeTitle === '') $homeWelcomeTitle = 'Welcome to Warcry CMS';
if ($homeWelcomeText === '') $homeWelcomeText = "We are a growing server with 2 realms 1 blizzlike and 1 fun realm instant 255 with much custom content.\nIf you are looking forward to join our team or have any questions, please join our Discord channel or create a topic on the forum!";
setting_save($DB, 'site_name', $siteName);
setting_save($DB, 'copyright', $copyright);
setting_save($DB, 'home_welcome_title', $homeWelcomeTitle);
setting_save($DB, 'home_welcome_text', $homeWelcomeText);
if (isset($_FILES['favicon']) && is_uploaded_file($_FILES['favicon']['tmp_name'])) {
    $allowed = array('ico','png','jpg','jpeg','gif','webp');
    $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $dir = $config['RootPath'].'/uploads/settings';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $file = 'favicon.'.$ext;
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $dir.'/'.$file)) {
            setting_save($DB, 'favicon', 'uploads/settings/'.$file);
        }
    }
}
header('Location: '.$config['BaseURL'].'/admin/index.php?page=settings'); exit;
