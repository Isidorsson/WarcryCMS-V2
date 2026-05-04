<?PHP
if (!defined('init_executes'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$CORE->loggedInOrReturn();
$CORE->CheckPermissionsExecute(PERMISSION_MEDIA_MOVIES);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$wallDir = $config['RootPath'] . '/uploads/media/wallpapers';
$thumbDir = $wallDir . '/thumbs';
$manifestFile = $wallDir . '/wallpapers.json';

if (!is_dir($wallDir)) { @mkdir($wallDir, 0755, true); }
if (!is_dir($thumbDir)) { @mkdir($thumbDir, 0755, true); }

function wc_wallpapers_load_manifest($file)
{
    if (!file_exists($file)) { return array(); }
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : array();
}

function wc_wallpapers_save_manifest($file, $items)
{
    file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT));
}

function wc_wallpapers_safe_name($name)
{
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9\._-]+/i', '-', $name);
    $name = trim($name, '.-_');
    return $name == '' ? 'wallpaper' : $name;
}

function wc_wallpapers_make_thumb($source, $dest, $mime)
{
    if (!function_exists('imagecreatetruecolor')) { return copy($source, $dest); }
    if ($mime == 'image/jpeg' || $mime == 'image/pjpeg' || $mime == 'image/jpg') { $src = @imagecreatefromjpeg($source); }
    else if ($mime == 'image/png') { $src = @imagecreatefrompng($source); }
    else if ($mime == 'image/gif') { $src = @imagecreatefromgif($source); }
    else if ($mime == 'image/webp' && function_exists('imagecreatefromwebp')) { $src = @imagecreatefromwebp($source); }
    else { $src = false; }
    if (!$src) { return copy($source, $dest); }

    $sw = imagesx($src); $sh = imagesy($src);
    $tw = 280; $th = 158;
    $srcRatio = $sw / max(1, $sh);
    $thumbRatio = $tw / $th;
    if ($srcRatio > $thumbRatio) { $cropH = $sh; $cropW = (int)($sh * $thumbRatio); $cropX = (int)(($sw - $cropW) / 2); $cropY = 0; }
    else { $cropW = $sw; $cropH = (int)($sw / $thumbRatio); $cropX = 0; $cropY = (int)(($sh - $cropH) / 2); }

    $thumb = imagecreatetruecolor($tw, $th);
    imagecopyresampled($thumb, $src, 0, 0, $cropX, $cropY, $tw, $th, $cropW, $cropH);
    $ok = imagejpeg($thumb, $dest, 90);
    imagedestroy($src); imagedestroy($thumb);
    return $ok;
}

$ERRORS->NewInstance('wallpaper_media');
$ERRORS->onSuccess('Wallpaper media was updated successfully.', '/index.php?page=media');

if ($action == 'upload')
{
    if (!isset($_FILES['wallpaper']) || !is_uploaded_file($_FILES['wallpaper']['tmp_name']))
    {
        $ERRORS->Add('Please select a wallpaper image.');
    }
    else
    {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        if ($title == '') { $title = 'Wallpaper'; }

        $info = @getimagesize($_FILES['wallpaper']['tmp_name']);
        if (!$info) { $ERRORS->Add('The uploaded file is not a valid image.'); }
        else
        {
            $mime = image_type_to_mime_type($info[2]);
            $allowed = array('image/jpeg'=>'jpg','image/pjpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/gif'=>'gif');
            if (function_exists('imagecreatefromwebp')) { $allowed['image/webp'] = 'webp'; }
            if (!isset($allowed[$mime])) { $ERRORS->Add('Only JPG, PNG, GIF' . (function_exists('imagecreatefromwebp') ? ', WEBP' : '') . ' images are allowed.'); }
            if (filesize($_FILES['wallpaper']['tmp_name']) > 12 * 1024 * 1024) { $ERRORS->Add('The wallpaper is too big. Maximum size is 12 MB.'); }
        }
    }

    $ERRORS->Check('/index.php?page=media');

    $ext = $allowed[$mime];
    $base = wc_wallpapers_safe_name(pathinfo($_FILES['wallpaper']['name'], PATHINFO_FILENAME));
    $fileName = date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 6) . '-' . $base . '.' . $ext;
    $target = $wallDir . '/' . $fileName;
    $thumbName = 'thumb-' . pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
    $thumbTarget = $thumbDir . '/' . $thumbName;

    if (!move_uploaded_file($_FILES['wallpaper']['tmp_name'], $target))
    {
        $ERRORS->Add('Upload failed. Check folder permissions for /uploads/media/wallpapers.');
        $ERRORS->Check('/index.php?page=media');
    }

    wc_wallpapers_make_thumb($target, $thumbTarget, $mime);

    $items = wc_wallpapers_load_manifest($manifestFile);
    array_unshift($items, array(
        'title' => $title,
        'file' => $fileName,
        'thumb' => $thumbName,
        'width' => (int)$info[0],
        'height' => (int)$info[1],
        'created' => date('Y-m-d H:i:s')
    ));
    wc_wallpapers_save_manifest($manifestFile, $items);
    $ERRORS->triggerSuccess();
}
else if ($action == 'delete')
{
    $file = isset($_GET['file']) ? basename($_GET['file']) : '';
    if ($file == '') { $ERRORS->Add('Missing wallpaper file.'); }
    $ERRORS->Check('/index.php?page=media');

    $items = wc_wallpapers_load_manifest($manifestFile);
    $new = array();
    $thumb = '';
    foreach ($items as $item)
    {
        if (isset($item['file']) && $item['file'] == $file)
        {
            $thumb = isset($item['thumb']) ? basename($item['thumb']) : '';
            continue;
        }
        $new[] = $item;
    }
    if (file_exists($wallDir . '/' . $file)) { @unlink($wallDir . '/' . $file); }
    if ($thumb != '' && file_exists($thumbDir . '/' . $thumb)) { @unlink($thumbDir . '/' . $thumb); }
    wc_wallpapers_save_manifest($manifestFile, $new);
    $ERRORS->triggerSuccess();
}
else
{
    header('HTTP/1.0 404 not found');
    exit;
}
?>
