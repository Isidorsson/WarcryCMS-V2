<?php
if (!defined('init_ajax'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

if (!$CURUSER->isOnline())
{
    echo '@AjaxError@, <br>You must be logged in.';
    die;
}

/**
 * Return a safe path inside $baseDir or false when the requested file tries to
 * escape the allowed directory. This prevents ../ path traversal checks too.
 */
function warcry_safe_path($baseDir, $fileName)
{
    $baseReal = realpath($baseDir);
    if ($baseReal === false) {
        return false;
    }

    $fileName = str_replace('\\', '/', (string)$fileName);
    $fileName = basename($fileName);

    if ($fileName === '' || $fileName === '.' || $fileName === '..') {
        return false;
    }

    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $fileName)) {
        return false;
    }

    return $baseReal . DIRECTORY_SEPARATOR . $fileName;
}

if (isset($_POST['checkFile']))
{
    $uploadDir = rtrim($config['RootPath'], '/\\') . '/admin/tempUploads/';
    $safePath = warcry_safe_path($uploadDir, isset($_POST['file']) ? $_POST['file'] : '');

    echo ($safePath !== false && is_file($safePath)) ? 1 : 0;
    exit;
}

$folder = rtrim($config['RootPath'], '/\\') . '/admin/tempUploads/';
$maxsize = 2097152;
$error = '@AjaxError@, <br>';

if (!isset($_FILES['file'])) {
    exit;
}

if (!is_dir($folder)) {
    @mkdir($folder, 0755, true);
}

$folderReal = realpath($folder);
if ($folderReal === false || !is_dir($folderReal)) {
    echo $error . 'Upload folder does not exist.';
    exit;
}

$file = $_FILES['file'];

if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
    echo $error . 'Invalid upload.';
    exit;
}

if (!empty($file['error'])) {
    switch ((int)$file['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $error .= 'The uploaded file exceeds the upload_max_filesize directive in php.ini<br>';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $error .= 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form<br>';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error .= 'The uploaded file was only partially uploaded<br>';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error .= 'No file was uploaded.<br>';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error .= 'Missing a temporary folder<br>';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error .= 'Failed to write file to disk<br>';
            break;
        case UPLOAD_ERR_EXTENSION:
            $error .= 'File upload stopped by extension<br>';
            break;
        default:
            $error .= 'No error code available<br>';
            break;
    }
}

if (filesize($file['tmp_name']) > $maxsize) {
    $error .= 'The file you are uploading is too big, 2mb max.<br>';
}

$mime = null;
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false || empty($imageInfo[2])) {
    $error .= 'File Type not allowed.<br>';
} else {
    $mime = image_type_to_mime_type($imageInfo[2]);
    $allowed = array(
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    );

    if (!isset($allowed[$mime])) {
        $error .= 'File Type not allowed.<br>';
    }

    // cross-check via finfo so getimagesize spoofing alone isn't enough
    if ($mime !== null && function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $finfoMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }
        if ($finfoMime !== false && $finfoMime !== $mime) {
            $error .= 'File Type not allowed.<br>';
        }
    }
}

if ($error !== '@AjaxError@, <br>') {
    echo $error;
    exit;
}

// Never trust or reuse the client-provided filename. Generate our own safe name.
$extension = $allowed[$mime];
$file_name = bin2hex(function_exists('random_bytes') ? random_bytes(12) : openssl_random_pseudo_bytes(12)) . '.' . $extension;

$uploadfile = warcry_safe_path($folderReal, $file_name);
if ($uploadfile === false || dirname($uploadfile) !== $folderReal) {
    echo $error . 'Invalid upload path.';
    exit;
}

if (!move_uploaded_file($file['tmp_name'], $uploadfile)) {
    $error .= 'Cannot upload the file.';

    if (!is_writable($folderReal)) {
        $error .= ' : Folder not writable.';
    }

    echo $error;
    exit;
}

@chmod($uploadfile, 0644);
echo $file_name;
