<?PHP
if (!defined('init_executes'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

// If the user is already logged in return him to index
if ($CURUSER->isOnline())
{
    header("Refresh: 0; url=".$config['BaseURL']."/admin/index.php");
    exit();
}


$ERRORS->NewInstance('login');

if (function_exists('warcry_csrf_verify') && !warcry_csrf_verify()) {
    $ERRORS->Add('Security token expired. Please try again.');
    $ERRORS->Check('/login.php');
    exit;
}

if (isset($_POST['panel_gate'])) {
    $code = isset($_POST['admin_panel_code']) ? (string)$_POST['admin_panel_code'] : '';
    if (function_exists('warcry_admin_panel_unlock') && warcry_admin_panel_unlock($code)) {
        header('Location: login.php');
        exit;
    }
    $ERRORS->Add('Invalid admin panel security code.');
    $ERRORS->Check('/login.php');
    exit;
}

if (function_exists('warcry_admin_require_panel_code')) {
    warcry_admin_require_panel_code();
}


$username = (isset($_POST['username']) ? trim($_POST['username']) : false);
$password = (isset($_POST['password']) ? $_POST['password'] : false);

if (isset($_POST['url_bl']))
{
    $_SESSION['url_bl'] = $_POST['url_bl'];
}

if (!$username)
{
    $ERRORS->Add("Please enter account name.");
}
if (!$password)
{
    $ERRORS->Add("Please enter account password.");
}

$ERRORS->Check('/login.php');

####################################################################
## AdminCP login compatible with AzerothCore salt/verifier and legacy sha_pass_hash

try
{
    // AzerothCore stores usernames uppercase. UPPER() also keeps old mixed-case DBs compatible.
    $usernameUpper = strtoupper($username);

    $res = $AUTH_DB->prepare("SELECT * FROM `account` WHERE UPPER(`username`) = :username LIMIT 1");
    $res->bindParam(':username', $usernameUpper, PDO::PARAM_STR);
    $res->execute();

    $account = $res->fetch(PDO::FETCH_ASSOC);
    unset($res);

    if (!$account)
    {
        $ERRORS->Add("Incorrect username or password.");
        $ERRORS->Check('/login.php');
        exit;
    }

    if (!server_Account::verifyPassword($account, $password))
    {
        $ERRORS->Add("Incorrect username or password.");
        $ERRORS->Check('/login.php');
        exit;
    }

    $accid = (int)$account['id'];

    // Check if the account is allowed to login into the admin panel.
    // Admin access is controlled by the CMS `acp_permissions` table.
    $perms = new Permissions($accid);

    if (!$perms->IsAllowedToUseACP())
    {
        $ERRORS->Add("This account does not have AdminCP permissions.");
        $ERRORS->Check('/login.php');
        exit;
    }

    // Session hash must match the same method used by server_Account::userCheck(true).
    if (server_Account::isAzerothCoreSchema())
    {
        $sessionHash = server_Account::makeSessionHashFromRow($account);
    }
    else
    {
        $sessionHash = isset($account['sha_pass_hash']) ? $account['sha_pass_hash'] : server_Account::makeHash($account['username'], $password);
    }

    // Make some logging
    $CURUSER->logInfoAtLogin($accid);

    // Login the user
    $CURUSER->setLoggedIn($accid, $sessionHash);

    // Check if we have URL the user wanted to access before we ask to login
    if (isset($_SESSION['url_bl']))
    {
        $url = trim($_SESSION['url_bl']);
        unset($_SESSION['url_bl']);
    }
    elseif (isset($_POST['url_bl']))
    {
        $url = trim($_POST['url_bl']);
    }
    else
    {
        $url = $config['BaseURL'] . '/admin/index.php?code=login_success';
    }

    header("Location: " . $url);
    exit;
}
catch (Exception $e)
{
    $logFile = dirname(__FILE__) . '/../../cache/admin_login_error.log';
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    $ERRORS->Add("Admin login failed. Check cache/admin_login_error.log.");
}

####################################################################

$ERRORS->Check('/login.php');
exit;
