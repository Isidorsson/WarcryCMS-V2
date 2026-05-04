<?PHP
if (!defined('init_executes'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$CORE->loggedInOrReturn();
$CORE->CheckPermissionsExecute(PERMISSION_ARTICLES);

$ERRORS->NewInstance('flushArticleComments');
$ERRORS->onSuccess('Article comments were successfully flushed.', '/index.php?page=articles');

$scope = isset($_GET['scope']) ? $_GET['scope'] : 'article';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($scope === 'all')
{
    $delete = $DB->prepare('DELETE FROM `article_comments`;');
    $delete->execute();
}
else
{
    if (!$id)
    {
        $ERRORS->Add('Missing article id.');
        $ERRORS->Check('/index.php?page=articles');
    }

    $check = $DB->prepare('SELECT `id` FROM `articles` WHERE `id` = :id LIMIT 1;');
    $check->bindParam(':id', $id, PDO::PARAM_INT);
    $check->execute();

    if ($check->rowCount() == 0)
    {
        $ERRORS->Add('Invalid article id.');
        $ERRORS->Check('/index.php?page=articles');
    }

    $delete = $DB->prepare('DELETE FROM `article_comments` WHERE `article` = :id;');
    $delete->bindParam(':id', $id, PDO::PARAM_INT);
    $delete->execute();
}

$ERRORS->triggerSuccess();
$ERRORS->Check('/index.php?page=articles');
exit;
