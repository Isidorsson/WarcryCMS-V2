<?PHP
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
require_once $config['RootPath'] . '/engine/server_modules/trinity/world_item_helper.php';
warcry_store_ensure_icon_column();
$RealmID = isset($_GET['realm']) ? $_GET['realm'] : '-1';
$_SESSION['ADMIN_SelectedRealmS'] = $RealmID;
if ($RealmID != '-1') { $_SESSION['ADMIN_SelectedRealmS'] = isset($realms_config[(int)$RealmID]) ? (string)(int)$RealmID : '-1'; }
$RealmID = $_SESSION['ADMIN_SelectedRealmS'];
if ($error = $ERRORS->DoPrint(array('edit_storeitem'))) { echo $error; }
if ($success = $ERRORS->successPrint(array('edit_storeitem', 'add_storeitem'))) { echo $success; }
?>
<nav id="secondary" class="disable-tabbing"><ul><li class="current"><a href="index.php?page=store">Item Store</a></li><li><a href="index.php?page=store-add">Add new item</a></li></ul></nav>
<?php if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_STORE)) { $CORE->ErrorBox('You do not have the required permissions.'); } ?>
<section id="content"><div class="tab" id="maintab"><h2>Item Store Management</h2><br />
<div style="width:200px;display:inline-block;vertical-align:middle;margin-right:10px;"><select name="realm" id="realm-select"><option value="-1">All Realms</option><?php foreach ($realms_config as $id => $realmData) { echo '<option value="',$id,'" ',((string)$id===(string)$RealmID?'selected="selected"':''),'>',htmlspecialchars($realmData['name'], ENT_QUOTES, 'UTF-8'),'</option>'; } ?></select></div>
<script>$(function(){ $('#realm-select').on('change', function(){ window.location='index.php?page=store&realm='+$(this).val(); }); });</script>
<br /><br />
<style>.item-icon-img{width:32px;height:32px;border-radius:5px;border:1px solid rgba(212,175,55,.45);vertical-align:middle;margin-right:8px}.datatable td{vertical-align:middle}.muted{opacity:.7;font-size:12px}</style>
<table class="datatable" id="datatable"><thead><tr><th>Entry</th><th>Name</th><th>Item Level</th><th>Realms</th><th>Price Gold</th><th>Price Silver</th><th>Class</th><th>Subclass</th><th>Actions</th></tr></thead><tbody>
<?php
$params = array();
$sql = "SELECT `id`,`entry`,`name`,`ItemLevel`,`realm`,`gold`,`silver`,`class`,`subclass`,`displayid`,`icon`,`Quality` FROM `store_items`";
if ($RealmID !== '-1') {
    $sql .= " WHERE ".warcry_realm_match_sql('`realm`');
}
$sql .= " ORDER BY `id` DESC";
$stmt = $DB->prepare($sql);
if ($RealmID !== '-1') { warcry_bind_realm_match($stmt, $RealmID); }
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '<tr>';
    echo '<td>',(int)$row['entry'],'</td>';
    echo '<td>',warcry_item_icon_html($row['entry'], $row['displayid'], isset($row['icon']) ? $row['icon'] : ''),'<a class="q',(int)$row['Quality'],'" href="https://www.wowhead.com/wotlk/item=',(int)$row['entry'],'" rel="item=',(int)$row['entry'],'" target="_blank">',htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),'</a><div class="muted">displayid: ',(int)$row['displayid'],' · icon: ',htmlspecialchars(isset($row['icon']) ? $row['icon'] : '', ENT_QUOTES, 'UTF-8'),'</div></td>';
    echo '<td>',(int)$row['ItemLevel'],'</td><td>',htmlspecialchars($row['realm'], ENT_QUOTES, 'UTF-8'),'</td><td>',(int)$row['gold'],'</td><td>',(int)$row['silver'],'</td>';
    echo '<td>',htmlspecialchars(Item_FindClass($row['class']), ENT_QUOTES, 'UTF-8'),' [',(int)$row['class'],']</td>';
    echo '<td>',htmlspecialchars(Item_FindSubclass($row['class'], $row['subclass']), ENT_QUOTES, 'UTF-8'),' [',(int)$row['subclass'],']</td>';
    echo '<td><span class="button-group"><a href="#" onclick="return ConstructEdit('.(int)$row['id'].');" class="button icon edit">Edit</a> <a href="#" onclick="return DeleteItem(this,'.(int)$row['id'].');" class="button icon remove danger">Remove</a></span></td>';
    echo '</tr>';
}
?>
</tbody></table></div></section>
<script src="template/js/forms.js" type="text/javascript"></script><script src="template/js/jquery.form.js" type="text/javascript"></script><script src="template/js/jquery-ui-1.10.0.sortable.min.js" type="text/javascript"></script>
<script>var $configURL='<?php echo $config['BaseURL']; ?>';
function DeleteItem(e,id){var TR=$(e).closest('tr'); if(!confirm('Are you sure you want to delete this item?')) return false; $.get('ajax.php?phase=18',{id:id},function(data){ if(data=='OK'){TR.fadeOut('slow'); new Notification('The item was successfully deleted.','success');} else {new Notification(data,'error','urgent');}}); return false;}
function ConstructEdit(id){alert('Edit popup kept from old CMS. For now remove/re-add the item to refresh item_template data.'); return false;}</script>
<script>var wowhead_tooltips={"colorlinks":true,"iconizelinks":false,"renamelinks":false};</script><script src="https://wow.zamimg.com/js/tooltips.js"></script>
