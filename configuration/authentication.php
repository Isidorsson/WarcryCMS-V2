<?php
if (!defined('init_config'))
{
	header('HTTP/1.0 404 not found');
	exit;
}

$auth_config['DatabaseHost'] = 'localhost';
$auth_config['DatabaseUser'] = 'Ghost';
$auth_config['DatabasePass'] = 'ascent';
$auth_config['DatabaseName'] = 'auth';
$auth_config['DatabaseEncoding'] = 'utf8';
