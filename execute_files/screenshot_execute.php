<?php
if (!defined('init_executes'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$CORE->loggedInOrReturn();
$ERRORS->NewInstance('screenshots');
$ERRORS->onSuccess('The screenshot was successfully uploaded. It is now waiting for staff approval.', '/index.php?page=upload-screenshot');

function warcry_screenshot_fail($message)
{
    global $ERRORS;
    $ERRORS->Add($message);
    $ERRORS->Check('/index.php?page=upload-screenshot');
    exit;
}

function warcry_screenshot_safe_name($name)
{
    $name = pathinfo($name, PATHINFO_FILENAME);
    $name = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $name);
    $name = trim($name, '_');
    if ($name === '') { $name = 'screenshot'; }
    return substr($name, 0, 80);
}

function warcry_screenshot_save_resized($src, $dest, $mime, $maxW, $maxH)
{
    switch ($mime)
    {
        case 'image/jpeg': $img = @imagecreatefromjpeg($src); break;
        case 'image/png':  $img = @imagecreatefrompng($src); break;
        case 'image/gif':  $img = @imagecreatefromgif($src); break;
        case 'image/webp': $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false; break;
        default: $img = false;
    }
    if (!$img) { return false; }

    $w = imagesx($img);
    $h = imagesy($img);
    if ($w <= 0 || $h <= 0) { imagedestroy($img); return false; }

    $scale = min($maxW / $w, $maxH / $h);
    if ($scale > 1) { $scale = 1; }
    $newW = max(1, (int)floor($w * $scale));
    $newH = max(1, (int)floor($h * $scale));

    $canvas = imagecreatetruecolor($newW, $newH);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $newW, $newH, $transparent);
    imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);

    $ok = false;
    switch ($mime)
    {
        case 'image/jpeg': $ok = imagejpeg($canvas, $dest, 92); break;
        case 'image/png':  $ok = imagepng($canvas, $dest, 6); break;
        case 'image/gif':  $ok = imagegif($canvas, $dest); break;
        case 'image/webp': $ok = function_exists('imagewebp') ? imagewebp($canvas, $dest, 92) : false; break;
    }
    imagedestroy($canvas);
    imagedestroy($img);
    return $ok;
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$descr = isset($_POST['descr']) ? trim($_POST['descr']) : '';

if ($title === '') { warcry_screenshot_fail('Please fill in the title field.'); }
if ($descr === '') { warcry_screenshot_fail('Please fill in the description field.'); }
if (!isset($_FILES['file']) || !is_array($_FILES['file'])) { warcry_screenshot_fail('Please select a screenshot to upload.'); }
if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) { warcry_screenshot_fail('The upload failed. Please try another image.'); }

$maxBytes = 12 * 1024 * 1024;
if ($_FILES['file']['size'] <= 0 || $_FILES['file']['size'] > $maxBytes) { warcry_screenshot_fail('The image is too large. Maximum size is 12 MB.'); }

$tempFile = $_FILES['file']['tmp_name'];
$info = @getimagesize($tempFile);
if ($info === false || empty($info['mime'])) { warcry_screenshot_fail('The image file is invalid. Please upload a real JPG, PNG, GIF or WEBP image.'); }

$allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif');
if (function_exists('imagecreatefromwebp') && function_exists('imagewebp')) { $allowed['image/webp'] = 'webp'; }
$mime = strtolower($info['mime']);
if (!isset($allowed[$mime])) { warcry_screenshot_fail('File type not allowed. Please upload JPG, PNG, GIF' . (isset($allowed['image/webp']) ? ' or WEBP' : '') . '.'); }

$file_path = $config['RootPath'] . '/uploads/media/screenshots';
$thumb_path = $file_path . '/thumbs';
if (!is_dir($file_path)) { @mkdir($file_path, 0755, true); }
if (!is_dir($thumb_path)) { @mkdir($thumb_path, 0755, true); }
if (!is_writable($file_path) || !is_writable($thumb_path)) { warcry_screenshot_fail('The screenshot folder is not writable. Please check uploads/media/screenshots permissions.'); }

$imageName = warcry_screenshot_safe_name($_FILES['file']['name']) . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $allowed[$mime];
$filePathReal = realpath($file_path);
$thumbPathReal = realpath($thumb_path);
if ($filePathReal === false || $thumbPathReal === false) { warcry_screenshot_fail('The screenshot folder path is invalid.'); }
$file_src_new = $filePathReal . DIRECTORY_SEPARATOR . basename($imageName);
$file_src_new_thumb = $thumbPathReal . DIRECTORY_SEPARATOR . basename($imageName);
if (strpos($file_src_new, $filePathReal . DIRECTORY_SEPARATOR) !== 0 || strpos($file_src_new_thumb, $thumbPathReal . DIRECTORY_SEPARATOR) !== 0) { warcry_screenshot_fail('Invalid upload path.'); }

if (!move_uploaded_file($tempFile, $file_src_new)) { warcry_screenshot_fail('The website failed to upload your screenshot. Please check folder permissions.'); }

// Normalize/save the original and create a clean thumbnail without relying on the old EXIF/ImageManipulation class.
if (!warcry_screenshot_save_resized($file_src_new, $file_src_new, $mime, 1920, 1080))
{
    @unlink($file_src_new);
    warcry_screenshot_fail('The image file is invalid. Please try another JPG or PNG image.');
}
if (!warcry_screenshot_save_resized($file_src_new, $file_src_new_thumb, $mime, 200, 114))
{
    @unlink($file_src_new);
    warcry_screenshot_fail('The website failed to create the screenshot thumbnail.');
}

$time = $CORE->getTime();
$type = TYPE_SCREENSHOT;
$status = SCREENSHOT_STATUS_PENDING;

$insert = $DB->prepare("INSERT INTO `images` (`name`, `descr`, `added`, `account`, `image`, `type`, `status`) VALUES (:name, :descr, :added, :account, :image, :type, :status);");
$insert->bindParam(':name', $title, PDO::PARAM_STR);
$insert->bindParam(':descr', $descr, PDO::PARAM_STR);
$insert->bindParam(':added', $time, PDO::PARAM_STR);
$insert->bindParam(':account', $CURUSER->get('id'), PDO::PARAM_INT);
$insert->bindParam(':image', $imageName, PDO::PARAM_STR);
$insert->bindParam(':type', $type, PDO::PARAM_INT);
$insert->bindParam(':status', $status, PDO::PARAM_INT);
$insert->execute();

if ($insert->rowCount() == 0)
{
    @unlink($file_src_new);
    @unlink($file_src_new_thumb);
    warcry_screenshot_fail('The website failed to save your screenshot.');
}

$ERRORS->Check('/index.php?page=upload-screenshot');
$ERRORS->triggerSuccess();
exit;
