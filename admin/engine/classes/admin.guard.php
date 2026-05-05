<?php
if (!defined('init_engine')) {
    header('HTTP/1.0 404 not found');
    exit;
}

function warcry_admin_public_index_url()
{
    global $config;
    return rtrim($config['BaseURL'], '/') . '/index.php';
}

function warcry_admin_redirect_public()
{
    header('Location: ' . warcry_admin_public_index_url());
    exit;
}

function warcry_admin_panel_config_file()
{
    global $config;
    return rtrim($config['RootPath'], '/\\') . '/configuration/Admin_panel.php';
}

function warcry_admin_panel_config()
{
    $admin_panel_config = array('enabled' => true, 'code_hash' => '');
    $file = warcry_admin_panel_config_file();
    if (file_exists($file)) {
        include $file;
    }
    return is_array($admin_panel_config) ? $admin_panel_config : array('enabled' => true, 'code_hash' => '');
}

function warcry_admin_panel_is_unlocked()
{
    $cfg = warcry_admin_panel_config();
    if (empty($cfg['enabled'])) return true;
    return !empty($_SESSION['WARCRY_ADMIN_PANEL_UNLOCKED']) && $_SESSION['WARCRY_ADMIN_PANEL_UNLOCKED'] === true;
}

function warcry_admin_panel_unlock($code)
{
    $cfg = warcry_admin_panel_config();
    $hash = isset($cfg['code_hash']) ? (string)$cfg['code_hash'] : '';
    if ($hash !== '' && password_verify((string)$code, $hash)) {
        $_SESSION['WARCRY_ADMIN_PANEL_UNLOCKED'] = true;
        return true;
    }
    return false;
}

function warcry_admin_require_panel_code()
{
    if (!warcry_admin_panel_is_unlocked()) {
        header('Location: login.php');
        exit;
    }
}

function warcry_admin_save_panel_code($newCode)
{
    $newCode = trim((string)$newCode);
    if (strlen($newCode) < 4 || strlen($newCode) > 128) {
        return false;
    }
    $hash = password_hash($newCode, PASSWORD_DEFAULT);
    $content = "<?php
/**
 * Warcry Admin Panel Gate
 * Stored as a one-way password_hash. It cannot be decrypted, only replaced.
 */
if (!defined('init_engine')) {
    header('HTTP/1.0 404 not found');
    exit;
}

\$admin_panel_config = array(
    'enabled' => true,
    'code_hash' => '" . str_replace("'", "\'", $hash) . "',
);
";
    $file = warcry_admin_panel_config_file();
    return @file_put_contents($file, $content, LOCK_EX) !== false;
}

function warcry_admin_is_allowed_account($accountId)
{
    $accountId = (int)$accountId;
    if ($accountId <= 0) return false;
    if (!class_exists('Permissions')) {
        $permFile = dirname(__FILE__) . '/permissions.php';
        if (file_exists($permFile)) require_once $permFile;
    }
    if (class_exists('Permissions')) {
        $perms = new Permissions($accountId);
        return $perms->IsAllowedToUseACP();
    }
    return false;
}

function warcry_admin_require_panel_access()
{
    global $CURUSER;
    warcry_admin_require_panel_code();
    if (!$CURUSER || !$CURUSER->isOnline()) {
        warcry_admin_redirect_public();
    }
    $id = (int)$CURUSER->get('id');
    if ($id <= 0 || !warcry_admin_is_allowed_account($id)) {
        $_SESSION = array();
        warcry_admin_redirect_public();
    }
}
?>
