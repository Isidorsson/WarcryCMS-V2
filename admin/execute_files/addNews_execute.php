<?php
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
$CORE->loggedInOrReturn();
$CORE->CheckPermissionsExecute(PERMISSION_NEWS);
$ERRORS->NewInstance('addNews');
$ERRORS->onSuccess('The news was successfully posted.', '/index.php?page=news');
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$shortText = isset($_POST['shortText']) ? trim($_POST['shortText']) : '';
$text = isset($_POST['text']) ? trim($_POST['text']) : '';
$image = isset($_POST['image']) ? trim($_POST['image']) : 'default.png';
if ($title === '') $ERRORS->Add('Please enter a news headline.');
if ($shortText === '') $ERRORS->Add('Please enter short text.');
if ($text === '') $ERRORS->Add('Please enter content.');
$ERRORS->Check('/index.php?page=news-post');
if ($image === '') $image = 'default.png';
$image = basename($image);
$now = date('Y-m-d H:i:s');
$authorId = (int)$CURUSER->get('id');
$author = (string)$CURUSER->get('displayName');
$insert = $DB->prepare("INSERT INTO `news` (`title`, `shortText`, `text`, `image`, `added`, `author`, `authorStr`) VALUES (:title, :shortText, :text, :image, :added, :author, :authorStr)");
$ok = $insert->execute(array(':title'=>$title, ':shortText'=>$shortText, ':text'=>$text, ':image'=>$image, ':added'=>$now, ':author'=>$authorId, ':authorStr'=>$author));
if (!$ok || $insert->rowCount() < 1) { $ERRORS->Add('The website failed to insert the news record.'); $ERRORS->Check('/index.php?page=news-post'); }
$ERRORS->triggerSuccess();
exit;
