<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_NEWS)) { $CORE->ErrorBox('You do not have the required permissions.'); }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$res = $DB->query("SELECT * FROM `news` ORDER BY `id` DESC LIMIT 250");
$news = $res ? $res->fetchAll() : array();
?>
<nav id="secondary" class="disable-tabbing"><ul><li class="current"><a href="index.php?page=news">News</a></li><li><a href="index.php?page=news-post">Post News</a></li></ul></nav>
<section id="content"><div class="tab" id="maintab"><h2>News Management</h2>
  <div class="admin-actions"><a class="button primary" href="index.php?page=news-post">Create News</a><span class="pill">Source: warcry.news</span></div>
  <table class="datatable"><thead><tr><th>ID</th><th>Title</th><th>Short Text</th><th>Author</th><th>Added</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($news as $n): ?>
    <tr><td><?php echo (int)$n['id']; ?></td><td><strong><?php echo h($n['title']); ?></strong></td><td><?php echo h($n['shortText']); ?></td><td><?php echo h($n['authorStr']); ?></td><td><?php echo h($n['added']); ?></td><td><span class="button-group"><a class="button" href="index.php?page=news-edit&id=<?php echo (int)$n['id']; ?>">Edit</a><a class="button danger" onclick="return deletecheck('Delete this news?');" href="execute.php?take=delete&action=news&id=<?php echo (int)$n['id']; ?>">Remove</a></span></td></tr>
  <?php endforeach; if (!$news): ?><tr><td colspan="6">No news found.</td></tr><?php endif; ?>
  </tbody></table>
</div>
