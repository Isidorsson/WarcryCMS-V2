<?php
if (!defined('init_ajax'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

$status = '0';
$timeout = 0.5;

// Local AzerothCore: do not use the old hard-coded public IP.
// Consider logon online if authserver 3724 is reachable OR the configured worldserver is reachable OR a character is online.
$checks = array(array('127.0.0.1', 3724));
if (isset($realms_config) && is_array($realms_config))
{
    foreach ($realms_config as $realmId => $realm)
    {
        $checks[] = array(isset($realm['address']) ? $realm['address'] : '127.0.0.1', isset($realm['port']) ? (int)$realm['port'] : 8085);
    }
}

foreach ($checks as $check)
{
    $sock = @fsockopen($check[0], $check[1], $errno, $errstr, $timeout);
    if ($sock)
    {
        $status = '1';
        @fclose($sock);
        break;
    }
}

if ($status !== '1')
{
    try
    {
        $CORE->load_ServerModule('realm.stats');
        $stats = new server_RealmStats();
        $stats->setRealm(1);
        $online = $stats->getOnline();
        if ((int)$online['total'] > 0)
        {
            $status = '1';
        }
    }
    catch (Exception $e) {}
}

echo $status;
exit;
