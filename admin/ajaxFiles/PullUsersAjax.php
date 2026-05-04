<?php
if (!defined('init_ajax')) { header('HTTP/1.0 404 not found'); exit; }

if (!$CURUSER->isOnline()) { echo json_encode(array('error' => 'You must be logged in.')); die; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_PREV_USERS)) { echo json_encode(array('error' => 'You do not have the required permissions.')); die; }

function wc_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function wc_dt_get($key, $default = '') { return isset($_GET[$key]) ? $_GET[$key] : $default; }

$start  = max(0, (int)wc_dt_get('iDisplayStart', 0));
$length = (int)wc_dt_get('iDisplayLength', 25);
if ($length < 1 || $length > 250) { $length = 25; }
$search = trim((string)wc_dt_get('sSearch', ''));
$sEcho  = (int)wc_dt_get('sEcho', 0);

$columns = array('id', 'username', 'email', 'joindate');
$orderColumn = 'id';
$orderDir = 'DESC';
if (isset($_GET['iSortCol_0'])) {
    $idx = (int)$_GET['iSortCol_0'];
    if ($idx === 0) $orderColumn = 'id';
    if ($idx === 1) $orderColumn = 'username';
    if ($idx === 4) $orderColumn = 'email';
    if ($idx === 6) $orderColumn = 'joindate';
}
if (isset($_GET['sSortDir_0']) && strtolower($_GET['sSortDir_0']) === 'asc') { $orderDir = 'ASC'; }

$where = '';
$params = array();
if ($search !== '') {
    $where = "WHERE `id` LIKE :q OR `username` LIKE :q OR `email` LIKE :q";
    $params[':q'] = '%' . $search . '%';
}

try {
    $totalStmt = $AUTH_DB->query("SELECT COUNT(`id`) FROM `account`");
    $iTotal = (int)$totalStmt->fetchColumn();

    $countSql = "SELECT COUNT(`id`) FROM `account` $where";
    $countStmt = $AUTH_DB->prepare($countSql);
    foreach ($params as $k => $v) { $countStmt->bindValue($k, $v, PDO::PARAM_STR); }
    $countStmt->execute();
    $iFilteredTotal = (int)$countStmt->fetchColumn();

    $sql = "SELECT `id`, `username`, `email`, `joindate` FROM `account` $where ORDER BY `$orderColumn` $orderDir LIMIT :start, :len";
    $stmt = $AUTH_DB->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v, PDO::PARAM_STR); }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':len', $length, PDO::PARAM_INT);
    $stmt->execute();

    $output = array(
        'sEcho' => $sEcho,
        'iTotalRecords' => $iTotal,
        'iTotalDisplayRecords' => $iFilteredTotal,
        'aaData' => array()
    );

    while ($authRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $accId = (int)$authRow['id'];

        $webStmt = $DB->prepare("SELECT `displayName`, `rank`, `reg_ip`, `status` FROM `account_data` WHERE `id` = :id LIMIT 1");
        $webStmt->bindValue(':id', $accId, PDO::PARAM_INT);
        $webStmt->execute();
        $webRow = $webStmt->fetch(PDO::FETCH_ASSOC);

        $displayName = ($webRow && $webRow['displayName'] !== '') ? $webRow['displayName'] : $authRow['username'];
        $rankValue = ($webRow && is_numeric($webRow['rank'])) ? (int)$webRow['rank'] : 0;
        $regIp = ($webRow && $webRow['reg_ip'] !== '') ? $webRow['reg_ip'] : '-';
        $statusBadge = (!$webRow) ? ' <span class="wc-badge warn">CMS missing</span>' : '';

        $gmLevel = '';
        try {
            $gmRes = $AUTH_DB->prepare("SELECT `gmlevel`, `RealmID` FROM `account_access` WHERE `id` = :acc ORDER BY `RealmID` ASC");
            $gmRes->bindValue(':acc', $accId, PDO::PARAM_INT);
            $gmRes->execute();
            $parts = array();
            while ($gmRec = $gmRes->fetch(PDO::FETCH_ASSOC)) {
                $parts[] = 'Level: ' . wc_h($gmRec['gmlevel']) . ' - Realm: ' . wc_h($gmRec['RealmID']);
            }
            $gmLevel = implode('<br>', $parts);
        } catch (Exception $e) { $gmLevel = ''; }

        $Rank = new UserRank($rankValue);
        $output['aaData'][] = array(
            $accId,
            '<a href="index.php?page=user-preview&uid=' . $accId . '">' . wc_h($displayName) . '</a> [' . wc_h($authRow['username']) . ']' . $statusBadge,
            wc_h($Rank->string()) . ' [' . $Rank->int() . ']',
            $gmLevel,
            wc_h($authRow['email']),
            wc_h($regIp),
            wc_h($authRow['joindate'])
        );
    }

    echo json_encode($output);
} catch (Exception $e) {
    echo json_encode(array(
        'sEcho' => $sEcho,
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
        'aaData' => array(),
        'error' => 'Users fetch failed: ' . $e->getMessage()
    ));
}
