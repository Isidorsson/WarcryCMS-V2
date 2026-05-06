<?php
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_TICKETS)) { header('Location: index.php?page=tickets&error=1'); exit; }

$CORE->loggedInOrReturn();

$action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$realm  = isset($_POST['realm']) ? (int)$_POST['realm'] : (isset($_GET['realm']) ? (int)$_GET['realm'] : 1);
$postedTable = isset($_POST['ticket_table']) ? trim($_POST['ticket_table']) : '';

if (!isset($realms_config[$realm])) { $realm = 1; }

function wc_ticket_redirect($realm, $suffix = '') {
    header('Location: index.php?page=tickets&realm='.(int)$realm.$suffix);
    exit;
}

function wc_ticket_allowed_tables() {
    return array(
        'gm_ticket' => array(
            'table_sql' => '`gm_ticket`',
            'id_col'    => 'id',
            'guid_col'  => 'playerGuid',
            'msg_col'   => 'description',
            'columns'   => array('id','playerGuid','description','completed','response','comment','viewed','assignedTo','closedBy','resolvedBy','needMoreHelp','lastModifiedTime','escalated')
        ),
        'gm_tickets' => array(
            'table_sql' => '`gm_tickets`',
            'id_col'    => 'ticketId',
            'guid_col'  => 'guid',
            'msg_col'   => 'message',
            'columns'   => array('ticketId','guid','message','completed','response','comment','viewed','assignedTo','closedBy','resolvedBy','needMoreHelp','lastModifiedTime','escalated')
        )
    );
}

function wc_ticket_table_meta($table) {
    $allowed = wc_ticket_allowed_tables();
    return (is_string($table) && isset($allowed[$table])) ? $allowed[$table] : false;
}

function wc_ticket_safe_col_sql($table, $col) {
    $meta = wc_ticket_table_meta($table);
    if (!$meta || !in_array($col, $meta['columns'], true)) { return false; }
    return '`'.$col.'`';
}

function wc_ticket_col($pdo, $table, $col) {
    try {
        $meta = wc_ticket_table_meta($table);
        if (!$meta || !in_array($col, $meta['columns'], true)) { return false; }
        $q = $pdo->query('SHOW COLUMNS FROM '.$meta['table_sql'].' LIKE '.$pdo->quote($col));
        return ($q && $q->rowCount() > 0);
    } catch (Exception $e) { return false; }
}

function wc_ticket_table_exists($pdo, $table) {
    try {
        $meta = wc_ticket_table_meta($table);
        if (!$meta) { return false; }
        $q = $pdo->query('SHOW TABLES LIKE '.$pdo->quote($table));
        return ($q && $q->rowCount() > 0);
    } catch (Exception $e) { return false; }
}

function wc_ticket_tables($pdo, $preferred = '') {
    $tables = array();
    if ($preferred !== '' && wc_ticket_table_meta($preferred) && wc_ticket_table_exists($pdo, $preferred)) {
        $tables[] = $preferred;
    }
    foreach (array('gm_ticket', 'gm_tickets') as $t) {
        if (!in_array($t, $tables, true) && wc_ticket_table_exists($pdo, $t)) {
            $tables[] = $t;
        }
    }
    return $tables;
}

function wc_ticket_id_col($table) {
    $meta = wc_ticket_table_meta($table);
    return $meta ? $meta['id_col'] : 'id';
}

function wc_ticket_id_col_sql($table) {
    $meta = wc_ticket_table_meta($table);
    return $meta ? '`'.$meta['id_col'].'`' : '`id`';
}

function wc_ticket_guid_col($table) {
    $meta = wc_ticket_table_meta($table);
    return $meta ? $meta['guid_col'] : 'playerGuid';
}

function wc_ticket_msg_col($table) {
    $meta = wc_ticket_table_meta($table);
    return $meta ? $meta['msg_col'] : 'description';
}

function wc_ticket_exists($pdo, $table, $id) {
    // Keep table and identifier SQL static. This prevents SQL injection and avoids SAST false positives.
    if ($table === 'gm_ticket') {
        $st = $pdo->prepare('SELECT COUNT(*) FROM `gm_ticket` WHERE `id`=:id');
    } elseif ($table === 'gm_tickets') {
        $st = $pdo->prepare('SELECT COUNT(*) FROM `gm_tickets` WHERE `ticketId`=:id');
    } else {
        return false;
    }

    $st->execute(array(':id' => (int)$id));
    return ((int)$st->fetchColumn() > 0);
}

function wc_ticket_update_column($pdo, $table, $id, $column, $value) {
    $id = (int)$id;
    if ($id <= 0) { return 0; }

    $statements = array(
        'gm_ticket' => array(
            'description'      => 'UPDATE `gm_ticket` SET `description`=:v WHERE `id`=:id LIMIT 1',
            'completed'        => 'UPDATE `gm_ticket` SET `completed`=:v WHERE `id`=:id LIMIT 1',
            'response'         => 'UPDATE `gm_ticket` SET `response`=:v WHERE `id`=:id LIMIT 1',
            'comment'          => 'UPDATE `gm_ticket` SET `comment`=:v WHERE `id`=:id LIMIT 1',
            'viewed'           => 'UPDATE `gm_ticket` SET `viewed`=:v WHERE `id`=:id LIMIT 1',
            'assignedTo'       => 'UPDATE `gm_ticket` SET `assignedTo`=:v WHERE `id`=:id LIMIT 1',
            'closedBy'         => 'UPDATE `gm_ticket` SET `closedBy`=:v WHERE `id`=:id LIMIT 1',
            'resolvedBy'       => 'UPDATE `gm_ticket` SET `resolvedBy`=:v WHERE `id`=:id LIMIT 1',
            'needMoreHelp'     => 'UPDATE `gm_ticket` SET `needMoreHelp`=:v WHERE `id`=:id LIMIT 1',
            'lastModifiedTime' => 'UPDATE `gm_ticket` SET `lastModifiedTime`=:v WHERE `id`=:id LIMIT 1',
            'escalated'        => 'UPDATE `gm_ticket` SET `escalated`=:v WHERE `id`=:id LIMIT 1'
        ),
        'gm_tickets' => array(
            'message'          => 'UPDATE `gm_tickets` SET `message`=:v WHERE `ticketId`=:id LIMIT 1',
            'completed'        => 'UPDATE `gm_tickets` SET `completed`=:v WHERE `ticketId`=:id LIMIT 1',
            'response'         => 'UPDATE `gm_tickets` SET `response`=:v WHERE `ticketId`=:id LIMIT 1',
            'comment'          => 'UPDATE `gm_tickets` SET `comment`=:v WHERE `ticketId`=:id LIMIT 1',
            'viewed'           => 'UPDATE `gm_tickets` SET `viewed`=:v WHERE `ticketId`=:id LIMIT 1',
            'assignedTo'       => 'UPDATE `gm_tickets` SET `assignedTo`=:v WHERE `ticketId`=:id LIMIT 1',
            'closedBy'         => 'UPDATE `gm_tickets` SET `closedBy`=:v WHERE `ticketId`=:id LIMIT 1',
            'resolvedBy'       => 'UPDATE `gm_tickets` SET `resolvedBy`=:v WHERE `ticketId`=:id LIMIT 1',
            'needMoreHelp'     => 'UPDATE `gm_tickets` SET `needMoreHelp`=:v WHERE `ticketId`=:id LIMIT 1',
            'lastModifiedTime' => 'UPDATE `gm_tickets` SET `lastModifiedTime`=:v WHERE `ticketId`=:id LIMIT 1',
            'escalated'        => 'UPDATE `gm_tickets` SET `escalated`=:v WHERE `ticketId`=:id LIMIT 1'
        )
    );

    if (!isset($statements[$table]) || !isset($statements[$table][$column])) { return 0; }
    if (!wc_ticket_col($pdo, $table, $column)) { return 0; }
    if (!wc_ticket_exists($pdo, $table, $id)) { return 0; }

    $st = $pdo->prepare($statements[$table][$column]);
    $st->execute(array(':v' => $value, ':id' => $id));
    return max(1, (int)$st->rowCount());
}

if ($id <= 0) { wc_ticket_redirect($realm, '&error=bad_id'); }

$RDB = $CORE->RealmDatabaseConnection($realm);
if (!$RDB) { wc_ticket_redirect($realm, '&error=db'); }

$tables = wc_ticket_tables($RDB, $postedTable);
if (!$tables) { wc_ticket_redirect($realm, '&error=no_table'); }

$adminId = 1;
try { $adminId = (int)$CURUSER->get('id'); } catch (Exception $e) { $adminId = 1; }
if ($adminId <= 0 && isset($_SESSION['uid'])) { $adminId = (int)$_SESSION['uid']; }
if ($adminId <= 0) { $adminId = 1; }

$changed = 0;
$primaryTable = $tables[0];

try {
    foreach ($tables as $table) {
        if (!wc_ticket_exists($RDB, $table, $id)) { continue; }

        $msgCol       = wc_ticket_msg_col($table);
        $hasCompleted = wc_ticket_col($RDB, $table, 'completed');
        $hasResponse  = wc_ticket_col($RDB, $table, 'response');
        $hasComment   = wc_ticket_col($RDB, $table, 'comment');
        $hasViewed    = wc_ticket_col($RDB, $table, 'viewed');
        $hasAssigned  = wc_ticket_col($RDB, $table, 'assignedTo');
        $hasClosedBy  = wc_ticket_col($RDB, $table, 'closedBy');
        $hasResolved  = wc_ticket_col($RDB, $table, 'resolvedBy');
        $hasNeedHelp  = wc_ticket_col($RDB, $table, 'needMoreHelp');
        $hasLastMod   = wc_ticket_col($RDB, $table, 'lastModifiedTime');
        $hasEscalated = wc_ticket_col($RDB, $table, 'escalated');

        if ($action === 'save') {
            $message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';
            if ($message === '') { throw new Exception('Ticket message cannot be empty.'); }

            $changed += wc_ticket_update_column($RDB, $table, $id, $msgCol, $message);
            if ($hasComment)  { $changed += wc_ticket_update_column($RDB, $table, $id, 'comment', isset($_POST['comment']) ? trim((string)$_POST['comment']) : ''); }
            if ($hasResponse) { $changed += wc_ticket_update_column($RDB, $table, $id, 'response', isset($_POST['response']) ? trim((string)$_POST['response']) : ''); }
            if ($hasViewed)   { $changed += wc_ticket_update_column($RDB, $table, $id, 'viewed', isset($_POST['viewed']) ? (int)$_POST['viewed'] : 1); }
            if ($hasAssigned) { $changed += wc_ticket_update_column($RDB, $table, $id, 'assignedTo', isset($_POST['assignedTo']) ? (int)$_POST['assignedTo'] : 0); }
            if ($hasNeedHelp) { $changed += wc_ticket_update_column($RDB, $table, $id, 'needMoreHelp', isset($_POST['needMoreHelp']) ? (int)$_POST['needMoreHelp'] : 1); }
            if ($hasLastMod)  { $changed += wc_ticket_update_column($RDB, $table, $id, 'lastModifiedTime', time()); }
            continue;
        }

        if ($action === 'close') {
            // AzerothCore reads these columns directly. A ticket is closed when closedBy or completed is non-zero.
            if ($hasClosedBy)  { $changed += wc_ticket_update_column($RDB, $table, $id, 'closedBy', $adminId); }
            if ($hasResolved)  { $changed += wc_ticket_update_column($RDB, $table, $id, 'resolvedBy', $adminId); }
            if ($hasCompleted) { $changed += wc_ticket_update_column($RDB, $table, $id, 'completed', 1); }
            if ($hasViewed)    { $changed += wc_ticket_update_column($RDB, $table, $id, 'viewed', 1); }
            if ($hasNeedHelp)  { $changed += wc_ticket_update_column($RDB, $table, $id, 'needMoreHelp', 0); }
            if ($hasEscalated) { $changed += wc_ticket_update_column($RDB, $table, $id, 'escalated', 0); }
            if ($hasLastMod)   { $changed += wc_ticket_update_column($RDB, $table, $id, 'lastModifiedTime', time()); }
            continue;
        }

        if ($action === 'open') {
            if ($hasClosedBy)  { $changed += wc_ticket_update_column($RDB, $table, $id, 'closedBy', 0); }
            if ($hasResolved)  { $changed += wc_ticket_update_column($RDB, $table, $id, 'resolvedBy', 0); }
            if ($hasCompleted) { $changed += wc_ticket_update_column($RDB, $table, $id, 'completed', 0); }
            if ($hasViewed)    { $changed += wc_ticket_update_column($RDB, $table, $id, 'viewed', 0); }
            if ($hasNeedHelp)  { $changed += wc_ticket_update_column($RDB, $table, $id, 'needMoreHelp', 1); }
            if ($hasLastMod)   { $changed += wc_ticket_update_column($RDB, $table, $id, 'lastModifiedTime', time()); }
            continue;
        }

        if ($action === 'delete') {
            // Keep DELETE statements fully static so scanners do not flag dynamic SQL identifiers.
            // $table is already restricted by wc_ticket_tables(), but we still branch explicitly.
            if ($table === 'gm_ticket') {
                $st = $RDB->prepare('DELETE FROM `gm_ticket` WHERE `id`=:id LIMIT 1');
            } elseif ($table === 'gm_tickets') {
                $st = $RDB->prepare('DELETE FROM `gm_tickets` WHERE `ticketId`=:id LIMIT 1');
            } else {
                continue;
            }

            $st->execute(array(':id' => (int)$id));
            $changed += (int)$st->rowCount();
            continue;
        }
    }

    if ($changed <= 0) { wc_ticket_redirect($realm, '&view='.$id.'&error=no_db_change'); }

    if ($action === 'save')   { wc_ticket_redirect($realm, '&view='.$id.'&saved=1'); }
    if ($action === 'close')  { wc_ticket_redirect($realm, '&closed=1'); }
    if ($action === 'open')   { wc_ticket_redirect($realm, '&view='.$id.'&opened=1'); }
    if ($action === 'delete') { wc_ticket_redirect($realm, '&deleted=1'); }
} catch (Exception $e) {
    wc_ticket_redirect($realm, '&view='.$id.'&error=1');
}

wc_ticket_redirect($realm, '&error=unknown_action');
