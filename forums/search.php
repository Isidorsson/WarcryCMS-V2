<?php
// Warcry CMS compatibility route.
// Keeps /forums/search.php working while loading the normal forum system from the site root.
$_GET['page'] = isset($_GET['page']) && $_GET['page'] !== '' ? $_GET['page'] : 'home';
require_once dirname(__DIR__) . '/forums.php';
