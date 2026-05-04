<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$DB->query("CREATE TABLE IF NOT EXISTS `changelogs` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `revision` mediumint(8) unsigned NOT NULL DEFAULT 1, `changelog` tinyint(2) NOT NULL DEFAULT 1, `text` text NOT NULL, `author` varchar(150) NOT NULL, `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
$rows = $DB->query("SELECT * FROM `changelogs` ORDER BY `id` DESC LIMIT 250")->fetchAll();
?>
<nav id="secondary" class="disable-tabbing"><ul><li class="current"><a href="index.php?page=changelogs">Changelogs</a></li></ul></nav>
<section id="content"><div class="tab" id="maintab"><h2>Changelog Management</h2>
  <div class="notice">Push changelogs directly to the website page. Category 1 = Website, Category 2 = Core.</div>
  <div class="form admin-card">
    <form method="post" action="<?php echo $config['BaseURL']; ?>/admin/execute.php?take=save_changelog">
      <section><label>Category*</label><div><select name="changelog" required><option value="1">Website Changelog</option><option value="2">Core Changelog</option></select></div></section>
      <section><label>Revision*</label><div><input type="number" name="revision" value="1" min="1" required style="width:160px"></div></section>
      <section><label>Author</label><div><input type="text" name="author" value="<?php echo h($CURUSER->get('displayName')); ?>" maxlength="150" style="width:100%"></div></section>
      <section><label>Change text*</label><div><textarea name="text" rows="6" required placeholder="Example: Added Armory media upload support."></textarea></div></section>
      <button type="submit" class="button primary">Publish Changelog</button>
    </form>
  </div>
  <h3>Recent Changelogs</h3>
  <table class="datatable"><thead><tr><th>ID</th><th>Category</th><th>Rev</th><th>Author</th><th>Text</th><th>Date</th><th>Action</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr>
    <td><?php echo (int)$r['id']; ?></td><td><?php echo ((int)$r['changelog'] === 2 ? 'Core' : 'Website'); ?></td><td>Rev <?php echo (int)$r['revision']; ?></td><td><?php echo h($r['author']); ?></td><td><?php echo nl2br(h($r['text'])); ?></td><td><?php echo h($r['time']); ?></td><td><a class="button danger" onclick="return confirm('Delete this changelog?');" href="execute.php?take=delete_changelog&id=<?php echo (int)$r['id']; ?>">Delete</a></td>
  </tr><?php endforeach; if (!$rows): ?><tr><td colspan="7">No changelogs found.</td></tr><?php endif; ?>
  </tbody></table>
</div></section>
