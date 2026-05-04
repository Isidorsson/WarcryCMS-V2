<?php
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
$DB->query("CREATE TABLE IF NOT EXISTS `changelogs` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `revision` mediumint(8) unsigned NOT NULL DEFAULT 1, `changelog` tinyint(2) NOT NULL DEFAULT 1, `text` text NOT NULL, `author` varchar(150) NOT NULL, `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
$cat = isset($_POST['changelog']) ? (int)$_POST['changelog'] : 1; if (!in_array($cat, array(1,2))) $cat = 1;
$rev = isset($_POST['revision']) ? max(1, (int)$_POST['revision']) : 1;
$text = isset($_POST['text']) ? trim($_POST['text']) : '';
$author = isset($_POST['author']) && trim($_POST['author']) !== '' ? trim($_POST['author']) : $CURUSER->get('displayName');
if ($text !== '') { $q=$DB->prepare("INSERT INTO `changelogs` (`revision`,`changelog`,`text`,`author`) VALUES (:revision,:changelog,:text,:author)"); $q->execute(array(':revision'=>$rev, ':changelog'=>$cat, ':text'=>$text, ':author'=>$author)); }
header('Location: '.$config['BaseURL'].'/admin/index.php?page=changelogs'); exit;
