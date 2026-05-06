<?PHP
if (!defined('init_executes'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$CORE->loggedInOrReturn();
$CORE->CheckPermissionsExecute(PERMISSION_MEDIA_MOVIES);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$avatarDir = $config['RootPath'] . '/resources/avatars';
$manifestFile = $avatarDir . '/avatars.json';

if (!is_dir($avatarDir)) { @mkdir($avatarDir, 0755, true); }

function wc_avatars_load_manifest($file)
{
    if (!file_exists($file)) { return array(); }
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : array();
}

function wc_avatars_save_manifest($file, $items)
{
    file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT));
}

function wc_avatars_safe_name($name)
{
    $name = strtolower(basename((string)$name));
    $name = pathinfo($name, PATHINFO_FILENAME);
    $name = preg_replace('/[^a-z0-9_-]+/i', '-', $name);
    $name = trim($name, '-_');
    if ($name === '') { $name = 'avatar'; }
    return substr($name, 0, 80);
}

function wc_avatars_next_id($items)
{
    $max = 1000;
    foreach ($items as $item)
    {
        if (isset($item['id']) && (int)$item['id'] > $max) { $max = (int)$item['id']; }
    }
    return $max + 1;
}

$ERRORS->NewInstance('avatar_media');
$ERRORS->onSuccess('Avatar gallery was updated successfully.', '/index.php?page=avatars');

$allowedRanks = array(RANK_ROOKIE, RANK_PARTICIPANT, RANK_MEMBER, RANK_VETERAN, RANK_SENIOR_MEMBER, RANK_ADDICT, RANK_STAFF_MEMBER);

if ($action == 'upload')
{
    $rank = isset($_POST['rank']) ? (int)$_POST['rank'] : RANK_ROOKIE;
    if (!in_array($rank, $allowedRanks)) { $ERRORS->Add('Invalid avatar category.'); }

    if (!isset($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name']))
    {
        $ERRORS->Add('Please select an avatar image.');
    }
    else
    {
        $info = @getimagesize($_FILES['avatar']['tmp_name']);
        if (!$info) { $ERRORS->Add('The uploaded file is not a valid image.'); }
        else
        {
            $mime = image_type_to_mime_type($info[2]);
            $allowed = array('image/jpeg'=>'jpg','image/pjpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/gif'=>'gif');
            if (function_exists('imagecreatefromwebp')) { $allowed['image/webp'] = 'webp'; }
            if (!isset($allowed[$mime])) { $ERRORS->Add('Only JPG, PNG, GIF' . (function_exists('imagecreatefromwebp') ? ', WEBP' : '') . ' images are allowed.'); }
            if (filesize($_FILES['avatar']['tmp_name']) > 5 * 1024 * 1024) { $ERRORS->Add('The avatar is too big. Maximum size is 5 MB.'); }

            //cross-check via finfo so getimagesize spoofing alone isn't enough
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $finfoMime = $finfo ? finfo_file($finfo, $_FILES['avatar']['tmp_name']) : false;
                if ($finfo) finfo_close($finfo);
                if ($finfoMime !== false && !isset($allowed[$finfoMime])) {
                    $ERRORS->Add('File content does not match an allowed image type.');
                }
            }
        }
    }

    $ERRORS->Check('/index.php?page=avatars');

    $items = wc_avatars_load_manifest($manifestFile);
    $id = wc_avatars_next_id($items);
    $ext = $allowed[$mime];
    $base = wc_avatars_safe_name(pathinfo($_FILES['avatar']['name'], PATHINFO_FILENAME));
    $fileName = 'custom_avatar_' . $id . '-' . bin2hex(random_bytes(4)) . '-' . $base . '.' . $ext;
    $avatarDirReal = realpath($avatarDir);
    if ($avatarDirReal === false) { $ERRORS->Add('Avatar folder is invalid.'); $ERRORS->Check('/index.php?page=avatars'); }
    $target = $avatarDirReal . DIRECTORY_SEPARATOR . basename($fileName);
    if (strpos($target, $avatarDirReal . DIRECTORY_SEPARATOR) !== 0) { $ERRORS->Add('Invalid avatar upload path.'); $ERRORS->Check('/index.php?page=avatars'); }

    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target))
    {
        $ERRORS->Add('Upload failed. Check folder permissions for /resources/avatars.');
        $ERRORS->Check('/index.php?page=avatars');
    }

    $items[] = array(
        'id' => $id,
        'rank' => $rank,
        'file' => $fileName,
        'created' => date('Y-m-d H:i:s')
    );
    wc_avatars_save_manifest($manifestFile, $items);
    $ERRORS->triggerSuccess();
}
else if ($action == 'delete')
{
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { $ERRORS->Add('Missing avatar ID.'); }
    $ERRORS->Check('/index.php?page=avatars');

    $items = wc_avatars_load_manifest($manifestFile);
    $new = array();
    $file = '';
    foreach ($items as $item)
    {
        if (isset($item['id']) && (int)$item['id'] == $id)
        {
            $file = isset($item['file']) ? basename($item['file']) : '';
            continue;
        }
        $new[] = $item;
    }

    $avatarDirReal = realpath($avatarDir);
    if ($file != '' && $avatarDirReal !== false) {
        $deletePath = realpath($avatarDirReal . DIRECTORY_SEPARATOR . basename($file));
        if ($deletePath !== false && strpos($deletePath, $avatarDirReal . DIRECTORY_SEPARATOR) === 0 && file_exists($deletePath)) { @unlink($deletePath); }
    }
    wc_avatars_save_manifest($manifestFile, $new);
    $ERRORS->triggerSuccess();
}
else
{
    header('HTTP/1.0 404 not found');
    exit;
}
?>
