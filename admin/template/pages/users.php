<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_PREV_USERS)) { $CORE->ErrorBox('You do not have the required permissions.'); }
function wh($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$params = array();
$where = '';
if ($search !== '') {
    $where = "WHERE username LIKE :q OR email LIKE :q";
    $params[':q'] = '%'.$search.'%';
}

// IMPORTANT: Auth users come from AUTH DB only. CMS profiles come from CMS DB only.
// Do not join account_data on AUTH DB, because AzerothCore/characters also has account_data tables.
$sql = "SELECT id, username, email, joindate, last_ip, last_login, online, expansion FROM `account` $where ORDER BY id DESC LIMIT 250";
$stmt = $AUTH_DB->prepare($sql);
foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_STR); }
$stmt->execute();
$authRows = $stmt->fetchAll();

$rows = array();
foreach ($authRows as $a) {
    $cmsStmt = $DB->prepare("SELECT `id`, `displayName`, `rank` AS `cms_rank`, `status` AS `cms_status`, `reg_ip`, `last_ip`, `selected_realm` FROM `account_data` WHERE `id` = :id LIMIT 1");
    $cmsStmt->execute(array(':id' => $a['id']));
    $cms = $cmsStmt->fetch();

    if (!$cms) {
        $ins = $DB->prepare("INSERT IGNORE INTO `account_data`
            (`id`,`displayName`,`silver`,`gold`,`cooldowns`,`socialData`,`birthday`,`gender`,`country`,`secretQuestion`,`secretAnswer`,`avatar`,`avatarType`,`rank`,`last_ip`,`admin_last_ip`,`reg_ip`,`last_login`,`last_login2`,`admin_last_login`,`admin_last_login2`,`status`,`event`,`salt`,`selected_realm`,`bt_milestone`)
            VALUES (:id,:displayName,0,0,'','','','', 'US',0,'','',0,0,:ip,'0.0.0.0',:ip,NOW(),NOW(),NOW(),NOW(),'active','NONE',SHA1(CONCAT(RAND(),NOW(),:saltName)),1,0)");
        $ins->execute(array(
            ':id' => $a['id'],
            ':displayName' => $a['username'],
            ':saltName' => $a['username'],
            ':ip' => !empty($a['last_ip']) ? $a['last_ip'] : '0.0.0.0'
        ));
        $cms = array('id'=>$a['id'], 'displayName'=>$a['username'], 'cms_rank'=>0, 'cms_status'=>'active', 'reg_ip'=>$a['last_ip'], 'last_ip'=>$a['last_ip'], 'selected_realm'=>1);
    }

    $gmAccess = array();
    try {
        $gm = $AUTH_DB->prepare("SELECT gmlevel, RealmID FROM `account_access` WHERE `id` = :id ORDER BY RealmID ASC");
        $gm->execute(array(':id' => $a['id']));
        foreach ($gm->fetchAll() as $g) { $gmAccess[] = ((int)$g['gmlevel']).'@'.((int)$g['RealmID']); }
    } catch (Exception $e) {}

    $rows[] = array_merge($a, array(
        'displayName' => $cms['displayName'],
        'rank' => (int)$cms['cms_rank'],
        'status' => $cms['cms_status'],
        'reg_ip' => $cms['reg_ip'],
        'selected_realm' => $cms['selected_realm'],
        'gm_access' => implode(', ', $gmAccess)
    ));
}
?>
<nav id="secondary" class="disable-tabbing"><ul><li class="current"><a href="#maintab">Users</a></li></ul></nav>
<section id="content"><div class="tab" id="maintab">
  <h2>User Management</h2>
  <div class="notice">Users are loaded from <strong>auth.account</strong>. CMS profiles are loaded/created in <strong>warcry.account_data</strong>. This prevents the wrong <code>auth.account_data</code> lookup.</div>
  <form method="get" class="admin-card" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="page" value="users">
    <input type="text" name="q" value="<?php echo wh($search); ?>" placeholder="Search username or email" style="min-width:260px">
    <button class="button primary" type="submit">Search</button>
    <a class="button" href="index.php?page=users">Reset</a>
  </form>
  <table class="datatable">
    <thead><tr><th>ID</th><th>Account</th><th>CMS Display</th><th>Rank</th><th>GM Access</th><th>Email</th><th>IP</th><th>Join Date</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $rank = class_exists('UserRank') ? new UserRank((int)$r['rank']) : null; ?>
      <tr>
        <td><?php echo (int)$r['id']; ?></td>
        <td><strong><?php echo wh($r['username']); ?></strong></td>
        <td><?php echo wh($r['displayName']); ?></td>
        <td><span class="pill gold"><?php echo $rank ? wh($rank->string()).' ['.(int)$rank->int().']' : (int)$r['rank']; ?></span></td>
        <td><?php echo $r['gm_access'] ? wh($r['gm_access']) : '<span class="pill">Player</span>'; ?></td>
        <td><?php echo wh($r['email']); ?></td>
        <td><?php echo wh($r['reg_ip'] ?: $r['last_ip']); ?></td>
        <td><?php echo wh($r['joindate']); ?></td>
        <td><span class="pill <?php echo $r['status']==='active'?'green':'red'; ?>"><?php echo wh($r['status']); ?></span></td>
      </tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9">No users found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
