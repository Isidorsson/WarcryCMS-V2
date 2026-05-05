<?php
include_once 'engine/initialize.php';
if ($CURUSER->isOnline()) {
    if (function_exists('warcry_admin_is_allowed_account') && warcry_admin_is_allowed_account((int)$CURUSER->get('id'))) {
        header('Location: '.$config['BaseURL'].'/admin/index.php');
        exit;
    }
    header('Location: '.$config['BaseURL'].'/index.php');
    exit;
}
$panelUnlocked = function_exists('warcry_admin_panel_is_unlocked') ? warcry_admin_panel_is_unlocked() : true;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Warcry Admin Login</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
  <script>window.jQuery || document.write('<script src="template/js/jquery-1.7.js"><\/script>');</script>
  <script src="login/js/jquery.validate.js"></script>
  <script src="login/js/notifications.js"></script>
  <script src="login/js/js.js"></script>
  <link rel="stylesheet" href="login/css/reset.css">
  <link rel="stylesheet" href="login/css/style.css?v=warcry-pro-secure-2026-05-05">
</head>
<body class="warcry-login">
  <ul id="notifications"></ul>
  <main class="login-shell">
    <section class="login-panel">
      <div class="login-brand">
        <div class="brand-mark"><img src="template/img/logo.png" alt="Warcry CMS"></div>
        <div><h1>Warcry Admin</h1><p>Secure CMS Control Panel</p></div>
      </div>
      <?php if ($error = $ERRORS->DoPrint('login')) { echo '<div class="login-alert">'.$error.'</div>'; unset($error); } ?>
      <?php if (!$panelUnlocked): ?>
      <form name="panel_code" action="execute.php?take=login" method="post" novalidate class="login-form">
        <?php echo function_exists('warcry_csrf_field') ? warcry_csrf_field() : ''; ?>
        <input type="hidden" name="panel_gate" value="1">
        <label>Admin Panel Security Code</label>
        <input type="password" name="admin_panel_code" placeholder="Enter admin panel code" class="required" autocomplete="off" autofocus>
        <button type="submit" id="loginbutton">Unlock Admin Panel</button>
      </form>
      <div class="login-foot">Default code: Admin • Change it in Settings after login</div>
      <?php else: ?>
      <form name="login" action="execute.php?take=login" method="post" novalidate class="login-form">
        <?php echo function_exists('warcry_csrf_field') ? warcry_csrf_field() : ''; ?>
        <label>Username</label><input type="text" name="username" placeholder="Enter username" class="required" autocomplete="username">
        <label>Password</label><input type="password" name="password" placeholder="Enter password" class="required" autocomplete="current-password">
        <button type="submit" id="loginbutton">Login</button>
      </form>
      <div class="login-foot">Warcry CMS • Professional Admin UI</div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
