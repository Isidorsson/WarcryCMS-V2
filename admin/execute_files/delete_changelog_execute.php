<?php
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) { $q=$DB->prepare("DELETE FROM `changelogs` WHERE `id`=? LIMIT 1"); $q->execute(array($id)); }
header('Location: '.$config['BaseURL'].'/admin/index.php?page=changelogs'); exit;
