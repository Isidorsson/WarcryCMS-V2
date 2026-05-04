<?php
if (!defined('init_ajax'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

$realm = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$timeout = 0.5;
$status = '0';

if (isset($realms_config[$realm]))
{
    $address = isset($realms_config[$realm]['address']) ? $realms_config[$realm]['address'] : '127.0.0.1';
    $port = isset($realms_config[$realm]['port']) ? (int)$realms_config[$realm]['port'] : 8085;
    $sock = @fsockopen($address, $port, $errno, $errstr, $timeout);
    if ($sock)
    {
        $status = '1';
        @fclose($sock);
    }
    else
    {
        // Fallback for local AzerothCore: if at least one character is online, show realm online.
        try
        {
            $CORE->load_ServerModule('realm.stats');
            $stats = new server_RealmStats();
            $stats->setRealm($realm);
            $online = $stats->getOnline();
            if ((int)$online['total'] > 0)
            {
                $status = '1';
            }
        }
        catch (Exception $e) {}
    }
}

echo $status;
exit;
