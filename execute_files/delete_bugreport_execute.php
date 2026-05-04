<?PHP
if (!defined('init_executes'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$CORE->loggedInOrReturn();
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$accountId = (int)$CURUSER->get('id');

if ($id > 0) {
    $stmt = $DB->prepare('DELETE FROM `bugtracker` WHERE `id` = :id AND `account` = :acc LIMIT 1');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':acc', $accountId, PDO::PARAM_INT);
    $stmt->execute();
}

header('Location: '.$config['BaseURL'].'/index.php?page=bugtracker');
exit;
