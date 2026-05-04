<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_NEWS)) { $CORE->ErrorBox('You do not have the required permissions.'); }
?>
<nav id="secondary" class="disable-tabbing"><ul><li><a href="index.php?page=news">News</a></li><li class="current"><a href="index.php?page=news-post">Post News</a></li></ul></nav>
<section id="content"><div class="tab" id="maintab"><h2>Post News</h2>
  <div class="notice">This simplified editor avoids the old broken 2011 upload/BBCode scripts. You can edit images later if needed.</div>
  <?php if ($error = $ERRORS->DoPrint('addNews')) { echo $error; } ?>
  <div class="form admin-card"><form method="post" action="<?php echo $config['BaseURL']; ?>/admin/execute.php?take=addNews" id="newsForm" name="addNewsForm">
    <section><label>Headline*<small>Maximum 250 characters.</small></label><div><input name="title" type="text" required maxlength="250" style="width:100%"></div></section>
    <section><label>Short Text*<small>Small preview text shown on the site.</small></label><div><textarea name="shortText" required maxlength="500" rows="5"></textarea></div></section>
    <section><label>Content*<small>HTML/plain text supported by the CMS.</small></label><div><textarea name="text" required rows="12"></textarea></div></section>
    <section><label>Image filename<small>Optional. Default is <code>default.png</code>.</small></label><div><input name="image" type="text" placeholder="default.png" style="width:100%"></div></section>
    <button type="submit" class="button primary">Publish News</button> <a class="button" href="index.php?page=news">Cancel</a>
  </form></div>
</div>
