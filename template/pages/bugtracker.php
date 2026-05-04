<?php
if (!defined('init_pages'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$TPL->SetTitle('Bug Tracker');
$TPL->AddCSS('template/style/page-bugtracker-all.css');
$TPL->LoadHeader();

$statusApproved = BT_APP_STATUS_APPROVED;

function bt_clean($value) {
    return htmlspecialchars(stripslashes((string)$value), ENT_QUOTES, 'UTF-8');
}
function bt_status_name($status) {
    switch ((int)$status) {
        case BT_STATUS_NEW: return 'New';
        case BT_STATUS_OPEN: return 'Open';
        case BT_STATUS_ONHOLD: return 'On hold';
        case BT_STATUS_DUPLICATE: return 'Duplicate';
        case BT_STATUS_INVALID: return 'Invalid';
        case BT_STATUS_WONTFIX: return 'Won\'t fix';
        case BT_STATUS_RESOLVED: return 'Resolved';
        default: return 'Unknown';
    }
}
function bt_priority_name($priority) {
    switch ((int)$priority) {
        case BT_PRIORITY_LOW: return 'Low';
        case BT_PRIORITY_HIGH: return 'High';
        case BT_PRIORITY_NORMAL:
        default: return 'Normal';
    }
}
function bt_approval_name($approval) {
    switch ((int)$approval) {
        case BT_APP_STATUS_APPROVED: return 'Approved';
        case BT_APP_STATUS_DECLINED: return 'Declined';
        default: return 'Pending';
    }
}
function bt_main_category_name($cat) {
    switch ((int)$cat) {
        case BT_CAT_WEBSITE: return 'Website';
        case BT_CAT_WOTLK_CORE: return 'WotLK Core';
        default: return 'Unknown';
    }
}
function bt_report_category_name($mainCategory, $categoryId, $subCategoryId) {
    $category = 'Unknown';
    try {
        $store = new BTCategories();
        $main = $store->getMainCategory((int)$mainCategory);
        if ($main) {
            $catObj = $main->getCategory((int)$categoryId);
            if ($catObj) {
                $category = $catObj->getName();
                if ($catObj->hasSubCategories() && (int)$subCategoryId > 0) {
                    $sub = $catObj->getSubCategoryName((int)$subCategoryId);
                    if ($sub) {
                        $category .= ' - '.$sub;
                    }
                }
            }
        }
    } catch (Exception $e) {}
    return $category;
}

$totalStmt = $DB->prepare('SELECT COUNT(*) FROM `bugtracker`');
$totalStmt->execute();
$total = (int)$totalStmt->fetchColumn();

$approvedStmt = $DB->prepare('SELECT COUNT(*) FROM `bugtracker` WHERE `approval` = :status');
$approvedStmt->bindValue(':status', $statusApproved, PDO::PARAM_INT);
$approvedStmt->execute();
$countApproved = (int)$approvedStmt->fetchColumn();

$userReports = array();
$userTotal = 0;
$userApproved = 0;

if ($CURUSER->isOnline()) {
    $accountId = (int)$CURUSER->get('id');

    $stmt = $DB->prepare('SELECT COUNT(*) FROM `bugtracker` WHERE `account` = :acc');
    $stmt->bindValue(':acc', $accountId, PDO::PARAM_INT);
    $stmt->execute();
    $userTotal = (int)$stmt->fetchColumn();

    $stmt = $DB->prepare('SELECT COUNT(*) FROM `bugtracker` WHERE `account` = :acc AND `approval` = :status');
    $stmt->bindValue(':acc', $accountId, PDO::PARAM_INT);
    $stmt->bindValue(':status', $statusApproved, PDO::PARAM_INT);
    $stmt->execute();
    $userApproved = (int)$stmt->fetchColumn();

    $stmt = $DB->prepare('SELECT `id`, `title`, `content`, `maincategory`, `category`, `subcategory`, `added`, `status`, `priority`, `approval` FROM `bugtracker` WHERE `account` = :acc ORDER BY `id` DESC LIMIT 25');
    $stmt->bindValue(':acc', $accountId, PDO::PARAM_INT);
    $stmt->execute();
    $userReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="content_holder">
    <div class="sub-page-title">
        <div id="title"><h1>Bug Tracker<p></p><span></span></h1></div>
    </div>

    <div class="container_2 bugtracker-clean" align="center">
        <div class="bugs-search-bar container_3">
            <form method="get" action="<?php echo $config['BaseURL']; ?>/index.php" class="bt-search-form">
                <input type="text" placeholder="Search..." name="q" />
                <select styled="styled" id="search-category" name="mainCategory">
                    <option value="<?php echo BT_CAT_WEBSITE; ?>">Website</option>
                    <option value="<?php echo BT_CAT_WOTLK_CORE; ?>" selected="selected">WotLK Core</option>
                </select>
                <input type="hidden" name="search" value="1" />
                <input type="hidden" name="page" value="bugtracker-search" />
                <input type="submit" value="Search" />
            </form>
        </div>

        <div class="holder-bugtracker">
            <div class="bt-stats-row">
                <div class="bug-reports-holder reports">
                    <h1><?php echo $total; ?></h1>
                    <h3>Submitted Reports</h3>
                </div>
                <div class="bug-reports-holder confirmed">
                    <h1><?php echo $countApproved; ?></h1>
                    <h3>Approved Reports</h3>
                </div>
                <a href="<?php echo $config['BaseURL']; ?>/index.php?page=bugtracker_submit" class="submit-bug-report">
                    <div class="plus-ico"><div id="partone"></div><div id="parttwo"></div></div>
                    <h1>Submit Report</h1>
                </a>
            </div>

            <?php if ($CURUSER->isOnline()) { ?>
                <div class="bugs-submited-by-me">
                    You have submitted <b><?php echo $userTotal; ?></b> bug reports <span>(<?php echo $userApproved; ?> approved)</span>
                </div>

                <div class="all-reports-by-me bt-my-reports">
                    <?php if ($userTotal > 0 && count($userReports) > 0) { ?>
                        <ul class="reports" id="report-container">
                            <?php foreach ($userReports as $report) { 
                                $rid = (int)$report['id'];
                                $approvalClass = strtolower(bt_approval_name($report['approval']));
                            ?>
                                <li class="bt-report-card <?php echo $approvalClass; ?>">
                                    <div class="bt-report-head">
                                        <div>
                                            <strong><?php echo bt_clean($report['title']); ?></strong>
                                            <p><?php echo bt_clean(bt_main_category_name($report['maincategory'])); ?> / <?php echo bt_clean(bt_report_category_name($report['maincategory'], $report['category'], $report['subcategory'])); ?></p>
                                        </div>
                                        <span class="bt-badge <?php echo $approvalClass; ?>"><?php echo bt_clean(bt_approval_name($report['approval'])); ?></span>
                                    </div>
                                    <div class="bt-report-meta">
                                        <span>Status: <?php echo bt_clean(bt_status_name($report['status'])); ?></span>
                                        <span>Priority: <?php echo bt_clean(bt_priority_name($report['priority'])); ?></span>
                                    </div>
                                    <p class="bt-report-content"><?php echo nl2br(bt_clean($report['content'])); ?></p>
                                    <div class="bt-report-actions">
                                        <a href="<?php echo $config['BaseURL']; ?>/index.php?page=bugtracker_edit&id=<?php echo $rid; ?>">Edit</a>
                                        <form method="post" action="<?php echo $config['BaseURL']; ?>/execute.php?take=delete_bugreport" onsubmit="return confirm('Delete this bug report?');">
                                            <input type="hidden" name="id" value="<?php echo $rid; ?>" />
                                            <button type="submit">Delete</button>
                                        </form>
                                    </div>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="bt-empty-list">You do not have any bug reports yet.</div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="bugs-submited-by-me">Please login to view, edit, or delete your own bug reports.</div>
            <?php } ?>

            <div class="bug-tracker-info">
                <h3><font color="#c7962c">Bug Tracker Guidelines</font></h3><br/>
                <b><font color="#79736a">We highly appreciate your efforts to report any problems you may discover on our site or ingame. In order to process and resolve all reported bugs, we ask you to follow the guidelines below.</font></b><br/><br/>
                <font color="#656059">
                    - Please search before submitting anything to our bug tracker. It's possible someone else has already reported the bug in question.<br/>
                    - Use proper titles. E.g. the name of the quest, NPC or Item you may have problems with.<br/>
                    - What is wrong? E.g. What happens and what is supposed to happen.<br/>
                    - Add anything else you think might be useful for us to know.<br/><br/>
                </font>
                <i><font color="#79736a">Please follow these guidelines and you'll make us work much easier. In return, we'll reward you with Silver Coins for each approved report.</font></i>
            </div>
        </div>
    </div>
</div>
<?php $TPL->LoadFooter(); ?>
