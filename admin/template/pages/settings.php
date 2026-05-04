<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$DB->query("CREATE TABLE IF NOT EXISTS `site_settings` (`name` varchar(64) NOT NULL, `value` text NOT NULL, PRIMARY KEY (`name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
function setting_get_admin($DB, $name, $default='') { $s=$DB->prepare("SELECT `value` FROM `site_settings` WHERE `name`=? LIMIT 1"); $s->execute(array($name)); $v=$s->fetchColumn(); return ($v===false?$default:$v); }
$siteName = setting_get_admin($DB, 'site_name', isset($config['SiteName']) ? $config['SiteName'] : 'Warcry');
$copyright = setting_get_admin($DB, 'copyright', 'Copyright &copy; <b>Warcry CMS</b>&trade; 2026. All Rights Reserved.');
$favicon = setting_get_admin($DB, 'favicon', 'template/style/images/favicon.ico');
$homeWelcomeTitle = setting_get_admin($DB, 'home_welcome_title', 'Welcome to Warcry CMS');
$homeWelcomeText = setting_get_admin($DB, 'home_welcome_text', "We are a growing server with 2 realms 1 blizzlike and 1 fun realm instant 255 with much custom content.\nIf you are looking forward to join our team or have any questions, please join our Discord channel or create a topic on the forum!");
?>
<nav id="secondary" class="disable-tabbing"><ul><li class="current"><a href="index.php?page=settings">Settings</a></li></ul></nav>
<section id="content"><div class="tab" id="maintab"><h2>Site Settings</h2>
  <div class="notice">Customize your public site name, homepage welcome text, footer copyright and favicon.</div>
  <div class="form admin-card">
    <form method="post" enctype="multipart/form-data" action="<?php echo $config['BaseURL']; ?>/admin/execute.php?take=save_settings">
      <section><label>Site Name*</label><div><input type="text" name="site_name" value="<?php echo h($siteName); ?>" maxlength="150" required style="width:100%"></div></section>
      <section><label>Homepage Welcome Title*</label><div><input type="text" name="home_welcome_title" value="<?php echo h($homeWelcomeTitle); ?>" maxlength="150" required style="width:100%"><small>This controls the big title on the home banner.</small></div></section>
      <section><label>Homepage Welcome Text*</label><div><textarea name="home_welcome_text" rows="5" required><?php echo h($homeWelcomeText); ?></textarea><small>Line breaks are kept on the website.</small></div></section>
      <section><label>Copyright*</label><div><textarea name="copyright" rows="4" required><?php echo h($copyright); ?></textarea><small>HTML allowed for simple tags like &lt;b&gt; and &amp;copy;.</small></div></section>
      <section><label>Current Favicon</label><div><img src="<?php echo $config['BaseURL'].'/'.h($favicon); ?>?v=<?php echo time(); ?>" style="width:32px;height:32px;background:#111;border:1px solid #333;padding:4px" alt="favicon"> <code><?php echo h($favicon); ?></code></div></section>
      <section><label>Upload New Favicon</label><div><input type="file" name="favicon" accept=".ico,.png,.jpg,.jpeg,.gif,.webp"><small>Recommended: .ico or 32x32 PNG.</small></div></section>
      <button type="submit" class="button primary">Save Settings</button>
    </form>
  </div>
</div></section>
