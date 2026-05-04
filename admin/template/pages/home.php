<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
if ($error = $ERRORS->DoPrint('permissions')) { echo $error; }
unset($error);
?>
<nav id="secondary" class="disable-tabbing"><ul><li class="current"><a href="#maintab">Dashboard</a></li></ul></nav>
<section id="content">
  <div class="tab" id="maintab">
    <h2>Warcry Admin Dashboard</h2>
    <p class="muted">Clean control center with only the useful admin modules enabled.</p>

    <div class="admin-grid">
      <div class="stat-card"><span>CMS</span><strong>Online</strong><small class="muted">Admin panel ready</small></div>
      <div class="stat-card"><span>Theme</span><strong>2026</strong><small class="muted">Professional UI</small></div>
      <div class="stat-card"><span>Code</span><strong>Clean</strong><small class="muted">Unused demo content removed</small></div>
      <div class="stat-card"><span>Navigation</span><strong>Simple</strong><small class="muted">Functional pages only</small></div>
    </div>

    <h3>Quick navigation</h3>
    <div class="quick-actions">
      <a class="quick-card" href="index.php?page=news"><strong>Manage News</strong><p>Create, edit and control website news posts.</p></a>
      <a class="quick-card" href="index.php?page=articles"><strong>Manage Articles</strong><p>Control articles and content pages.</p></a>
      <a class="quick-card" href="index.php?page=store"><strong>Item Store</strong><p>Add or edit shop items.</p></a>
      <a class="quick-card" href="index.php?page=users"><strong>Users</strong><p>Review accounts and permissions.</p></a>
      <a class="quick-card" href="index.php?page=forums"><strong>Forums</strong><p>Manage forum categories and content.</p></a>
      <a class="quick-card" href="index.php?page=settings"><strong>Settings</strong><p>Update homepage, slider and CMS options.</p></a>
    </div>

    <div class="notice" style="margin-top:20px;">Old placeholder dashboard tabs, fake charts and unused demo buttons were removed.</div>
  </div>
</section>
