<?PHP
if (!defined('init_executes'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

//if the user is already logged in return him to index
if ($CURUSER->isOnline())
{
   header("Refresh: 0; url=".$config['BaseURL']."/index.php");
   exit();
}

//prepare multi errors
$ERRORS->NewInstance('login');
	
$username = (isset($_POST['username']) ? $_POST['username'] : false);
$password = (isset($_POST['password']) ? $_POST['password'] : false);
$rememberme = (isset($_POST['rememberme']) ? true : false);

if (isset($_POST['url_bl']))
{
	//check if it is valid URL
	if($CORE->ValidateURLBeforeLogin($_POST['url_bl']))
	{
		$_SESSION['url_bl'] = $_POST['url_bl'];
	}
	unset($_POST['url_bl']);
}

if (!$username)
{
	$ERRORS->Add("Please enter account name.");
}
if (!$password)
{
	$ERRORS->Add("Please enter account password.");
}

$ERRORS->Check('/index.php?page=login');

####################################################################
## The actual Login script begins here

	$usernameLookup = strtoupper(trim($username));

	// AzerothCore uses salt + verifier instead of sha_pass_hash.
	// Do not use CORE_COLUMNS here because older Warcry maps the password column to sha_pass_hash.
	$res = $AUTH_DB->prepare("SELECT * FROM `account` WHERE UPPER(`username`) = :username LIMIT 1");
	$res->bindParam(':username', $usernameLookup, PDO::PARAM_STR);
	$res->execute();

	if ($acc = $res->fetch(PDO::FETCH_ASSOC))
	{
		$accid = (int)$acc['id'];
		$accusername = $acc['username'];
		$accemail = isset($acc['email']) ? $acc['email'] : '';
		$accflags = isset($acc['expansion']) ? (int)$acc['expansion'] : (isset($acc['Flags']) ? (int)$acc['Flags'] : 2);

		// Validate password depending on the auth schema: AzerothCore SRP6 or legacy sha_pass_hash.
		if (server_Account::verifyPassword($acc, $password))
		{
			$sessionHash = server_Account::isAzerothCoreSchema() ? server_Account::makeSessionHashFromRow($acc) : (isset($acc['sha_pass_hash']) ? $acc['sha_pass_hash'] : server_Account::makehash($accusername, $password));
			$continue = false;

			if (!$CURUSER->logInfoAtLogin($accid))
			{
				if ($CURUSER->handle_MissingRecord($accid))
				{
					$continue = true;
				}
				else
				{
					$ERRORS->Add("The account exists in Auth, but the CMS profile could not be created. Please contact the administration.");
				}
			}
			else
			{
				$continue = true;
			}

			if ($continue)
			{
				$CURUSER->setLoggedIn($accid, $sessionHash);
				$_SESSION['JustLoggedIn'] = true;

				if ($rememberme)
				{
					$salt = uniqid(mt_rand(), true);
					$update = $DB->prepare("UPDATE `account_data` SET `salt` = :salt WHERE `id` = :acc LIMIT 1;");
					$update->bindParam(':acc', $accid, PDO::PARAM_INT);
					$update->bindParam(':salt', $salt, PDO::PARAM_STR);
					$update->execute();

					$newHash = sha1($sessionHash . $salt);
					$expire = strtotime('+1 month', time());
					$value = $accusername . '-' . $newHash;
					$CORE->setCookie('rmm', $value, $expire);
					unset($newHash, $expire, $value, $salt);
				}

	  		header("Location: " . $config['BaseURL'] . "/index.php?page=loginb");
				exit;
			}
		}
		else
		{
			$ERRORS->Add("You've entered wrong password.");
		}
	}
	else
	{
		$ERRORS->Add("The account you are trying to access does not exist.");
	}
	unset($res);

####################################################################

$ERRORS->Check('/index.php?page=login');

exit;