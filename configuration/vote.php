<?php
if (!defined('init_config'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

//The cooldown to be applied on each voting site
$config['VOTE']['Cooldown'] = '12 hours';
//Points per Vote
$config['VOTE']['PPV'] = 2;
//Lets people recieve points per IP only
$config['VOTE']['IP_CHECK'] = true;
