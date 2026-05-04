<?PHP
if (!defined('init_ajax'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$RealmId = $CURUSER->GetRealm();
$perPage = ((isset($_GET['perPage'])) ? max(1, (int)$_GET['perPage']) : 6);
$search = (isset($_GET['search']) ? trim($_GET['search']) : '');
$quality = (isset($_GET['quality']) ? $_GET['quality'] : '-1');

function warcry_store_filter_realm_sql($realmId, &$params)
{
    $params[':realm'] = (string)$realmId;
    $sql = "(`realm` = :realm OR `realm` = 'all' OR FIND_IN_SET(:realm, REPLACE(`realm`, ' ', '')) > 0";
    if ((string)$realmId === '1') {
        $params[':realmName'] = 'AzerothCore';
        $sql .= " OR `realm` = :realmName";
    }
    $sql .= ')';
    return $sql;
}

$params = array();
$whereParts = array(warcry_store_filter_realm_sql($RealmId, $params));
if ($search !== '') {
    $whereParts[] = "`name` LIKE :search";
    $params[':search'] = '%' . $search . '%';
}
if ($quality !== '-1' && $quality !== '') {
    $whereParts[] = "`Quality` = :quality";
    $params[':quality'] = (int)$quality;
}
$where = 'WHERE ' . implode(' AND ', $whereParts);

$count_res = $DB->prepare("SELECT COUNT(*) FROM `store_items` $where");
foreach ($params as $k => $v) {
    $count_res->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$count_res->execute();
$count = (int)$count_res->fetchColumn();
$totalPages = ceil($count / $perPage);

if (!headers_sent()) {
    header('Content-Type: text/xml; charset=utf-8');
}

echo '<?xml version="1.0" encoding="UTF-8"?><info><totalPages>', $totalPages, '</totalPages><totalRecords>', $count, '</totalRecords></info>';
exit;
