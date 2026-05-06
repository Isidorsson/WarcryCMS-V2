<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_PREV_USERS)) { $CORE->ErrorBox('You do not have the required permissions.'); }

function wa_u($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function wa_money($copper){ $copper=(int)$copper; $g=floor($copper/10000); $s=floor(($copper%10000)/100); $c=$copper%100; return $g.'g '.$s.'s '.$c.'c'; }
function wa_status_class($v){ return $v === 'active' ? 'green' : ($v === 'pending' ? 'gold' : 'red'); }
function wa_class_name($id){ $m=array(1=>'Warrior',2=>'Paladin',3=>'Hunter',4=>'Rogue',5=>'Priest',6=>'Death Knight',7=>'Shaman',8=>'Mage',9=>'Warlock',11=>'Druid'); return isset($m[(int)$id])?$m[(int)$id]:'Class '.(int)$id; }
function wa_race_name($id){ $m=array(1=>'Human',2=>'Orc',3=>'Dwarf',4=>'Night Elf',5=>'Undead',6=>'Tauren',7=>'Gnome',8=>'Troll',10=>'Blood Elf',11=>'Draenei'); return isset($m[(int)$id])?$m[(int)$id]:'Race '.(int)$id; }
function wa_col_exists($pdo,$table,$col){ try { if(!preg_match('/^[A-Za-z0-9_]+$/',$table) || !preg_match('/^[A-Za-z0-9_]+$/',$col)) return false; $sql="SHOW COLUMNS FROM `".$table."` LIKE ".$pdo->quote($col); $q=$pdo->query($sql); return ($q && $q->rowCount()>0); } catch(Exception $e){ return false; } }

$view = isset($_GET['view']) ? $_GET['view'] : 'list';
$account = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$realm = isset($_GET['realm']) ? (int)$_GET['realm'] : 1;
if (!isset($realms_config[$realm])) { $realm = 1; }

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$params = array();
$where = '';
if ($search !== '') {
    $where = "WHERE username LIKE :q OR email LIKE :q OR id = :id";
    $params[':q'] = '%'.$search.'%';
}

$rows = array();
if ($view === 'list') {
    $sql = "SELECT id, username, email, joindate, last_ip, last_login, online, expansion, locked, mutetime FROM `account` $where ORDER BY id DESC LIMIT 300";
    $stmt = $AUTH_DB->prepare($sql);
    if ($search !== '') { $stmt->bindValue(':q', '%'.$search.'%', PDO::PARAM_STR); $stmt->bindValue(':id', (int)$search, PDO::PARAM_INT); }
    $stmt->execute();
    $authRows = $stmt->fetchAll();
    foreach ($authRows as $a) {
        $cmsStmt = $DB->prepare("SELECT `id`, `displayName`, `silver`, `gold`, `rank` AS `cms_rank`, `status` AS `cms_status`, `reg_ip`, `last_ip`, `selected_realm` FROM `account_data` WHERE `id` = :id LIMIT 1");
        $cmsStmt->execute(array(':id' => $a['id']));
        $cms = $cmsStmt->fetch();
        if (!$cms) {
            $ins = $DB->prepare("INSERT IGNORE INTO `account_data`
                (`id`,`displayName`,`silver`,`gold`,`cooldowns`,`socialData`,`birthday`,`gender`,`country`,`secretQuestion`,`secretAnswer`,`avatar`,`avatarType`,`rank`,`last_ip`,`admin_last_ip`,`reg_ip`,`last_login`,`last_login2`,`admin_last_login`,`admin_last_login2`,`status`,`event`,`salt`,`selected_realm`,`bt_milestone`)
                VALUES (:id,:displayName,0,0,'','','','', 'US',0,'','',0,0,:lastIp,'0.0.0.0',:regIp,NOW(),NOW(),NOW(),NOW(),'active','NONE',SHA1(CONCAT(RAND(),NOW(),:saltName)),1,0)");
            $ins->execute(array(':id'=>$a['id'], ':displayName'=>$a['username'], ':saltName'=>$a['username'], ':lastIp'=>!empty($a['last_ip'])?$a['last_ip']:'0.0.0.0', ':regIp'=>!empty($a['last_ip'])?$a['last_ip']:'0.0.0.0'));
            $cms = array('id'=>$a['id'], 'displayName'=>$a['username'], 'silver'=>0, 'gold'=>0, 'cms_rank'=>0, 'cms_status'=>'active', 'reg_ip'=>$a['last_ip'], 'last_ip'=>$a['last_ip'], 'selected_realm'=>1);
        }
        $gmAccess = array();
        try { $gm=$AUTH_DB->prepare("SELECT gmlevel, RealmID FROM `account_access` WHERE `id`=:id ORDER BY RealmID ASC"); $gm->execute(array(':id'=>$a['id'])); foreach($gm->fetchAll() as $g){ $gmAccess[]=(int)$g['gmlevel'].'@'.(int)$g['RealmID']; } } catch(Exception $e) {}
        $charCount = 0;
        try { $rdb=$CORE->RealmDatabaseConnection((int)$cms['selected_realm']); $cq=$rdb->prepare("SELECT COUNT(*) FROM `characters` WHERE `account`=:id"); $cq->execute(array(':id'=>$a['id'])); $charCount=(int)$cq->fetchColumn(); } catch(Exception $e) {}
        $rows[] = array_merge($a, array('displayName'=>$cms['displayName'], 'silver'=>$cms['silver'], 'gold'=>$cms['gold'], 'rank'=>(int)$cms['cms_rank'], 'status'=>$cms['cms_status'], 'reg_ip'=>$cms['reg_ip'], 'selected_realm'=>$cms['selected_realm'], 'gm_access'=>implode(', ', $gmAccess), 'char_count'=>$charCount));
    }
}

$webRecord = false; $authRecord = false; $characters = array();
if ($view === 'manage' && $account > 0) {
    $webRes = $DB->prepare("SELECT * FROM `account_data` WHERE `id` = :id LIMIT 1");
    $webRes->execute(array(':id'=>$account));
    $webRecord = $webRes->fetch();
    $authRes = $AUTH_DB->prepare("SELECT * FROM `account` WHERE `id` = :id LIMIT 1");
    $authRes->execute(array(':id'=>$account));
    $authRecord = $authRes->fetch();
    if (!$webRecord && $authRecord) {
        $ins = $DB->prepare("INSERT IGNORE INTO `account_data` (`id`,`displayName`,`silver`,`gold`,`cooldowns`,`socialData`,`birthday`,`gender`,`country`,`secretQuestion`,`secretAnswer`,`avatar`,`avatarType`,`rank`,`last_ip`,`admin_last_ip`,`reg_ip`,`last_login`,`last_login2`,`admin_last_login`,`admin_last_login2`,`status`,`event`,`salt`,`selected_realm`,`bt_milestone`) VALUES (:id,:displayName,0,0,'','','','', 'US',0,'','',0,0,:lastIp,'0.0.0.0',:regIp,NOW(),NOW(),NOW(),NOW(),'active','NONE',SHA1(CONCAT(RAND(),NOW(),:saltName)),1,0)");
        $ins->execute(array(':id'=>$account, ':displayName'=>$authRecord['username'], ':saltName'=>$authRecord['username'], ':lastIp'=>!empty($authRecord['last_ip'])?$authRecord['last_ip']:'0.0.0.0', ':regIp'=>!empty($authRecord['last_ip'])?$authRecord['last_ip']:'0.0.0.0'));
        $webRes->execute(array(':id'=>$account)); $webRecord=$webRes->fetch();
    }
    try {
        $rdb = $CORE->RealmDatabaseConnection($realm);
        $cols = "guid,name,level,race,class,gender,money,online,totaltime,totalKills,map,position_x,position_y,position_z";
        $charQ = $rdb->prepare("SELECT $cols FROM `characters` WHERE `account`=:id ORDER BY level DESC, name ASC");
        $charQ->execute(array(':id'=>$account));
        $characters = $charQ->fetchAll();
    } catch(Exception $e) { $characters = array(); }
}
?>
<nav id="secondary" class="disable-tabbing"><ul><li class="<?php echo $view==='list'?'current':''; ?>"><a href="index.php?page=users">Users</a></li><?php if($view==='manage' && $account): ?><li class="current"><a href="#manage">Manage User</a></li><?php endif; ?></ul></nav>
<section id="content"><div class="tab" id="maintab">
<?php if ($msg = $ERRORS->successPrint(array('manage_user'))) { echo $msg; } if ($err = $ERRORS->DoPrint(array('manage_user'))) { echo $err; } ?>
<?php if ($view === 'list'): ?>
  <div class="wc-page-title"><div><h2>User Management</h2><p>Manage CMS profiles, account access, credits, DP/VP currency, status and connected characters.</p></div><span class="pill green">Full Manager</span></div>
  <form method="get" class="admin-card wc-searchbar">
    <input type="hidden" name="page" value="users">
    <input type="text" name="q" value="<?php echo wa_u($search); ?>" placeholder="Search by account, email or ID">
    <button class="button primary" type="submit">Search</button>
    <a class="button secondary" href="index.php?page=users">Reset</a>
  </form>
  <div class="wc-table-card">
  <table class="datatable wc-user-table">
    <thead><tr><th>ID</th><th>Account</th><th>Display</th><th>Rank</th><th>Currency</th><th>Characters</th><th>GM</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $rank = class_exists('UserRank') ? new UserRank((int)$r['rank']) : null; ?>
      <tr>
        <td>#<?php echo (int)$r['id']; ?></td>
        <td><strong><?php echo wa_u($r['username']); ?></strong><br><small><?php echo wa_u($r['email']); ?></small></td>
        <td><?php echo wa_u($r['displayName']); ?><br><small>IP <?php echo wa_u($r['reg_ip'] ?: $r['last_ip']); ?></small></td>
        <td><span class="pill gold"><?php echo $rank ? wa_u($rank->string()).' ['.(int)$rank->int().']' : (int)$r['rank']; ?></span></td>
        <td><span class="pill">VP <?php echo (int)$r['silver']; ?></span> <span class="pill gold">DP <?php echo (int)$r['gold']; ?></span></td>
        <td><span class="pill"><?php echo (int)$r['char_count']; ?> chars</span></td>
        <td><?php echo $r['gm_access'] ? wa_u($r['gm_access']) : '<span class="pill">Player</span>'; ?></td>
        <td><span class="pill <?php echo wa_status_class($r['status']); ?>"><?php echo wa_u($r['status']); ?></span></td>
        <td><a class="button" href="index.php?page=users&view=manage&uid=<?php echo (int)$r['id']; ?>&realm=<?php echo (int)$r['selected_realm']; ?>">Manage</a></td>
      </tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9">No users found.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
<?php else: ?>
  <?php if(!$authRecord || !$webRecord): ?>
    <div class="notice">User not found.</div>
  <?php else: ?>
  <div class="wc-page-title"><div><h2><?php echo wa_u($authRecord['username']); ?></h2><p>Complete account control, CMS profile, currency, permissions and characters.</p></div><a class="button secondary" href="index.php?page=users">Back to users</a></div>
  <div class="wc-user-summary">
    <div class="stat-card"><span>CMS Display</span><strong><?php echo wa_u($webRecord['displayName']); ?></strong></div>
    <div class="stat-card"><span>Vote Points / Silver</span><strong><?php echo (int)$webRecord['silver']; ?></strong></div>
    <div class="stat-card"><span>Donation Points / Gold</span><strong><?php echo (int)$webRecord['gold']; ?></strong></div>
    <div class="stat-card"><span>Characters</span><strong><?php echo count($characters); ?></strong></div>
  </div>
  <div class="wc-manager-grid">
    <form class="admin-card wc-form-card" method="post" action="execute.php?take=manage_user">
      <input type="hidden" name="action" value="save_profile"><input type="hidden" name="uid" value="<?php echo (int)$account; ?>"><input type="hidden" name="realm" value="<?php echo (int)$realm; ?>">
      <h3>CMS Profile & Currency</h3>
      <div class="wc-form-grid">
        <label>Display Name<input type="text" name="displayName" value="<?php echo wa_u($webRecord['displayName']); ?>" maxlength="32"></label>
        <label>Status<select name="status"><option value="active" <?php echo $webRecord['status']==='active'?'selected':''; ?>>Active</option><option value="pending" <?php echo $webRecord['status']==='pending'?'selected':''; ?>>Pending</option><option value="disabled" <?php echo $webRecord['status']==='disabled'?'selected':''; ?>>Disabled</option></select></label>
        <label>VP / Silver<input type="number" name="silver" value="<?php echo (int)$webRecord['silver']; ?>" min="0"></label>
        <label>DP / Gold<input type="number" name="gold" value="<?php echo (int)$webRecord['gold']; ?>" min="0"></label>
        <label>Rank<select name="rank"><?php $RanksData = class_exists('RankStringData') ? new RankStringData() : false; if($RanksData){ foreach($RanksData->data as $rv=>$rn){ echo '<option value="'.(int)$rv.'" '.((int)$rv===(int)$webRecord['rank']?'selected':'').'>'.wa_u($rn).' ['.(int)$rv.']</option>'; }} else { for($i=0;$i<=9;$i++) echo '<option value="'.$i.'" '.($i==(int)$webRecord['rank']?'selected':'').'>Rank '.$i.'</option>'; } ?></select></label>
        <label>Selected Realm<select name="selected_realm"><?php foreach($realms_config as $rid=>$rc): ?><option value="<?php echo (int)$rid; ?>" <?php echo (int)$webRecord['selected_realm']===(int)$rid?'selected':''; ?>><?php echo wa_u($rc['name']); ?></option><?php endforeach; ?></select></label>
        <label>Country<input type="text" name="country" value="<?php echo wa_u($webRecord['country']); ?>" maxlength="2"></label>
        <label>Gender<input type="text" name="gender" value="<?php echo wa_u($webRecord['gender']); ?>" maxlength="10"></label>
      </div>
      <button class="button primary" type="submit">Save Profile</button>
    </form>

    <form class="admin-card wc-form-card" method="post" action="execute.php?take=manage_user">
      <input type="hidden" name="action" value="save_auth"><input type="hidden" name="uid" value="<?php echo (int)$account; ?>"><input type="hidden" name="realm" value="<?php echo (int)$realm; ?>">
      <h3>Auth Account</h3>
      <div class="wc-form-grid">
        <label>Username<input type="text" value="<?php echo wa_u($authRecord['username']); ?>" disabled></label>
        <label>Email<input type="email" name="email" value="<?php echo wa_u($authRecord['email']); ?>"></label>
        <label>Expansion<input type="number" name="expansion" value="<?php echo (int)$authRecord['expansion']; ?>" min="0" max="4"></label>
        <label>Locked<select name="locked"><option value="0" <?php echo (int)$authRecord['locked']===0?'selected':''; ?>>No</option><option value="1" <?php echo (int)$authRecord['locked']===1?'selected':''; ?>>Yes</option></select></label>
        <label>Mute Time<input type="number" name="mutetime" value="<?php echo (int)$authRecord['mutetime']; ?>" min="0"></label>
        <label>Last IP<input type="text" value="<?php echo wa_u($authRecord['last_ip']); ?>" disabled></label>
      </div>
      <button class="button primary" type="submit">Save Account</button>
    </form>

    <form class="admin-card wc-form-card" method="post" action="execute.php?take=manage_user">
      <input type="hidden" name="action" value="save_gm"><input type="hidden" name="uid" value="<?php echo (int)$account; ?>"><input type="hidden" name="realm" value="<?php echo (int)$realm; ?>">
      <h3>GM Access</h3>
      <div class="wc-form-grid">
        <label>Realm<select name="gm_realm"><option value="-1">All Realms (-1)</option><?php foreach($realms_config as $rid=>$rc): ?><option value="<?php echo (int)$rid; ?>"><?php echo wa_u($rc['name']); ?></option><?php endforeach; ?></select></label>
        <label>GM Level<select name="gmlevel"><option value="0">Player / Remove</option><option value="1">GM 1</option><option value="2">GM 2</option><option value="3">GM 3</option><option value="4">GM 4</option></select></label>
      </div>
      <button class="button primary" type="submit">Update GM Access</button>
    </form>
  </div>

  <div class="admin-card wc-form-card">
    <div class="wc-section-head"><div><h3>Characters</h3><p>Select the realm and manage each character linked to this account.</p></div><form method="get" class="wc-realm-picker"><input type="hidden" name="page" value="users"><input type="hidden" name="view" value="manage"><input type="hidden" name="uid" value="<?php echo (int)$account; ?>"><select name="realm"><?php foreach($realms_config as $rid=>$rc): ?><option value="<?php echo (int)$rid; ?>" <?php echo (int)$realm===(int)$rid?'selected':''; ?>><?php echo wa_u($rc['name']); ?></option><?php endforeach; ?></select><button class="button secondary" type="submit">Load</button></form></div>
    <?php if(!$characters): ?><div class="notice">No characters found for this account on selected realm.</div><?php else: ?>
    <div class="wc-character-grid">
    <?php foreach($characters as $ch): ?>
      <form class="wc-char-card" method="post" action="execute.php?take=manage_user">
        <input type="hidden" name="action" value="save_character"><input type="hidden" name="uid" value="<?php echo (int)$account; ?>"><input type="hidden" name="realm" value="<?php echo (int)$realm; ?>"><input type="hidden" name="guid" value="<?php echo (int)$ch['guid']; ?>">
        <div class="wc-char-top"><div><h4><?php echo wa_u($ch['name']); ?></h4><p><?php echo wa_u(wa_race_name($ch['race']).' '.wa_class_name($ch['class'])); ?></p></div><span class="pill <?php echo (int)$ch['online']===1?'green':'red'; ?>"><?php echo (int)$ch['online']===1?'Online':'Offline'; ?></span></div>
        <div class="wc-form-grid compact">
          <label>Level<input type="number" name="level" value="<?php echo (int)$ch['level']; ?>" min="1" max="80"></label>
          <label>Money Copper<input type="number" name="money" value="<?php echo (int)$ch['money']; ?>" min="0"><small><?php echo wa_money($ch['money']); ?></small></label>
          <label>Map<input type="number" name="map" value="<?php echo (int)$ch['map']; ?>"></label>
          <label>X<input type="text" name="position_x" value="<?php echo wa_u($ch['position_x']); ?>"></label>
          <label>Y<input type="text" name="position_y" value="<?php echo wa_u($ch['position_y']); ?>"></label>
          <label>Z<input type="text" name="position_z" value="<?php echo wa_u($ch['position_z']); ?>"></label>
        </div>
        <div class="wc-char-actions"><button class="button primary" type="submit">Save Character</button><a class="button secondary" target="_blank" href="../index.php?page=profile&uid=<?php echo (int)$account; ?>&char=<?php echo (int)$ch['guid']; ?>&realm=<?php echo (int)$realm; ?>">View Armory</a></div>
      </form>
    <?php endforeach; ?>
    </div><?php endif; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>
</div></section>
