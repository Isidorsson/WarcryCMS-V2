<?php
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_TICKETS)) { header('Location: index.php?page=tickets&error=1'); exit; }

$CORE->loggedInOrReturn();
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$realm = isset($_POST['realm']) ? (int)$_POST['realm'] : 1;
if (!isset($realms_config[$realm])) $realm = 1;

function wc_ticket_redirect($realm, $suffix='') { header('Location: index.php?page=tickets&realm='.(int)$realm.$suffix); exit; }
function wc_ticket_col($pdo, $table, $col){ try { $q=$pdo->prepare("SHOW COLUMNS FROM `".$table."` LIKE :c"); $q->execute(array(':c'=>$col)); return $q->rowCount()>0; } catch(Exception $e){ return false; } }
function wc_ticket_table($pdo){ foreach(array('gm_ticket','gm_tickets') as $t){ try{ $q=$pdo->query("SHOW TABLES LIKE '".$t."'"); if($q && $q->rowCount()>0) return $t; }catch(Exception $e){} } return false; }

if ($id <= 0) wc_ticket_redirect($realm, '&error=1');
$RDB = $CORE->RealmDatabaseConnection($realm);
if (!$RDB) wc_ticket_redirect($realm, '&error=1');
$table = wc_ticket_table($RDB);
if (!$table) wc_ticket_redirect($realm, '&error=1');

$idCol = ($table === 'gm_ticket') ? 'id' : 'ticketId';
$msgCol = ($table === 'gm_ticket') ? 'description' : 'message';
$hasCompleted = wc_ticket_col($RDB,$table,'completed');
$hasResponse = wc_ticket_col($RDB,$table,'response');
$hasComment = wc_ticket_col($RDB,$table,'comment');
$hasViewed = wc_ticket_col($RDB,$table,'viewed');
$hasAssigned = wc_ticket_col($RDB,$table,'assignedTo');
$hasClosedBy = wc_ticket_col($RDB,$table,'closedBy');
$hasNeedHelp = wc_ticket_col($RDB,$table,'needMoreHelp');
$hasLastMod = wc_ticket_col($RDB,$table,'lastModifiedTime');
$adminId = (int)$CURUSER->get('id');
if ($adminId <= 0 && isset($_SESSION['uid'])) $adminId = (int)$_SESSION['uid'];
if ($adminId <= 0) $adminId = 1;

try {
    if ($action === 'save') {
        $fields = array(); $params = array(':id'=>$id);
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        if ($message === '') throw new Exception('Ticket message cannot be empty.');
        $fields[] = "`$msgCol`=:message"; $params[':message'] = $message;
        if ($hasComment) { $fields[] = "`comment`=:comment"; $params[':comment'] = isset($_POST['comment']) ? trim($_POST['comment']) : ''; }
        if ($hasResponse) { $fields[] = "`response`=:response"; $params[':response'] = isset($_POST['response']) ? trim($_POST['response']) : ''; }
        if ($hasViewed) { $fields[] = "`viewed`=:viewed"; $params[':viewed'] = isset($_POST['viewed']) ? (int)$_POST['viewed'] : 0; }
        if ($hasAssigned) { $fields[] = "`assignedTo`=:assignedTo"; $params[':assignedTo'] = isset($_POST['assignedTo']) ? (int)$_POST['assignedTo'] : 0; }
        if ($hasNeedHelp) { $fields[] = "`needMoreHelp`=:needMoreHelp"; $params[':needMoreHelp'] = isset($_POST['needMoreHelp']) ? (int)$_POST['needMoreHelp'] : 0; }
        if ($hasLastMod) { $fields[] = "`lastModifiedTime`=:lm"; $params[':lm'] = time(); }
        $sql = "UPDATE `$table` SET ".implode(', ', $fields)." WHERE `$idCol`=:id LIMIT 1";
        $st = $RDB->prepare($sql); $st->execute($params);
        wc_ticket_redirect($realm, '&view='.$id.'&saved=1');
    }
    if ($action === 'close') {
        $fields = array(); $params = array(':id'=>$id);
        if ($hasClosedBy) { $fields[] = "`closedBy`=:admin"; $params[':admin']=$adminId; }
        if ($hasCompleted) { $fields[] = "`completed`=1"; }
        if ($hasViewed) { $fields[] = "`viewed`=1"; }
        if ($hasNeedHelp) { $fields[] = "`needMoreHelp`=0"; }
        if ($hasLastMod) { $fields[] = "`lastModifiedTime`=:lm"; $params[':lm']=time(); }
        if (!count($fields)) throw new Exception('No close fields available.');
        $st=$RDB->prepare("UPDATE `$table` SET ".implode(', ', $fields)." WHERE `$idCol`=:id LIMIT 1"); $st->execute($params);
        wc_ticket_redirect($realm, '&closed=1');
    }
    if ($action === 'open') {
        $fields = array(); $params = array(':id'=>$id);
        if ($hasClosedBy) { $fields[] = "`closedBy`=0"; }
        if ($hasCompleted) { $fields[] = "`completed`=0"; }
        if ($hasNeedHelp) { $fields[] = "`needMoreHelp`=1"; }
        if ($hasLastMod) { $fields[] = "`lastModifiedTime`=:lm"; $params[':lm']=time(); }
        if (!count($fields)) throw new Exception('No reopen fields available.');
        $st=$RDB->prepare("UPDATE `$table` SET ".implode(', ', $fields)." WHERE `$idCol`=:id LIMIT 1"); $st->execute($params);
        wc_ticket_redirect($realm, '&view='.$id.'&opened=1');
    }
    if ($action === 'delete') {
        $st=$RDB->prepare("DELETE FROM `$table` WHERE `$idCol`=:id LIMIT 1"); $st->execute(array(':id'=>$id));
        wc_ticket_redirect($realm, '&deleted=1');
    }
} catch (Exception $e) {
    wc_ticket_redirect($realm, '&view='.$id.'&error=1');
}
wc_ticket_redirect($realm, '&error=1');
