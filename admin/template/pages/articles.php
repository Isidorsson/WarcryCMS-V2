<?PHP
if (!defined('init_pages'))
{
    header('HTTP/1.0 404 not found');
    exit;
}
?>

<nav id="secondary" class="disable-tabbing">
    <ul>
        <li class="current"><a href="?page=articles">Articles</a></li>
        <li><a href="?page=new-article">New Article</a></li>
    </ul>
</nav>

<?php
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_ARTICLES))
{
    $CORE->ErrorBox('You do not have the required permissions.');
}

$articles = array();
try
{
    $query = $DB->query("\n        SELECT\n            a.`id`,\n            a.`title`,\n            a.`short_text`,\n            a.`views`,\n            a.`added`,\n            a.`author`,\n            a.`image`,\n            a.`comments`,\n            COALESCE(ad.`displayName`, CONCAT('User #', a.`author`)) AS `author_name`,\n            COUNT(ac.`id`) AS `comment_total`\n        FROM `articles` a\n        LEFT JOIN `account_data` ad ON ad.`id` = a.`author`\n        LEFT JOIN `article_comments` ac ON ac.`article` = a.`id`\n        GROUP BY a.`id`, a.`title`, a.`short_text`, a.`views`, a.`added`, a.`author`, a.`image`, a.`comments`, ad.`displayName`\n        ORDER BY a.`id` DESC\n    ");

    while ($row = $query->fetch(PDO::FETCH_ASSOC))
    {
        $articles[] = $row;
    }
}
catch (Exception $e)
{
    $articlesError = $e->getMessage();
}
?>

<section id="content">
    <div class="tab" id="maintab">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;">
            <div>
                <h2>Articles Management</h2>
                <p style="margin-top:4px;color:#9fb4d8;">Manage article content, images, views and comment availability.</p>
            </div>
            <span class="button-group">
                <a href="?page=new-article" class="button icon add">New Article</a>
                <a href="execute.php?take=flush_article_comments&scope=all" onclick="return deletecheck('Are you sure you want to delete ALL article comments? This cannot be undone.');" class="button icon remove danger">Flush All Comments</a>
            </span>
        </div>

        <?php
        if ($success = $ERRORS->successPrint(array('addArticle', 'editArticle', 'del_article', 'flushArticleComments')))
        {
            echo $success;
        }
        unset($success);

        if (isset($articlesError))
        {
            echo '<div class="error"><strong>Articles could not be loaded:</strong> ' . htmlspecialchars($articlesError, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        ?>

        <div style="overflow-x:auto;">
            <table id="articlesTable" class="datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Short Text</th>
                        <th>Views</th>
                        <th>Added</th>
                        <th>Author</th>
                        <th>Comments</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($articles) === 0): ?>
                        <tr>
                            <td colspan="9" style="text-align:center;padding:25px;color:#9fb4d8;">No articles found in the database.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($articles as $article): ?>
                            <?php
                            $id = (int)$article['id'];
                            $commentsEnabled = ((int)$article['comments'] === 1);
                            ?>
                            <tr>
                                <td><?php echo $id; ?></td>
                                <td><?php echo htmlspecialchars(stripslashes($article['title']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><div style="max-width:500px;white-space:normal;"><?php echo htmlspecialchars(stripslashes($article['short_text']), ENT_QUOTES, 'UTF-8'); ?></div></td>
                                <td><?php echo (int)$article['views']; ?></td>
                                <td><?php echo htmlspecialchars($article['added'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><a href="index.php?page=user-preview&uid=<?php echo (int)$article['author']; ?>"><?php echo htmlspecialchars($article['author_name'], ENT_QUOTES, 'UTF-8'); ?></a> [<?php echo (int)$article['author']; ?>]</td>
                                <td><?php echo (int)$article['comment_total']; ?></td>
                                <td>
                                    <?php if ($commentsEnabled): ?>
                                        <span style="color:#69d36f;font-weight:bold;">Comments Enabled</span>
                                    <?php else: ?>
                                        <span style="color:#ff6b6b;font-weight:bold;">Comments Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="button-group">
                                        <a href="index.php?page=edit-article&id=<?php echo $id; ?>" class="button icon edit">Edit</a>
                                        <a href="execute.php?take=flush_article_comments&scope=article&id=<?php echo $id; ?>" onclick="return deletecheck('Are you sure you want to delete all comments for this article?');" class="button icon remove">Flush Comments</a>
                                        <a href="execute.php?take=delete&action=article&id=<?php echo $id; ?>" onclick="return deletecheck('Are you sure you want to delete this article?');" class="button icon remove danger">Remove</a>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script src="template/js/jquery.datatables.js" type="text/javascript"></script>
<script type="text/javascript">
$(document).ready(function()
{
    if ($('#articlesTable tbody tr td[colspan]').length === 0)
    {
        $('#articlesTable').dataTable({
            "aaSorting": [[0, "desc"]],
            "aoColumnDefs": [
                { "bSortable": false, "aTargets": [2, 8] }
            ]
        });
    }
});
</script>
