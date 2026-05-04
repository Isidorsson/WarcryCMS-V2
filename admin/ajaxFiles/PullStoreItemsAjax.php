<?php
if (!defined('init_ajax'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

require_once $config['RootPath'] . '/engine/server_modules/trinity/world_item_helper.php';
warcry_store_ensure_schema();

if (!$CURUSER->isOnline())
{
    echo json_encode(array('error' => 'You must be logged in.'));
    die;
}

if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_STORE))
{
    echo json_encode(array('error' => 'You dont have the required permissions.'));
    die;
}

function warcry_store_realm_where($realm, &$params)
{
    $realm = (string)$realm;
    if ($realm === '' || $realm === '-1') {
        return '';
    }

    // Compatibility: previous broken patches saved realm as AzerothCore, while the real realm id is 1.
    $names = array($realm);
    if ($realm === '1') {
        $names[] = 'AzerothCore';
    }

    $parts = array();
    foreach ($names as $idx => $value) {
        $key = ':realm_' . $idx;
        $params[$key] = $value;
        $parts[] = "(`realm` = $key OR `realm` = 'all' OR FIND_IN_SET($key, REPLACE(`realm`, ' ', '')) > 0)";
    }
    return '(' . implode(' OR ', $parts) . ')';
}

$columns = array('entry', 'name', 'ItemLevel', 'realm', 'gold', 'silver', 'class', 'subclass', 'id');
$allowedOrder = array('entry', 'name', 'ItemLevel', 'realm', 'gold', 'silver', 'class', 'subclass', 'id');

$realm = isset($_GET['realm']) ? $_GET['realm'] : '-1';
$params = array();
$whereParts = array();

$realmWhere = warcry_store_realm_where($realm, $params);
if ($realmWhere !== '') {
    $whereParts[] = $realmWhere;
}

$search = isset($_GET['sSearch']) ? trim($_GET['sSearch']) : '';
if ($search !== '') {
    $whereParts[] = '(`entry` LIKE :search OR `name` LIKE :search OR `realm` LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereSql = count($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$limit = '';
if (isset($_GET['iDisplayStart']) && isset($_GET['iDisplayLength']) && $_GET['iDisplayLength'] != '-1') {
    $limit = ' LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
}

$order = ' ORDER BY `id` DESC';
if (isset($_GET['iSortCol_0'])) {
    $sortIndex = intval($_GET['iSortCol_0']);
    if (isset($columns[$sortIndex]) && in_array($columns[$sortIndex], $allowedOrder, true)) {
        $dir = (isset($_GET['sSortDir_0']) && strtolower($_GET['sSortDir_0']) === 'asc') ? 'ASC' : 'DESC';
        $order = ' ORDER BY `' . $columns[$sortIndex] . '` ' . $dir;
    }
}

$total = (int)$DB->query('SELECT COUNT(`id`) FROM `store_items`')->fetchColumn();

$countStmt = $DB->prepare('SELECT COUNT(`id`) FROM `store_items` ' . $whereSql);
foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v, PDO::PARAM_STR);
}
$countStmt->execute();
$filtered = (int)$countStmt->fetchColumn();

$stmt = $DB->prepare('SELECT `id`, `entry`, `name`, `ItemLevel`, `realm`, `gold`, `silver`, `class`, `subclass`, `Quality`, `displayid`, `icon` FROM `store_items` ' . $whereSql . $order . $limit);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->execute();

$output = array(
    'sEcho' => isset($_GET['sEcho']) ? intval($_GET['sEcho']) : 0,
    'iTotalRecords' => $total,
    'iTotalDisplayRecords' => $filtered,
    'aaData' => array()
);

while ($row = $stmt->fetch()) {
    $quality = isset($row['Quality']) ? (int)$row['Quality'] : 1;
    $className = function_exists('Item_FindClass') ? Item_FindClass($row['class']) : 'Class';
    $subName = function_exists('Item_FindSubclass') ? Item_FindSubclass($row['class'], $row['subclass']) : 'Subclass';

    $output['aaData'][] = array(
        (int)$row['entry'],
        warcry_item_icon_html($row['entry'], $row['displayid'], isset($row['icon']) ? $row['icon'] : '') . '<a href="https://www.wowhead.com/wotlk/item=' . (int)$row['entry'] . '" class="q' . $quality . ' item-link" rel="item=' . (int)$row['entry'] . '" target="_blank">' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</a>' . ((int)$row['displayid'] === 0 ? ' <span style="color:#f59e0b;">displayid:0</span>' : ''),
        (int)$row['ItemLevel'],
        htmlspecialchars($row['realm'], ENT_QUOTES, 'UTF-8'),
        (int)$row['gold'],
        (int)$row['silver'],
        htmlspecialchars($className, ENT_QUOTES, 'UTF-8') . ' [' . (int)$row['class'] . ']',
        htmlspecialchars($subName, ENT_QUOTES, 'UTF-8') . ' [' . (int)$row['subclass'] . ']',
        '<span class="button-group"><a href="#" onclick="return ConstructEdit(' . (int)$row['id'] . ');" class="button icon edit">Edit</a><a href="#" onclick="return DeleteItem(this, ' . (int)$row['id'] . ');" class="button icon remove danger">Remove</a></span>'
    );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($output);
exit;
