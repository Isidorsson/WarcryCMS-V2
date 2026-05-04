<?PHP
if (!defined('init_ajax')) { header('HTTP/1.0 404 not found'); exit; }
require_once $config['RootPath'] . '/engine/server_modules/trinity/world_item_helper.php';
warcry_store_ensure_icon_column();
$RealmId = $CURUSER->GetRealm();
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$perPage = isset($_GET['perPage']) ? max(1,(int)$_GET['perPage']) : 6;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$quality = isset($_GET['quality']) ? $_GET['quality'] : '-1';
$offset = ($page - 1) * $perPage;
$where = 'WHERE '.warcry_realm_match_sql('`realm`');
$params = array();
if ($search !== '') { $where .= ' AND `name` LIKE :search'; $params[':search'] = '%'.$search.'%'; }
if ($quality !== '-1' && $quality !== '') { $where .= ' AND `Quality` = :quality'; $params[':quality'] = (int)$quality; }
$sql = "SELECT id, entry, name, realm, gold, silver, displayid, icon, Quality FROM `store_items` $where ORDER BY entry DESC LIMIT ".(int)$offset.",".(int)$perPage;
$res = $DB->prepare($sql);
warcry_bind_realm_match($res, $RealmId);
foreach ($params as $k=>$v) { $res->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
$res->execute();
if (!headers_sent()) { header('Content-Type: text/xml; charset=UTF-8'); }
echo '<?xml version="1.0" encoding="UTF-8"?><itemlist count="', $res->rowCount(), '">';
$i=1;
while ($arr = $res->fetch(PDO::FETCH_ASSOC)) {
    $icon = warcry_get_store_item_icon($arr);
    if ((!isset($arr['icon']) || $arr['icon'] === '' || $arr['icon'] === 'inv_misc_questionmark') && $icon !== 'inv_misc_questionmark') {
        try { $u = $DB->prepare("UPDATE `store_items` SET `icon`=:icon WHERE `id`=:id LIMIT 1"); $u->execute(array(':icon'=>$icon, ':id'=>(int)$arr['id'])); } catch (Throwable $e) {}
    }
    $safeName = htmlspecialchars($arr['name'], ENT_QUOTES, 'UTF-8');
    $qualityNum = (int)$arr['Quality'];
    echo '<item id="',(int)$arr['id'],'"><entry>',(int)$arr['entry'],'</entry><realm>',htmlspecialchars($arr['realm'], ENT_XML1),'</realm><gold>',(int)$arr['gold'],'</gold><silver>',(int)$arr['silver'],'</silver><icon>',htmlspecialchars($icon, ENT_XML1),'</icon><name><![CDATA[',$arr['name'],']]></name><quality>',$qualityNum,'</quality><order>',$i,'</order><html><![CDATA[';
    echo '<li id="store-item-'.$i.'" class="store-clickable-item" data-item-id="'.(int)$arr['id'].'" data-entry="'.(int)$arr['entry'].'" data-name="'.$safeName.'" data-icon="'.htmlspecialchars($icon, ENT_QUOTES, 'UTF-8').'" data-quality="q'.$qualityNum.'" data-order="'.$i.'"><div id="item-cont" title="Add to cart"><div class="item-ico"><a href="#" class="store-add-to-cart" id="icon" title="Add to cart" style="background-image:url(https://wow.zamimg.com/images/wow/icons/large/'.$icon.'.jpg)"></a></div><div class="item-info"><p><a class="q'.$qualityNum.' store-add-to-cart store-add-name" href="#" title="Add to cart">'.$safeName.'</a></p><span id="info">Item #'.(int)$arr['entry'].'</span><div class="item-price-coins">';
    $goldStr = ((int)$arr['gold'] > 0) ? '<div class="g-coin"></div><span id="store-price-gold">'.(int)$arr['gold'].'</span>' : '';
    $silverStr = ((int)$arr['silver'] > 0) ? '<div class="s-coin"></div><span id="store-price-silver">'.(int)$arr['silver'].'</span>' : '';
    echo $silverStr . ($goldStr && $silverStr ? '<span id="separator">|</span>' : '') . $goldStr;
    echo '</div></div></div></li>'; 
    echo ']]></html></item>';
    $i++;
}
echo '</itemlist>';
exit;
