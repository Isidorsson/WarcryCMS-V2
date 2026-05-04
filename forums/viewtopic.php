<?php
// Warcry CMS compatibility route for old forum URLs.
$_GET['page'] = 'topic';
if (isset($_GET['t']) && !isset($_GET['id'])) {
    $_GET['id'] = $_GET['t'];
}
require_once dirname(__DIR__) . '/forums.php';
