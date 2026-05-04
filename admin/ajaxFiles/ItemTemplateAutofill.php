<?PHP
if (!defined('init_ajax')) { header('HTTP/1.0 404 not found'); exit; }
require_once $config['RootPath'] . '/engine/server_modules/trinity/world_item_helper.php';
header('Content-Type: application/json; charset=utf-8');
if (!$CURUSER->isOnline() || !$CURUSER->getPermissions()->isAllowed(PERMISSION_STORE)) {
    echo json_encode(array('error' => 'Permission denied.'));
    exit;
}
$entry = isset($_GET['entry']) ? (int)$_GET['entry'] : 0;
$item = warcry_get_world_item($entry);
if (!$item) { echo 'null'; exit; }
echo json_encode(array(
    'entry' => (int)$item['entry'],
    'name' => $item['name'],
    'quality' => (int)$item['Quality'],
    'Quality' => (int)$item['Quality'],
    'class' => (int)$item['class'],
    'subclass' => (int)$item['subclass'],
    'displayid' => (int)$item['displayid'],
    'InventoryType' => (int)$item['InventoryType'],
    'ItemLevel' => (int)$item['ItemLevel'],
    'AllowableClass' => (int)$item['AllowableClass'],
    'Flags' => (string)$item['Flags'],
    'description' => (string)$item['description'],
    'icon' => warcry_clean_icon_name($item['icon'])
));
exit;
