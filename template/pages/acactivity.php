<?php
if (!defined('init_pages'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$CORE->loggedInOrReturn();

// Set the title
$TPL->SetTitle('Account Activity');
// CSS
$TPL->AddCSS('template/style/page-activity-all.css');
// Print the header
$TPL->LoadHeader();

$p = (isset($_GET['p']) ? (int)$_GET['p'] : 1);
if ($p < 1) { $p = 1; }

$perPage = 6;
$accountId = (int)$CURUSER->get('id');
$username = (string)$CURUSER->get('username');
$displayName = (string)$CURUSER->get('displayName');
$logs = array();

function wc_activity_table_exists($table)
{
    global $DB;
    static $cache = array();

    // Only allow the exact activity-related table names used below.
    // This keeps the check safe and avoids dynamic SHOW TABLES syntax that breaks on some MySQL/PDO setups.
    $allowedTables = array(
        'coin_activity',
        'store_activity',
        'paypal_logs',
        'paymentwall_logs',
        'article_comments',
        'bugtracker',
        'wcf_topics',
        'wcf_forums',
        'wcf_posts',
        'images'
    );

    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    try {
        $stmt = $DB->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1');
        if (!$stmt) {
            $cache[$table] = false;
            return false;
        }
        $stmt->bindValue(':table', $table, PDO::PARAM_STR);
        $stmt->execute();
        $cache[$table] = ((int)$stmt->fetchColumn() > 0);
    } catch (Exception $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

function wc_activity_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function wc_activity_add(&$logs, $type, $title, $details, $time, $status = '')
{
    if (empty($time) || $time == '0000-00-00 00:00:00') {
        $time = date('Y-m-d H:i:s');
    }

    $logs[] = array(
        'type' => $type,
        'title' => $title,
        'details' => $details,
        'time' => $time,
        'status' => $status,
        'stamp' => strtotime($time) ? strtotime($time) : time()
    );
}

// Account creation / base account information
if ($CURUSER->get('joindate')) {
    wc_activity_add($logs, 'account', 'Account created', 'Your account was created on the website.', $CURUSER->get('joindate'), 'info');
}

// Coins activity
if (wc_activity_table_exists('coin_activity')) {
    try {
        $stmt = $DB->prepare('SELECT * FROM `coin_activity` WHERE `account` = :account ORDER BY `id` DESC LIMIT 300;');
        $stmt->bindValue(':account', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $coinType = 'Coins';
            if ((int)$row['coinsType'] == 1) { $coinType = 'Silver coins'; }
            if ((int)$row['coinsType'] == 2) { $coinType = 'Gold coins'; }

            $action = 'Coins activity';
            if ((int)$row['sourceType'] == 1) { $action = 'Purchased coins'; }
            if ((int)$row['sourceType'] == 2) { $action = 'Reward received'; }
            if ((int)$row['sourceType'] == 3) { $action = 'Coins deducted'; }

            $sign = ((int)$row['exchangeType'] == 2 ? '-' : '+');
            wc_activity_add($logs, 'coins', $action, $sign.' '.(int)$row['amount'].' '.$coinType.' - '.$row['source'], $row['time'], 'ok');
        }
    } catch (Exception $e) {}
}

// Store purchases
if (wc_activity_table_exists('store_activity')) {
    try {
        $stmt = $DB->prepare('SELECT sa.*, si.name AS item_name FROM `store_activity` sa LEFT JOIN `store_items` si ON si.id = sa.itemId WHERE sa.`account` = :account ORDER BY sa.`id` DESC LIMIT 300;');
        $stmt->bindValue(':account', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $itemName = (!empty($row['item_name']) ? $row['item_name'] : 'Unknown item');
            wc_activity_add($logs, 'shop', 'Store purchase', $itemName.' - '.$row['money'], $row['time'], 'ok');
        }
    } catch (Exception $e) {}
}

// PayPal logs - account can be stored as account id or username depending on the old CMS flow
if (wc_activity_table_exists('paypal_logs')) {
    try {
        $stmt = $DB->prepare('SELECT * FROM `paypal_logs` WHERE `account` = :accountId OR `account` = :username OR `account` = :displayName ORDER BY `id` DESC LIMIT 200;');
        $stmt->bindValue(':accountId', (string)$accountId, PDO::PARAM_STR);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':displayName', $displayName, PDO::PARAM_STR);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            wc_activity_add($logs, 'payment', 'PayPal payment '.$row['paypal_status'], 'Amount: '.$row['amount'].' | Transaction: '.$row['txn_id'], $row['time'], $row['paypal_status']);
        }
    } catch (Exception $e) {}
}

// Paymentwall logs
if (wc_activity_table_exists('paymentwall_logs')) {
    try {
        $stmt = $DB->prepare('SELECT * FROM `paymentwall_logs` WHERE `account` = :account ORDER BY `id` DESC LIMIT 200;');
        $stmt->bindValue(':account', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            wc_activity_add($logs, 'payment', 'Paymentwall transaction', 'Amount: '.$row['TransactionAmount'].' | Ref: '.$row['TransactionRefId'].' | '.$row['text'], date('Y-m-d H:i:s'), 'info');
        }
    } catch (Exception $e) {}
}

// Article comments
if (wc_activity_table_exists('article_comments')) {
    try {
        $stmt = $DB->prepare('SELECT ac.*, a.title AS article_title FROM `article_comments` ac LEFT JOIN `articles` a ON a.id = ac.article WHERE ac.`author` = :account ORDER BY ac.`id` DESC LIMIT 200;');
        $stmt->bindValue(':account', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $article = (!empty($row['article_title']) ? $row['article_title'] : 'Unknown article');
            wc_activity_add($logs, 'comment', 'Article comment posted', 'Article: '.$article, $row['added'], 'info');
        }
    } catch (Exception $e) {}
}

// Bugtracker reports
if (wc_activity_table_exists('bugtracker')) {
    try {
        $stmt = $DB->prepare('SELECT * FROM `bugtracker` WHERE `account` = :account ORDER BY `id` DESC LIMIT 200;');
        $stmt->bindValue(':account', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = ((int)$row['status'] == 1 ? 'completed' : 'pending');
            wc_activity_add($logs, 'bugtracker', 'Bug report created', $row['title'], $row['added'], $status);
        }
    } catch (Exception $e) {}
}

// Forum topics
if (wc_activity_table_exists('wcf_topics')) {
    try {
        $stmt = $DB->prepare('SELECT wt.*, wf.name AS forum_name FROM `wcf_topics` wt LEFT JOIN `wcf_forums` wf ON wf.id = wt.forum WHERE wt.`author` = :account ORDER BY wt.`id` DESC LIMIT 200;');
        $stmt->bindValue(':account', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            wc_activity_add($logs, 'forum', 'Forum topic created', $row['name'].' | Forum: '.(!empty($row['forum_name']) ? $row['forum_name'] : 'Unknown'), $row['added'], 'info');
        }
    } catch (Exception $e) {}
}

// Forum posts / replies
if (wc_activity_table_exists('wcf_posts')) {
    try {
        $stmt = $DB->prepare('SELECT wp.*, wt.name AS topic_name FROM `wcf_posts` wp LEFT JOIN `wcf_topics` wt ON wt.id = wp.topic WHERE wp.`author` = :account ORDER BY wp.`id` DESC LIMIT 200;');
        $stmt->bindValue(':account', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $deleted = ((int)$row['deleted_by'] > 0 ? 'deleted' : 'info');
            wc_activity_add($logs, 'forum', 'Forum reply posted', (!empty($row['topic_name']) ? $row['topic_name'] : $row['title']), $row['added'], $deleted);
        }
    } catch (Exception $e) {}
}

// Media uploads / avatars / screenshots if available
if (wc_activity_table_exists('images')) {
    try {
        $stmt = $DB->prepare('SELECT * FROM `images` WHERE `account` = :account ORDER BY `id` DESC LIMIT 200;');
        $stmt->bindValue(':account', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            wc_activity_add($logs, 'media', 'Media uploaded', $row['name'], $row['added'], ((int)$row['status'] == 1 ? 'approved' : 'pending'));
        }
    } catch (Exception $e) {}
}

usort($logs, function($a, $b) {
    if ($a['stamp'] == $b['stamp']) { return 0; }
    return ($a['stamp'] < $b['stamp']) ? 1 : -1;
});

$count = count($logs);
$totalPages = ($count > 0 ? ceil($count / $perPage) : 1);
if ($p > $totalPages) { $p = $totalPages; }
$offset = ($p - 1) * $perPage;
$pageLogs = array_slice($logs, $offset, $perPage);

function wc_activity_page_link($page, $label, $disabled = false)
{
    global $config;
    if ($disabled) {
        return '<li><span style="opacity:.45; cursor:default;">'.$label.'</span></li>';
    }
    return '<li><a href="'.$config['BaseURL'].'/index.php?page=activity&p='.$page.'">'.$label.'</a></li>';
}
?>

<div class="content_holder">

<div class="sub-page-title">
    <div id="title"><h1>Account Panel<p></p><span></span></h1></div>

    <div class="quick-menu">
        <a class="arrow" href="#"></a>
        <ul class="dropdown-qmenu">
            <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=store">Store</a></li>
            <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=teleporter">Teleporter</a></li>
            <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=buycoins">Buy Coins</a></li>
            <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=vote">Vote</a></li>
            <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=unstuck">Unstuck</a></li>
        </ul>
    </div>
</div>

    <div class="container_2 account" align="center">
     <div class="cont-image">

      <div class="container_3 account_sub_header">
         <div class="grad">
            <div class="page-title">Account activity</div>
            <a href="<?php echo $config['BaseURL'], '/index.php?page=account'; ?>">Back to account</a>
         </div>
      </div>

        <div class="account-activity">

            <div class="page-desc-holder">
                Here is your personal activity center. You can see where your rewards, Gold, Silver, purchases, posts and website actions come from.<br/>
                Only logs linked to your own account are displayed here.
            </div>

            <div class="container_3 account-wide" align="center">
                <ul class="activity-list">

                <?php
                if ($count > 0) {
                    foreach ($pageLogs as $log) {
                        $niceTime = date('d F Y, H:i:s', $log['stamp']);
                        echo '
                        <li>
                            <p id="r-title"><i>', wc_activity_escape($log['type']), '</i> <b>', wc_activity_escape($log['title']), '</b></p>
                            <p id="r-info">', wc_activity_escape($log['details']), (!empty($log['status']) ? ' <span style="opacity:.65;">['.wc_activity_escape($log['status']).']</span>' : ''), '</p>
                            <p id="ar-date">', wc_activity_escape($niceTime), '</p>
                        </li>';
                    }
                } else {
                    echo '<p class="there-is-nothing">There are no personal logs for your account yet.</p>';
                }
                ?>

                </ul>
            </div>

            <?php if ($count > $perPage) { ?>
            <div class="d-cont wide pagination-holder">
                <ul class="pagination" id="store-pagination">
                    <?php
                        echo wc_activity_page_link(1, 'First', ($p <= 1));
                        echo wc_activity_page_link(($p - 1), 'Previous', ($p <= 1));
                        echo '<li id="pages"><p>|&nbsp;&nbsp;</p>'.(($offset + 1)).'-'.min(($offset + $perPage), $count).' of '.$count.'<p>&nbsp;&nbsp;|</p></li>';
                        echo wc_activity_page_link(($p + 1), 'Next', ($p >= $totalPages));
                        echo wc_activity_page_link($totalPages, 'Last', ($p >= $totalPages));
                    ?>
                </ul>
                <div class="clear"></div>
            </div>
            <?php } ?>

        </div>
     </div>
    </div>
</div>

<?php
unset($logs, $pageLogs, $count, $p, $perPage, $offset, $totalPages);
$TPL->LoadFooter();
?>
