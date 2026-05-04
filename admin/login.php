<?php
include_once 'engine/initialize.php';
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
  <link rel="stylesheet" href="login/css/style.css?v=warcry2026">
</head>
<body class="warcry-login">
  <ul id="notifications"></ul>
  <main class="login-shell">
    <section class="login-panel">
      <div class="login-brand">
        <div class="brand-mark">W</div>
        <div>
          <h1>Warcry CMS</h1>
          <p>Secure Admin Panel</p>
        </div>
      </div>
      <?php
      if ($error = $ERRORS->DoPrint('login')) {
        echo '<div class="login-alert">'.$error.'</div>';
        unset($error);
      }
      ?>
      <form name="login" action="execute.php?take=login" method="post" novalidate class="login-form">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter username" class="required" autocomplete="username">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password" class="required" autocomplete="current-password">
        <button type="submit" id="loginbutton">Login</button>
      </form>
      <div class="login-foot">Warcry Admin • Clean 2026 UI</div>
    </section>
  </main>
</body>
</html>
