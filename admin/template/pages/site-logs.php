<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
function hal($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function warcry_table_exists($table) { global $DB; try { if(!preg_match('/^[a-zA-Z0-9_]+$/',$table)) return false; $s=$DB->query("SHOW TABLES LIKE ".$DB->quote($table)); return ($s && $s->rowCount() > 0); } catch(Exception $e) { return false; } }
function warcry_log_user($id) { global $DB; $id=(int)$id; if ($id <= 0) return '-'; try { $s=$DB->prepare("SELECT `displayName` FROM `account_data` WHERE `id`=:id LIMIT 1"); $s->execute(array(':id'=>$id)); $r=$s->fetch(); return $r ? '<a href="index.php?page=user-preview&uid='.$id.'">'.hal($r['displayName']).'</a> ['.$id.']' : $id; } catch(Exception $e) { return $id; } }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_LOGS)) { $CORE->ErrorBox('You do not have the required permissions.'); }
$filter = isset($_GET['type']) ? preg_replace('/[^a-z0-9_\-]/i','',$_GET['type']) : 'all';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = 150;
$items = array();
$add = function($type,$time,$account,$title,$text,$status,$ref='') use (&$items,$q,$filter) {
    if ($filter != 'all' && $filter != $type) return;
    $hay = strtolower($type.' '.$title.' '.$text.' '.$account.' '.$ref.' '.$status);
    if ($q !== '' && strpos($hay, strtolower($q)) === false) return;
    $items[] = array('type'=>$type,'time'=>$time,'account'=>$account,'title'=>$title,'text'=>$text,'status'=>$status,'ref'=>$ref);
};
try {
if (warcry_table_exists('account_data')) { $r=$DB->query("SELECT `id`,`displayName`,`reg_ip`,`last_ip`,`last_login`,`status` FROM `account_data` ORDER BY `id` DESC LIMIT 200"); while($x=$r->fetch()){ $add('account','', $x['id'], 'Account created / profile', 'Display name: '.$x['displayName'].' | Status: '.$x['status'].' | Reg IP: '.$x['reg_ip'].' | Last IP: '.$x['last_ip'].' | Last login: '.$x['last_login'], $x['status'], 'account#'.$x['id']); } }
if (warcry_table_exists('purchase_log')) { $r=$DB->query("SELECT * FROM `purchase_log` ORDER BY `id` DESC LIMIT 300"); while($x=$r->fetch()){ $add('purchase',$x['time'],$x['account'],$x['source'],$x['text'],$x['status'],'purchase_log#'.$x['id']); } }
if (warcry_table_exists('store_activity')) { $r=$DB->query("SELECT * FROM `store_activity` ORDER BY `id` DESC LIMIT 300"); while($x=$r->fetch()){ $add('shop',$x['time'],$x['account'],$x['source'],$x['text'].' | Item: '.$x['itemId'].' | Money: '.$x['money'],'ok','store_activity#'.$x['id']); } }
if (warcry_table_exists('paypal_logs')) { $r=$DB->query("SELECT * FROM `paypal_logs` ORDER BY `id` DESC LIMIT 200"); while($x=$r->fetch()){ $add('paypal',$x['time'],$x['account'],'PayPal '.$x['paypal_status'],$x['text'].' | TXN: '.$x['txn_id'].' | Amount: '.$x['amount'].' | Payer: '.$x['payer_email'], $x['paypal_status'], 'paypal#'.$x['id']); } }
if (warcry_table_exists('paymentwall_logs')) { $r=$DB->query("SELECT * FROM `paymentwall_logs` ORDER BY `id` DESC LIMIT 200"); while($x=$r->fetch(PDO::FETCH_ASSOC)){ $add('paymentwall',isset($x['time'])?$x['time']:'',isset($x['account'])?$x['account']:0,'Paymentwall',implode(' | ', array_map('strval',$x)),'info','paymentwall#'.(isset($x['id'])?$x['id']:'')); } }
if (warcry_table_exists('articles')) { $r=$DB->query("SELECT `id`,`title`,`author`,`added`,`views` FROM `articles` ORDER BY `id` DESC LIMIT 200"); while($x=$r->fetch()){ $add('article',$x['added'],$x['author'],'Article posted',$x['title'].' | Views: '.$x['views'],'ok','article#'.$x['id']); } }
if (warcry_table_exists('article_comments')) { $r=$DB->query("SELECT `id`,`text`,`author`,`added`,`article` FROM `article_comments` ORDER BY `id` DESC LIMIT 200"); while($x=$r->fetch()){ $add('comment',$x['added'],$x['author'],'Article comment','Article #'.$x['article'].' | '.$x['text'],'ok','comment#'.$x['id']); } }
if (warcry_table_exists('wcf_topics')) { $r=$DB->query("SELECT `id`,`name`,`author`,`added`,`forum`,`posts` FROM `wcf_topics` ORDER BY `id` DESC LIMIT 200"); while($x=$r->fetch()){ $add('forum_topic',$x['added'],$x['author'],'Forum topic',$x['name'].' | Forum: '.$x['forum'].' | Posts: '.$x['posts'],'ok','topic#'.$x['id']); } }
if (warcry_table_exists('wcf_posts')) { $r=$DB->query("SELECT `id`,`title`,`text`,`author`,`added`,`topic`,`deleted_by`,`deleted_time` FROM `wcf_posts` ORDER BY `id` DESC LIMIT 200"); while($x=$r->fetch()){ $add('forum_post',$x['added'],$x['author'],'Forum post',$x['title'].' | Topic: '.$x['topic'].' | '.$x['text'].($x['deleted_by']>0?' | Deleted by '.$x['deleted_by'].' at '.$x['deleted_time']:''), $x['deleted_by']>0?'deleted':'ok','post#'.$x['id']); } }
if (warcry_table_exists('bugtracker')) { $r=$DB->query("SELECT `id`,`title`,`account`,`added`,`status`,`priority`,`approval` FROM `bugtracker` ORDER BY `id` DESC LIMIT 200"); while($x=$r->fetch()){ $add('bugtracker',$x['added'],$x['account'],'Bug report',$x['title'].' | Status: '.$x['status'].' | Priority: '.$x['priority'].' | Approval: '.$x['approval'],'info','bug#'.$x['id']); } }
} catch(Exception $e) { echo '<div class="notice">Some logs could not be loaded: '.hal($e->getMessage()).'</div>'; }
usort($items, function($a,$b){ return strcmp($b['time'], $a['time']); });
$items = array_slice($items, 0, $limit);
$types = array('all'=>'All','account'=>'Accounts','purchase'=>'P.Store','shop'=>'Shop','paypal'=>'PayPal','paymentwall'=>'Paymentwall','article'=>'Articles','comment'=>'Comments','forum_topic'=>'Forum Topics','forum_post'=>'Forum Posts','bugtracker'=>'Bugtracker');
?>
<nav id="secondary" class="disable-tabbing"><ul><li class="current"><a href="index.php?page=site-logs">All Site Logs</a></li><li><a href="index.php?page=logs">Old Payment Logs</a></li></ul></nav>
<section id="content"><div class="tab" id="maintab"><h2>All Site Logs</h2><div class="notice">Central view for existing website activity: purchases, shop, accounts, articles, comments, forum posts, reports and payment logs.</div>
<form method="get" class="form pro-form" style="margin-bottom:15px;"><input type="hidden" name="page" value="site-logs"><section><label>Filter</label><div class="field-inline"><select name="type"><?php foreach($types as $k=>$v): ?><option value="<?php echo hal($k); ?>"<?php echo $filter==$k?' selected':''; ?>><?php echo hal($v); ?></option><?php endforeach; ?></select><input type="text" name="q" value="<?php echo hal($q); ?>" placeholder="Search logs..."><button type="submit" class="button primary">Search</button></div></section></form>
<table class="datatable"><thead><tr><th>Time</th><th>Type</th><th>Status</th><th>Account</th><th>Activity</th><th>Reference</th></tr></thead><tbody>
<?php if (empty($items)): ?><tr><td colspan="6">No logs found.</td></tr><?php endif; ?>
<?php foreach($items as $it): ?><tr><td><?php echo hal($it['time']); ?></td><td><?php echo hal($it['type']); ?></td><td><?php echo hal($it['status']); ?></td><td><?php echo warcry_log_user($it['account']); ?></td><td><strong><?php echo hal($it['title']); ?></strong><br><?php echo nl2br(hal(substr($it['text'],0,500))); ?></td><td><?php echo hal($it['ref']); ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
