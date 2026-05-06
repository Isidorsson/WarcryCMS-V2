<?php
if (!defined('init_executes')) { header('HTTP/1.0 404 not found'); exit; }
if (!$CURUSER->isOnline()) { echo 'You must be logged in.'; die; }
if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_MEDIA_SREENSHOTS)) { echo 'You dont have the required permissions.'; die; }

$ERRORS->NewInstance('admin_add_screenshot');
$ERRORS->onSuccess('The screenshot was added successfully.', '/admin/index.php?page=screenshots');

function admin_ss_error($msg) { global $ERRORS; $ERRORS->Add($msg); $ERRORS->Check('/admin/index.php?page=screenshots'); exit; }
function admin_ss_safe_name($name) { $name = pathinfo($name, PATHINFO_FILENAME); $name = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $name); $name = trim($name, '_'); return $name !== '' ? substr($name,0,80) : 'screenshot'; }
function admin_ss_resize($src, $dest, $mime, $maxW, $maxH)
{
    switch ($mime) { case 'image/jpeg': $img=@imagecreatefromjpeg($src); break; case 'image/png': $img=@imagecreatefrompng($src); break; case 'image/gif': $img=@imagecreatefromgif($src); break; case 'image/webp': $img=function_exists('imagecreatefromwebp')?@imagecreatefromwebp($src):false; break; default: $img=false; }
    if (!$img) return false;
    $w=imagesx($img); $h=imagesy($img); if ($w<=0 || $h<=0) { imagedestroy($img); return false; }
    $scale=min($maxW/$w,$maxH/$h); if ($scale>1) $scale=1;
    $nw=max(1,(int)floor($w*$scale)); $nh=max(1,(int)floor($h*$scale));
    $canvas=imagecreatetruecolor($nw,$nh); imagealphablending($canvas,false); imagesavealpha($canvas,true);
    $transparent=imagecolorallocatealpha($canvas,0,0,0,127); imagefilledrectangle($canvas,0,0,$nw,$nh,$transparent);
    imagecopyresampled($canvas,$img,0,0,0,0,$nw,$nh,$w,$h);
    $ok=false; switch($mime){ case 'image/jpeg': $ok=imagejpeg($canvas,$dest,92); break; case 'image/png': $ok=imagepng($canvas,$dest,6); break; case 'image/gif': $ok=imagegif($canvas,$dest); break; case 'image/webp': $ok=function_exists('imagewebp')?imagewebp($canvas,$dest,92):false; break; }
    imagedestroy($canvas); imagedestroy($img); return $ok;
}
function admin_ss_reward($accountId, $amount, $source)
{
    global $DB;
    $amount = (int)$amount;
    $accountId = (int)$accountId;
    if ($accountId <= 0 || $amount <= 0) return;
    $accUpdate = $DB->prepare("UPDATE `account_data` SET `silver` = silver + :reward WHERE `id` = :id LIMIT 1;");
    $accUpdate->bindParam(':reward', $amount, PDO::PARAM_INT);
    $accUpdate->bindParam(':id', $accountId, PDO::PARAM_INT);
    $accUpdate->execute();
    if ($accUpdate->rowCount() > 0 && class_exists('CoinActivity'))
    {
        $ca = new CoinActivity($accountId);
        $ca->set_SourceType(CA_SOURCE_TYPE_REWARD);
        $ca->set_SourceString($source);
        $ca->set_CoinsType(CA_COIN_TYPE_SILVER);
        $ca->set_ExchangeType(CA_EXCHANGE_TYPE_PLUS);
        $ca->set_Amount($amount);
        $ca->execute();
        unset($ca);
    }
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$descr = isset($_POST['descr']) ? trim($_POST['descr']) : '';
$account = isset($_POST['account']) ? (int)$_POST['account'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : SCREENSHOT_STATUS_APPROVED;
$reward = isset($_POST['reward']) ? (int)$_POST['reward'] : 0;
if ($title === '') admin_ss_error('Please enter a screenshot title.');
if ($descr === '') $descr = 'Uploaded by staff.';
if (!in_array($status, array(SCREENSHOT_STATUS_PENDING, SCREENSHOT_STATUS_APPROVED, SCREENSHOT_STATUS_DENIED))) $status = SCREENSHOT_STATUS_APPROVED;
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) admin_ss_error('Please select a valid image file.');
if ($_FILES['file']['size'] <= 0 || $_FILES['file']['size'] > 12*1024*1024) admin_ss_error('The image is too large. Maximum size is 12 MB.');

$info=@getimagesize($_FILES['file']['tmp_name']);
if (!$info || empty($info['mime'])) admin_ss_error('The image file is invalid.');
$allowed=array('image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif');
if (function_exists('imagecreatefromwebp') && function_exists('imagewebp')) $allowed['image/webp']='webp';
$mime=strtolower($info['mime']);
if (!isset($allowed[$mime])) admin_ss_error('File type not allowed. Use JPG, PNG, GIF'.(isset($allowed['image/webp'])?' or WEBP':'').'.');

$dir=$config['RootPath'].'/uploads/media/screenshots'; $thumb=$dir.'/thumbs';
if (!is_dir($dir)) @mkdir($dir,0755,true); if (!is_dir($thumb)) @mkdir($thumb,0755,true);
if (!is_writable($dir) || !is_writable($thumb)) admin_ss_error('Screenshot upload folder is not writable.');
$name=admin_ss_safe_name($_FILES['file']['name']).'_'.time().'_'.mt_rand(1000,9999).'.'.$allowed[$mime];
$dirReal=realpath($dir); $thumbReal=realpath($thumb);
if ($dirReal === false || $thumbReal === false) admin_ss_error('Screenshot upload folder is invalid.');
$dest=$dirReal.DIRECTORY_SEPARATOR.basename($name); $tdest=$thumbReal.DIRECTORY_SEPARATOR.basename($name);
if (strpos($dest, $dirReal.DIRECTORY_SEPARATOR) !== 0 || strpos($tdest, $thumbReal.DIRECTORY_SEPARATOR) !== 0) admin_ss_error('Invalid upload path.');
if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) admin_ss_error('Upload failed. Check folder permissions.');
if (!admin_ss_resize($dest,$dest,$mime,1920,1080) || !admin_ss_resize($dest,$tdest,$mime,200,114)) { @unlink($dest); @unlink($tdest); admin_ss_error('The website failed to process this image.'); }

$time=$CORE->getTime(); $type=TYPE_SCREENSHOT;
$insert=$DB->prepare("INSERT INTO `images` (`name`, `descr`, `added`, `account`, `image`, `type`, `status`) VALUES (:name,:descr,:added,:account,:image,:type,:status);");
$insert->bindParam(':name',$title,PDO::PARAM_STR); $insert->bindParam(':descr',$descr,PDO::PARAM_STR); $insert->bindParam(':added',$time,PDO::PARAM_STR); $insert->bindParam(':account',$account,PDO::PARAM_INT); $insert->bindParam(':image',$name,PDO::PARAM_STR); $insert->bindParam(':type',$type,PDO::PARAM_INT); $insert->bindParam(':status',$status,PDO::PARAM_INT); $insert->execute();
if ($insert->rowCount()==0) { @unlink($dest); @unlink($tdest); admin_ss_error('The website failed to save the screenshot.'); }
if ($status == SCREENSHOT_STATUS_APPROVED && $reward > 0 && $account > 0) admin_ss_reward($account, $reward, 'Screenshot Winner');
$ERRORS->Check('/admin/index.php?page=screenshots');
$ERRORS->triggerSuccess();
exit;
