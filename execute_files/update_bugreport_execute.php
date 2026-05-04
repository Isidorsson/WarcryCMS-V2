<?PHP
if (!defined('init_executes'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$CORE->loggedInOrReturn();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$priority = isset($_POST['priority']) ? (int)$_POST['priority'] : BT_PRIORITY_NORMAL;
$accountId = (int)$CURUSER->get('id');

if ($id <= 0 || $title === '' || $content === '') {
    header('Location: '.$config['BaseURL'].'/index.php?page=bugtracker');
    exit;
}
if (!in_array($priority, array(BT_PRIORITY_LOW, BT_PRIORITY_NORMAL, BT_PRIORITY_HIGH))) {
    $priority = BT_PRIORITY_NORMAL;
}

$stmt = $DB->prepare('UPDATE `bugtracker` SET `title` = :title, `content` = :content, `priority` = :priority WHERE `id` = :id AND `account` = :acc LIMIT 1');
$stmt->bindValue(':title', $title, PDO::PARAM_STR);
$stmt->bindValue(':content', $content, PDO::PARAM_STR);
$stmt->bindValue(':priority', $priority, PDO::PARAM_INT);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':acc', $accountId, PDO::PARAM_INT);
$stmt->execute();

header('Location: '.$config['BaseURL'].'/index.php?page=bugtracker');
exit;
