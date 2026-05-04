<?PHP
if (!defined('init_ajax'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

if (!$CURUSER->isOnline())
{
	echo 'You must be logged in!';
	die;
}

$avatarId = isset($_GET['id']) ? (int)$_GET['id'] : false;

if ($avatarId === false)
{
	echo 'You must select an avatar first.';
	die;
}

$storage = new AvatarGallery();

//validate the avatar
$newAvatar = $storage->get($avatarId);

if (!$newAvatar)
{
	echo 'The selected avatar is invalid.';
	die;
}

unset($storage);

//Let's validate the ranking requirements
if ($newAvatar->rank() > $CURUSER->getRank()->int())
{
	echo 'The selected avatar requires greater user rank.';
	die;
}

$update = $DB->prepare("UPDATE `account_data` SET `avatar` = :avatar, `avatarType` = :type WHERE `id` = :account LIMIT 1;");

$accountId = (int)$CURUSER->get('id');
$avatarValue = (int)$newAvatar->int();
$avatarType = (int)$newAvatar->type();

$update->bindValue(':account', $accountId, PDO::PARAM_INT);
$update->bindValue(':avatar', $avatarValue, PDO::PARAM_INT);
$update->bindValue(':type', $avatarType, PDO::PARAM_INT);

if ($update->execute())
{
	echo 'OK';
}
else
{
	echo 'The website failed to update your avatar.';
}

?>