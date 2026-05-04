<?PHP
if (!defined('init_ajax')) { header('HTTP/1.0 404 not found'); exit; }
require_once $config['RootPath'] . '/engine/server_modules/trinity/world_item_helper.php';
$entry = isset($_GET['entry']) ? (int)$_GET['entry'] : 0;
$item = warcry_get_world_item($entry);
header('Content-Type: application/json; charset=utf-8');
if (!$item) { echo 'null'; exit; }
$qualityNames = array(0=>'Poor',1=>'Common',2=>'Uncommon',3=>'Rare',4=>'Epic',5=>'Legendary',6=>'Artifact',7=>'Heirloom');
$className = function_exists('Item_FindClass') ? Item_FindClass((int)$item['class']) : (string)$item['class'];
$subclassName = function_exists('Item_FindSubclass') ? Item_FindSubclass((int)$item['class'], (int)$item['subclass']) : (string)$item['subclass'];
echo json_encode(array(
    'entry' => (int)$item['entry'],
    'name' => $item['name'],
    'quality' => (int)$item['Quality'],
    'Quality' => (int)$item['Quality'],
    'quality_str' => isset($qualityNames[(int)$item['Quality']]) ? $qualityNames[(int)$item['Quality']] : 'Common',
    'class' => (int)$item['class'],
    'subclass' => (int)$item['subclass'],
    'class_str' => $className,
    'subclass_str' => $subclassName,
    'displayid' => (int)$item['displayid'],
    'InventoryType' => (int)$item['InventoryType'],
    'InventoryType_str' => 'Inventory Type '.(int)$item['InventoryType'],
    'ItemLevel' => (int)$item['ItemLevel'],
    'AllowableClass' => (int)$item['AllowableClass'],
    'Flags' => (string)$item['Flags'],
    'description' => (string)$item['description'],
    'bonding_str' => '',
    'icon' => warcry_clean_icon_name($item['icon'])
));
exit;
