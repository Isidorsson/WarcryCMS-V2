<?php
if (!defined('init_pages'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$CORE->loggedInOrReturn();
$TPL->SetTitle('Edit Bug Report');
$TPL->AddCSS('template/style/page-bugtracker-all.css');
$TPL->LoadHeader();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$accountId = (int)$CURUSER->get('id');

$stmt = $DB->prepare('SELECT `id`, `title`, `content`, `priority` FROM `bugtracker` WHERE `id` = :id AND `account` = :acc LIMIT 1');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':acc', $accountId, PDO::PARAM_INT);
$stmt->execute();
$report = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="content_holder">
    <div class="sub-page-title"><div id="title"><h1>Edit Bug Report<p></p><span></span></h1></div></div>
    <div class="container_2" align="center">
        <div class="holder-bugtracker-form container_3 account-wide bt-edit-form" align="left" style="padding:36px">
            <?php if (!$report) { ?>
                <div class="bt-empty-list">Report not found or you do not have access to it.</div>
                <br><a class="bt-back-link" href="<?php echo $config['BaseURL']; ?>/index.php?page=bugtracker">Back to Bug Tracker</a>
            <?php } else { ?>
                <h2>Edit Your Report</h2>
                <form method="post" action="<?php echo $config['BaseURL']; ?>/execute.php?take=update_bugreport">
                    <input type="hidden" name="id" value="<?php echo (int)$report['id']; ?>" />
                    <input name="title" type="text" value="<?php echo htmlspecialchars(stripslashes($report['title']), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Report title" style="margin:15px 0 15px 0; width:800px;" />
                    <textarea name="content" style="display:block; float:none; width:800px; height:300px; margin:0 0 15px 0;" placeholder="Describe the bug."><?php echo htmlspecialchars(stripslashes($report['content']), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <div class="select-priority">
                        <label class="label_radio"><div></div><input type="radio" name="priority" value="<?php echo BT_PRIORITY_LOW; ?>" <?php echo ((int)$report['priority'] === BT_PRIORITY_LOW ? 'checked="checked"' : ''); ?>/><p>Low Priority</p></label>
                        <label class="label_radio"><div></div><input type="radio" name="priority" value="<?php echo BT_PRIORITY_NORMAL; ?>" <?php echo ((int)$report['priority'] === BT_PRIORITY_NORMAL ? 'checked="checked"' : ''); ?>/><p>Normal Priority</p></label>
                        <label class="label_radio"><div></div><input type="radio" name="priority" value="<?php echo BT_PRIORITY_HIGH; ?>" <?php echo ((int)$report['priority'] === BT_PRIORITY_HIGH ? 'checked="checked"' : ''); ?>/><p>High Priority</p></label>
                    </div>
                    <input type="submit" value="Save Changes" />
                    <a class="bt-back-link" href="<?php echo $config['BaseURL']; ?>/index.php?page=bugtracker">Cancel</a>
                </form>
            <?php } ?>
        </div>
    </div>
</div>
<?php $TPL->LoadFooter(); ?>
