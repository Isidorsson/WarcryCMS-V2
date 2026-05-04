<?PHP
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
$CORE->loggedInOrReturn();
$CORE->CheckPermissionsExecute(PERMISSION_STORE);
require_once $config['RootPath'] . '/engine/server_modules/trinity/world_item_helper.php';
warcry_store_ensure_icon_column();

$ERRORS->NewInstance('add_storeitem');
$ERRORS->onSuccess('The item was successfully added.', '/index.php?page=store');

$entry = isset($_POST['entry']) ? (int)$_POST['entry'] : 0;
$realm = isset($_POST['realm']) && trim($_POST['realm']) !== '' ? trim($_POST['realm']) : '1';
$gold = isset($_POST['gold']) ? (int)$_POST['gold'] : 0;
$silver = isset($_POST['silver']) ? (int)$_POST['silver'] : 0;

if ($entry <= 0) { $ERRORS->Add('Please enter a valid item entry.'); }
if ($realm === '') { $ERRORS->Add('Please enter the realm id. Example: 1'); }
$ERRORS->Check('/index.php?page=store-add');

$item = warcry_get_world_item($entry);
if (!$item) {
    $ERRORS->Add('Item entry '.$entry.' was not found in world.item_template. Check your world database connection and item id.');
    $ERRORS->Check('/index.php?page=store-add');
}

$name = $item['name'];
$class = (int)$item['class'];
$subclass = (int)$item['subclass'];
$itemlevel = (int)$item['ItemLevel'];
$quality = (int)$item['Quality'];
$type = (int)$item['InventoryType'];
$displayid = (int)$item['displayid'];
$flags = (string)$item['Flags'];
$allowable = (int)$item['AllowableClass'];
$description = (string)$item['description'];
$icon = warcry_clean_icon_name(isset($item['icon']) ? $item['icon'] : (isset($_POST['icon']) ? $_POST['icon'] : 'inv_misc_questionmark'));

$insert = $DB->prepare("INSERT INTO `store_items` (`entry`, `realm`, `name`, `gold`, `silver`, `class`, `subclass`, `ItemLevel`, `Quality`, `InventoryType`, `displayid`, `icon`, `Flags`, `AllowableClass`, `description`) VALUES (:entry, :realm, :name, :gold, :silver, :class, :subclass, :itemlevel, :quality, :invtype, :displayid, :icon, :flags, :allowable, :description)");
$ok = $insert->execute(array(
    ':entry' => $entry,
    ':realm' => $realm,
    ':name' => $name,
    ':gold' => $gold,
    ':silver' => $silver,
    ':class' => $class,
    ':subclass' => $subclass,
    ':itemlevel' => $itemlevel,
    ':quality' => $quality,
    ':invtype' => $type,
    ':displayid' => $displayid,
    ':icon' => $icon,
    ':flags' => $flags,
    ':allowable' => $allowable,
    ':description' => $description
));
if (!$ok || $insert->rowCount() < 1) { $ERRORS->Add('The website failed to insert the store item record.'); }
else { $ERRORS->triggerSuccess(); }
$ERRORS->Check('/index.php?page=store-add');
exit;
