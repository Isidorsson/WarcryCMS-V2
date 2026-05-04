<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
header('Location: '.(isset($config['BaseURL']) ? $config['BaseURL'] : '').'/index.php?page=media');
exit;
?>
