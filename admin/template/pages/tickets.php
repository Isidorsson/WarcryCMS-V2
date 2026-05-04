<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_TICKETS)) { $CORE->ErrorBox('You do not have the required permissions.'); }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$RealmID = isset($_GET['realm']) ? (int)$_GET['realm'] : (isset($_SESSION['ADMIN_SelectedRealm']) ? (int)$_SESSION['ADMIN_SelectedRealm'] : 1);
if (!isset($realms_config[$RealmID])) $RealmID = 1;
$_SESSION['ADMIN_SelectedRealm'] = $RealmID;
$iclosed = isset($_GET['iclosed']) ? (int)$_GET['iclosed'] : 0;
$REALM_DB = $CORE->RealmDatabaseConnection($RealmID);
?>
<nav id="secondary" class="disable-tabbing"><ul><li class="current"><a href="#maintab">Browse</a></li></ul></nav>
<section id="content"><div class="tab" id="maintab"><h2>GM Tickets</h2>
<?php if (!$REALM_DB): ?>
  <div class="error-box">Unable to connect to the Characters database for this realm. Check <code>configuration/server.php</code>.</div>
<?php else:
  $table = false; foreach (array('gm_ticket','gm_tickets') as $t) { $chk=$REALM_DB->query("SHOW TABLES LIKE '$t'"); if ($chk && $chk->rowCount()>0){$table=$t;break;} }
  if (!$table): ?>
    <div class="error-box">No GM ticket table found. AzerothCore normally uses <code>characters.gm_ticket</code>.</div>
  <?php else:
    if ($table === 'gm_ticket') {
      $where = $iclosed ? '' : 'WHERE t.closedBy = 0 AND t.completed = 0 AND t.type = 0';
      $sql = "SELECT t.id AS ticketId, t.description AS message, t.playerGuid AS guid, t.name, t.createTime, t.closedBy, t.assignedTo, t.comment, t.viewed, t.completed, t.response, c.online FROM gm_ticket t LEFT JOIN characters c ON c.guid=t.playerGuid $where ORDER BY t.createTime DESC LIMIT 250";
    } else {
      $where = $iclosed ? '' : 'WHERE t.closedBy = 0';
      $sql = "SELECT t.ticketId, t.message, t.guid, t.name, t.createTime, t.closedBy, t.assignedTo, t.comment, t.viewed, 0 AS completed, '' AS response, c.online FROM gm_tickets t LEFT JOIN characters c ON c.guid=t.guid $where ORDER BY t.createTime DESC LIMIT 250";
    }
    $res = $REALM_DB->query($sql); $tickets = $res ? $res->fetchAll() : array(); ?>
    <div class="admin-actions"><a class="button <?php echo !$iclosed?'primary':''; ?>" href="index.php?page=tickets&iclosed=0">Open only</a><a class="button <?php echo $iclosed?'primary':''; ?>" href="index.php?page=tickets&iclosed=1">Include closed</a><span class="pill">Realm: <?php echo h($realms_config[$RealmID]['name']); ?></span><span class="pill">Table: characters.<?php echo h($table); ?></span></div>
    <table class="datatable"><thead><tr><th>ID</th><th>Player</th><th>Message</th><th>Status</th><th>Viewed</th><th>Created</th><th>Comment / Response</th></tr></thead><tbody>
    <?php foreach ($tickets as $t): $open = ((int)$t['closedBy']===0 && (int)$t['completed']===0); $date = ((int)$t['createTime']>0 ? date('Y-m-d H:i:s',(int)$t['createTime']) : 'Unknown'); ?>
      <tr><td><?php echo (int)$t['ticketId']; ?></td><td><strong><?php echo h($t['name']); ?></strong><br><span class="pill <?php echo ((int)$t['online']===1)?'green':'red'; ?>"><?php echo ((int)$t['online']===1)?'online':'offline'; ?></span></td><td style="max-width:650px"><?php echo nl2br(h($t['message'])); ?></td><td><span class="pill <?php echo $open?'green':'red'; ?>"><?php echo $open?'Open':'Closed'; ?></span></td><td><?php echo (int)$t['viewed']; ?></td><td><?php echo h($date); ?></td><td><?php echo nl2br(h(trim($t['comment'].' '.$t['response']))); ?></td></tr>
    <?php endforeach; if (!$tickets): ?><tr><td colspan="7">No tickets found.</td></tr><?php endif; ?>
    </tbody></table>
  <?php endif; ?>
<?php endif; ?>
</div>
