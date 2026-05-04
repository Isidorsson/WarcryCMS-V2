<?php
// Warcry CMS compatibility route for old forum URLs.
$_GET['page'] = 'forum';
if (isset($_GET['f']) && !isset($_GET['id'])) {
    $_GET['id'] = $_GET['f'];
}
require_once dirname(__DIR__) . '/forums.php';
