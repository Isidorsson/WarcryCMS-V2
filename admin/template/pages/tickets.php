<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_TICKETS)) { $CORE->ErrorBox('You do not have the required permissions.'); }

function wc_t_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function wc_t_col($pdo, $table, $col){ try { if(!preg_match('/^[A-Za-z0-9_]+$/',$table) || !preg_match('/^[A-Za-z0-9_]+$/',$col)) return false; $sql="SHOW COLUMNS FROM `".$table."` LIKE ".$pdo->quote($col); $q=$pdo->query($sql); return ($q && $q->rowCount()>0); } catch(Exception $e){ return false; } }
function wc_t_tables($pdo){ $found=false; foreach(array('gm_ticket','gm_tickets') as $t){ try{ $q=$pdo->query("SHOW TABLES LIKE '".$t."'"); if($q && $q->rowCount()>0){ $found=$t; break; } }catch(Exception $e){} } return $found; }
function wc_t_money($copper){ $copper=(int)$copper; $g=floor($copper/10000); $s=floor(($copper%10000)/100); $c=$copper%100; return $g.'g '.$s.'s '.$c.'c'; }

$RealmID = isset($_GET['realm']) ? (int)$_GET['realm'] : (isset($_SESSION['ADMIN_SelectedRealm']) ? (int)$_SESSION['ADMIN_SelectedRealm'] : 1);
if (!isset($realms_config[$RealmID])) $RealmID = 1;
$_SESSION['ADMIN_SelectedRealm'] = $RealmID;
$status = isset($_GET['status']) ? $_GET['status'] : 'open';
if (!in_array($status, array('open','closed','all'))) $status = 'open';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$REALM_DB = $CORE->RealmDatabaseConnection($RealmID);
$table = $REALM_DB ? wc_t_tables($REALM_DB) : false;

$success = isset($_GET['saved']) || isset($_GET['closed']) || isset($_GET['opened']) || isset($_GET['deleted']);
$error = isset($_GET['error']);
?>
<nav id="secondary" class="disable-tabbing">
  <ul>
    <li class="<?php echo $viewId ? '' : 'current'; ?>"><a href="index.php?page=tickets&realm=<?php echo (int)$RealmID; ?>">Tickets</a></li>
    <?php if ($viewId): ?><li class="current"><a href="#maintab">Manage #<?php echo (int)$viewId; ?></a></li><?php endif; ?>
  </ul>
</nav>
<section id="content">
<div class="tab wc-ticket-page" id="maintab">
  <div class="wc-page-title">
    <div>
      <h2>GM Ticket Manager</h2>
      <p>Full live management for AzerothCore GM tickets: view, edit, assign, close, reopen and delete directly from the website.</p>
    </div>
    <form method="get" class="wc-realm-picker">
      <input type="hidden" name="page" value="tickets">
      <label>Realm</label>
      <select name="realm" onchange="this.form.submit()">
        <?php foreach($realms_config as $rid=>$r): ?><option value="<?php echo (int)$rid; ?>" <?php echo $rid==$RealmID?'selected':''; ?>><?php echo wc_t_h($r['name']); ?></option><?php endforeach; ?>
      </select>
    </form>
  </div>

  <?php if ($success): ?><div class="notice success">Ticket action completed successfully.</div><?php endif; ?>
  <?php if ($error): ?><div class="notice">The ticket action could not be completed. Verify the ticket still exists and that the Characters DB is online.</div><?php endif; ?>

<?php if (!$REALM_DB): ?>
  <div class="notice">Unable to connect to the Characters database for this realm. Check <code>configuration/server.php</code>.</div>
<?php elseif (!$table): ?>
  <div class="notice">No GM ticket table found. AzerothCore normally uses <code>characters.gm_ticket</code>.</div>
<?php else:
  $idCol = ($table === 'gm_ticket') ? 'id' : 'ticketId';
  $guidCol = ($table === 'gm_ticket') ? 'playerGuid' : 'guid';
  $msgCol = ($table === 'gm_ticket') ? 'description' : 'message';
  $hasCompleted = wc_t_col($REALM_DB,$table,'completed');
  $hasResponse = wc_t_col($REALM_DB,$table,'response');
  $hasComment = wc_t_col($REALM_DB,$table,'comment');
  $hasViewed = wc_t_col($REALM_DB,$table,'viewed');
  $hasAssigned = wc_t_col($REALM_DB,$table,'assignedTo');
  $hasClosedBy = wc_t_col($REALM_DB,$table,'closedBy');
  $hasNeedHelp = wc_t_col($REALM_DB,$table,'needMoreHelp');

  if ($viewId > 0):
    $select = "t.`$idCol` AS ticketId, t.`$guidCol` AS guid, t.`name`, t.`$msgCol` AS message, t.`createTime`, ".($hasClosedBy?'t.`closedBy`':'0')." AS closedBy, ".($hasCompleted?'t.`completed`':'0')." AS completed, ".($hasComment?'t.`comment`':'\'\'')." AS comment, ".($hasResponse?'t.`response`':'\'\'')." AS response, ".($hasViewed?'t.`viewed`':'0')." AS viewed, ".($hasAssigned?'t.`assignedTo`':'0')." AS assignedTo, ".($hasNeedHelp?'t.`needMoreHelp`':'0')." AS needMoreHelp, c.account, c.race, c.class, c.gender, c.level, c.money, c.map, c.position_x, c.position_y, c.position_z, c.online";
    $st=$REALM_DB->prepare("SELECT $select FROM `$table` t LEFT JOIN `characters` c ON c.guid=t.`$guidCol` WHERE t.`$idCol`=:id LIMIT 1");
    $st->execute(array(':id'=>$viewId));
    $ticket=$st->fetch();
    if (!$ticket): ?>
      <div class="notice">Ticket not found.</div>
    <?php else: $open = ((int)$ticket['closedBy']===0 && (int)$ticket['completed']===0); ?>
      <div class="wc-user-summary wc-ticket-summary">
        <div class="stat-card"><span>Ticket ID</span><strong>#<?php echo (int)$ticket['ticketId']; ?></strong></div>
        <div class="stat-card"><span>Player</span><strong><?php echo wc_t_h($ticket['name']); ?></strong></div>
        <div class="stat-card"><span>Status</span><strong><span class="pill <?php echo $open?'green':'red'; ?>"><?php echo $open?'Open':'Closed'; ?></span></strong></div>
        <div class="stat-card"><span>Created</span><strong><?php echo ((int)$ticket['createTime']>0) ? date('Y-m-d H:i', (int)$ticket['createTime']) : 'Unknown'; ?></strong></div>
      </div>

      <div class="wc-manager-grid wc-ticket-grid">
        <form class="admin-card wc-form-card" method="post" action="execute.php?take=ticket_manage&action=save">
          <input type="hidden" name="id" value="<?php echo (int)$ticket['ticketId']; ?>">
          <input type="hidden" name="realm" value="<?php echo (int)$RealmID; ?>">
          <input type="hidden" name="ticket_table" value="<?php echo wc_t_h($table); ?>">
          <h3>Edit Ticket</h3>
          <label>Ticket message
            <textarea name="message" rows="7"><?php echo wc_t_h($ticket['message']); ?></textarea>
          </label>
          <?php if ($hasComment): ?><label>GM internal comment
            <textarea name="comment" rows="5"><?php echo wc_t_h($ticket['comment']); ?></textarea>
          </label><?php endif; ?>
          <?php if ($hasResponse): ?><label>GM response / answer
            <textarea name="response" rows="5"><?php echo wc_t_h($ticket['response']); ?></textarea>
          </label><?php endif; ?>
          <div class="wc-form-grid compact">
            <?php if ($hasViewed): ?><label>Viewed<select name="viewed"><option value="0" <?php echo (int)$ticket['viewed']===0?'selected':''; ?>>No</option><option value="1" <?php echo (int)$ticket['viewed']===1?'selected':''; ?>>Yes</option></select></label><?php endif; ?>
            <?php if ($hasAssigned): ?><label>Assigned To<input type="number" name="assignedTo" value="<?php echo (int)$ticket['assignedTo']; ?>"></label><?php endif; ?>
            <?php if ($hasNeedHelp): ?><label>Need More Help<select name="needMoreHelp"><option value="0" <?php echo (int)$ticket['needMoreHelp']===0?'selected':''; ?>>No</option><option value="1" <?php echo (int)$ticket['needMoreHelp']===1?'selected':''; ?>>Yes</option></select></label><?php endif; ?>
          </div>
          <div class="form-actions"><button type="submit">Save Ticket</button><a class="button secondary" href="index.php?page=tickets&realm=<?php echo (int)$RealmID; ?>">Back to list</a></div>
        </form>

        <div class="admin-card wc-form-card">
          <h3>Player / Character Info</h3>
          <div class="wc-ticket-info">
            <p><span>Character GUID</span><strong><?php echo (int)$ticket['guid']; ?></strong></p>
            <p><span>Account ID</span><strong><?php echo (int)$ticket['account']; ?></strong></p>
            <p><span>Level</span><strong><?php echo (int)$ticket['level']; ?></strong></p>
            <p><span>Money</span><strong><?php echo wc_t_h(wc_t_money($ticket['money'])); ?></strong></p>
            <p><span>Online</span><strong><span class="pill <?php echo ((int)$ticket['online']===1)?'green':'red'; ?>"><?php echo ((int)$ticket['online']===1)?'Online':'Offline'; ?></span></strong></p>
            <p><span>Map / Position</span><strong><?php echo (int)$ticket['map']; ?> — <?php echo round((float)$ticket['position_x'],2); ?>, <?php echo round((float)$ticket['position_y'],2); ?>, <?php echo round((float)$ticket['position_z'],2); ?></strong></p>
          </div>
          <div class="form-actions">
            <a class="button secondary" target="_blank" href="../index.php?page=armory&realm=<?php echo (int)$RealmID; ?>&character=<?php echo urlencode($ticket['name']); ?>">View Armory</a>
            <a class="button secondary" href="index.php?page=users&view=manage&uid=<?php echo (int)$ticket['account']; ?>&realm=<?php echo (int)$RealmID; ?>">Manage Account</a>
          </div>
          <hr class="wc-soft-line">
          <h3>Fast Actions</h3>
          <div class="form-actions">
            <?php if ($open): ?>
              <form method="post" action="execute.php?take=ticket_manage&action=close" onsubmit="return confirm('Close this GM ticket?');"><input type="hidden" name="id" value="<?php echo (int)$ticket['ticketId']; ?>"><input type="hidden" name="realm" value="<?php echo (int)$RealmID; ?>"><input type="hidden" name="ticket_table" value="<?php echo wc_t_h($table); ?>"><button type="submit">Close Ticket</button></form>
            <?php else: ?>
              <form method="post" action="execute.php?take=ticket_manage&action=open" onsubmit="return confirm('Reopen this GM ticket?');"><input type="hidden" name="id" value="<?php echo (int)$ticket['ticketId']; ?>"><input type="hidden" name="realm" value="<?php echo (int)$RealmID; ?>"><input type="hidden" name="ticket_table" value="<?php echo wc_t_h($table); ?>"><button type="submit">Reopen Ticket</button></form>
            <?php endif; ?>
            <form method="post" action="execute.php?take=ticket_manage&action=delete" onsubmit="return confirm('Delete this GM ticket permanently?');"><input type="hidden" name="id" value="<?php echo (int)$ticket['ticketId']; ?>"><input type="hidden" name="realm" value="<?php echo (int)$RealmID; ?>"><input type="hidden" name="ticket_table" value="<?php echo wc_t_h($table); ?>"><button class="button danger" type="submit">Delete Ticket</button></form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php else:
    $where = array(); $params = array();
    if ($status === 'open') { $where[] = ($hasClosedBy?'t.`closedBy`=0':'1=1').($hasCompleted?' AND t.`completed`=0':''); }
    if ($status === 'closed') { $where[] = ($hasClosedBy?'t.`closedBy`<>0':'0=1').($hasCompleted?' OR t.`completed`<>0':''); }
    if ($q !== '') { $where[] = "(t.`name` LIKE :q_name OR t.`$msgCol` LIKE :q_msg OR t.`$idCol` LIKE :qid)"; $params[':q_name']='%'.$q.'%'; $params[':q_msg']='%'.$q.'%'; $params[':qid']='%'.(int)$q.'%'; }
    $whereSql = count($where) ? 'WHERE '.implode(' AND ', array_map(function($w){ return '('.$w.')'; }, $where)) : '';
    $select = "t.`$idCol` AS ticketId, t.`$guidCol` AS guid, t.`name`, t.`$msgCol` AS message, t.`createTime`, ".($hasClosedBy?'t.`closedBy`':'0')." AS closedBy, ".($hasCompleted?'t.`completed`':'0')." AS completed, ".($hasComment?'t.`comment`':'\'\'')." AS comment, ".($hasResponse?'t.`response`':'\'\'')." AS response, ".($hasViewed?'t.`viewed`':'0')." AS viewed, ".($hasAssigned?'t.`assignedTo`':'0')." AS assignedTo, c.account, c.level, c.online";
    $sql = "SELECT $select FROM `$table` t LEFT JOIN `characters` c ON c.guid=t.`$guidCol` $whereSql ORDER BY t.`createTime` DESC LIMIT 300";
    $st=$REALM_DB->prepare($sql); $st->execute($params); $tickets=$st->fetchAll();
    $openCount=0; $closedCount=0; $totalCount=0; try{ $totalCount=(int)$REALM_DB->query("SELECT COUNT(*) FROM `$table`")->fetchColumn(); $openExpr=($hasClosedBy?'`closedBy`=0':'1=1').($hasCompleted?' AND `completed`=0':''); $openCount=(int)$REALM_DB->query("SELECT COUNT(*) FROM `$table` WHERE $openExpr")->fetchColumn(); $closedCount=max(0,$totalCount-$openCount); }catch(Exception $e){}
  ?>
    <div class="wc-user-summary wc-ticket-summary">
      <div class="stat-card"><span>Open Tickets</span><strong><?php echo (int)$openCount; ?></strong></div>
      <div class="stat-card"><span>Closed Tickets</span><strong><?php echo (int)$closedCount; ?></strong></div>
      <div class="stat-card"><span>Total Tickets</span><strong><?php echo (int)$totalCount; ?></strong></div>
      <div class="stat-card"><span>DB Table</span><strong><?php echo wc_t_h($table); ?></strong></div>
    </div>

    <form method="get" class="admin-card wc-searchbar wc-ticket-filters">
      <input type="hidden" name="page" value="tickets"><input type="hidden" name="realm" value="<?php echo (int)$RealmID; ?>">
      <input type="text" name="q" placeholder="Search ticket ID, player name or message..." value="<?php echo wc_t_h($q); ?>">
      <select name="status"><option value="open" <?php echo $status==='open'?'selected':''; ?>>Open only</option><option value="closed" <?php echo $status==='closed'?'selected':''; ?>>Closed only</option><option value="all" <?php echo $status==='all'?'selected':''; ?>>All tickets</option></select>
      <button type="submit">Search</button><a class="button secondary" href="index.php?page=tickets&realm=<?php echo (int)$RealmID; ?>">Reset</a>
    </form>

    <div class="wc-table-card">
      <table class="wc-user-table wc-ticket-table">
        <thead><tr><th>ID</th><th>Player</th><th>Message</th><th>Status</th><th>Viewed</th><th>Assigned</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($tickets as $t): $open=((int)$t['closedBy']===0 && (int)$t['completed']===0); ?>
          <tr>
            <td><strong>#<?php echo (int)$t['ticketId']; ?></strong></td>
            <td><strong><?php echo wc_t_h($t['name']); ?></strong><br><span class="pill <?php echo ((int)$t['online']===1)?'green':'red'; ?>"><?php echo ((int)$t['online']===1)?'online':'offline'; ?></span></td>
            <td class="ticket-message"><?php echo nl2br(wc_t_h(mb_substr((string)$t['message'],0,180))); ?><?php echo mb_strlen((string)$t['message'])>180?'...':''; ?></td>
            <td><span class="pill <?php echo $open?'green':'red'; ?>"><?php echo $open?'Open':'Closed'; ?></span></td>
            <td><?php echo (int)$t['viewed'] ? '<span class="pill green">Yes</span>' : '<span class="pill">No</span>'; ?></td>
            <td><?php echo (int)$t['assignedTo']; ?></td>
            <td><?php echo ((int)$t['createTime']>0) ? date('Y-m-d H:i', (int)$t['createTime']) : 'Unknown'; ?></td>
            <td><div class="button-group"><a class="button" href="index.php?page=tickets&realm=<?php echo (int)$RealmID; ?>&view=<?php echo (int)$t['ticketId']; ?>">Manage</a><form method="post" action="execute.php?take=ticket_manage&action=<?php echo $open?'close':'open'; ?>" onsubmit="return confirm('<?php echo $open?'Close':'Reopen'; ?> this ticket?');"><input type="hidden" name="id" value="<?php echo (int)$t['ticketId']; ?>"><input type="hidden" name="realm" value="<?php echo (int)$RealmID; ?>"><input type="hidden" name="ticket_table" value="<?php echo wc_t_h($table); ?>"><button type="submit" class="button secondary"><?php echo $open?'Close':'Open'; ?></button></form></div></td>
          </tr>
        <?php endforeach; if (!$tickets): ?><tr><td colspan="8">No tickets found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>
</div>
</section>
