<?PHP
if (!defined('init_ajax'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$silver = ((isset($_GET['silver'])) ? (int)$_GET['silver'] : 0);
$gold = ((isset($_GET['gold'])) ? (int)$_GET['gold'] : 0);
$realm = ((isset($_GET['realm'])) ? (int)$_GET['realm'] : false);

if (!$CURUSER->isOnline()) {
    echo 'You must be logged in.';
    exit;
}

// Do not block store purchases with a realm-port ping.
// AzerothCore can still receive items through SOAP or the DB-mail fallback even if the world port is not reachable from PHP/WAMP.
if (!$realm || !isset($realms_config[$realm])) {
    echo 'Website error: Cannot determine the selected realm.';
    exit;
}

if ($silver > 0 and $gold > 0) {
    if ($CURUSER->get('silver') >= $silver and $CURUSER->get('gold') >= $gold) {
        echo 'OK';
    } else {
        $text = 'Not enough money.';
        if ($CURUSER->get('silver') < $silver) { $silverNeeded = $silver - $CURUSER->get('silver'); }
        if ($CURUSER->get('gold') < $gold) { $goldNeeded = $gold - $CURUSER->get('gold'); }
        if (isset($silverNeeded) and isset($goldNeeded)) { $text .= ' You are '. $silverNeeded .' silver and '. $goldNeeded .' gold short.'; }
        else { $text .= isset($silverNeeded) ? ' You are '. $silverNeeded .' silver short.' : ' You are '. $goldNeeded .' gold short.'; }
        echo $text;
    }
} else if ($silver == 0 and $gold > 0) {
    echo ($CURUSER->get('gold') >= $gold) ? 'OK' : 'Not enough money. You are '. ($gold - $CURUSER->get('gold')) .' gold short.';
} else if ($silver > 0 and $gold == 0) {
    echo ($CURUSER->get('silver') >= $silver) ? 'OK' : 'Not enough money. You are '. ($silver - $CURUSER->get('silver')) .' silver short.';
} else {
    echo 'Error: The script has nothing to do.';
}
exit;
