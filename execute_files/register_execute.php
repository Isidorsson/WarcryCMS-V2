<?php
if (!defined('init_executes'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

//setup new instance of multiple errors
$ERRORS->NewInstance('register');

//load the register module
$CORE->load_CoreModule('accounts.register');
$CORE->load_CoreModule('email.reservation');
$CORE->load_CoreModule('text.captcha');

$captcha = new TextCaptcha();

//Define the variables
$username = isset($_POST['username']) ? $_POST['username'] : false;

$displayName = isset($_POST['displayname']) ? $_POST['displayname'] : false;

$password = isset($_POST['password']) ? $_POST['password'] : false;
$password2 = isset($_POST['password2']) ? $_POST['password2'] : false;

$email = isset($_POST['email']) ? $_POST['email'] : false;

$birthdayMonth = isset($_POST['birthday']['month']) ? $_POST['birthday']['month'] : false;
$birthdayDay = isset($_POST['birthday']['day']) ? $_POST['birthday']['day'] : false;
$birthdayYear = isset($_POST['birthday']['year']) ? $_POST['birthday']['year'] : false;

$country = isset($_POST['country']) ? $_POST['country'] : false;

$secretQuestion = isset($_POST['secretQuestion']) ? (int)$_POST['secretQuestion'] : false;
$secretAnswer = isset($_POST['secretAnswer']) ? $_POST['secretAnswer'] : false;

$rafHash = false; // Recruit-a-Friend disabled.

//missing inputs check
######################################
######## USERNAME CHECK ##############
	if ($usernameError = AccountsRegister::checkUsername($username))
	{
		$ERRORS->Add($usernameError);
	}
	
$username = trim($username);

######################################
###### DISPLAY NAME CHECK ############
	if ($displaynameError = AccountsRegister::checkDisplayname($displayName))
	{
		$ERRORS->Add($displaynameError);
	}
	
######################################
######## PASSWORD CHECK ##############
	if ($passwordError = AccountsRegister::checkPassword($password, $password2))
	{
		$ERRORS->Add($passwordError);
	}
	
$password = trim($password);

######################################
######### EMAIL CHECK ################
	if ($emailError = AccountsRegister::checkEmail($email))
	{
		$ERRORS->Add($emailError);
	}
	else
	{
		//check for reservation
		if (EmailReservations::IsReserved(array('email' => $email)) === true)
		{
			$ERRORS->Add('The e-mail address is reserved.');
		}
	}
	
$email = trim($email);

######################################
######### BIRTHDAY Check #############
	//validate the Month
	if ($birthdayMonthError = AccountsRegister::checkBirthdayMonth($birthdayMonth))
	{
		$ERRORS->Add($birthdayMonthError);
	}
	
	//validate the Day
	if ($birthdayDayError = AccountsRegister::checkBirthdayDay($birthdayDay))
	{
		$ERRORS->Add($birthdayDayError);
	}

	//validate the Year
	if ($birthdayYearError = AccountsRegister::checkBirthdayYear($birthdayYear))
	{
		$ERRORS->Add($birthdayYearError);
	}

//add zero "0" to the day if it's not aready entered
$dayLen = strlen($birthdayDay);
if (($dayLen >= 1 and $dayLen <= 2) and ($birthdayDay >= 1 and $birthdayDay <= 31))
{
	if ($dayLen == 1)
	{
		$birthdayDay = '0' . $birthdayDay;
	}
}

//merge the birthday
$birthday = $birthdayMonth . '/' . $birthdayDay . '/' . $birthdayYear;

######################################
######### Country Check ##############
	if ($countryError = AccountsRegister::checkCountry($country))
	{
		$ERRORS->Add($countryError);
	}

######################################
## Secret Question & Answer Check ####
	if ($secretQuestionError = AccountsRegister::checkSecretQuestion($secretQuestion))
	{
		$ERRORS->Add($secretQuestionError);
	}
	
	if ($secretAnswerError = AccountsRegister::checkSecretAnswer($secretAnswer))
	{
		$ERRORS->Add($secretAnswerError);
	}

$secretAnswer = trim($secretAnswer);

######################################
######### Text Captcha Check #########
	/*
	if ($CaptchaResponseField = $captcha->GetResponseFieldName())
	{
		$CaptchaResponse = isset($_POST[$CaptchaResponseField]) ? $_POST[$CaptchaResponseField] : false;
		//check if it was filled in
		if (!$CaptchaResponse)
		{
			$ERRORS->Add('Please answer the Human Test question.');
		}
		else if (!$captcha->CheckAnswer($CaptchaResponse))
		{
			$ERRORS->Add('You have failed to answer the Human Test question.');
		}
	}
	else
	{
		$ERRORS->Add('There was a problem with the Human Test.');
	}
	//kill the captcha session
	$captcha->Kill();
	//free up some mem
	unset($CaptchaResponseField, $CaptchaResponse, $captcha);
	*/
//Check for errors
$ERRORS->Check('/index.php?page=register');

##################################################
######## REGISTER SERVER ACCOUNT #################

//some default variables
$expansion = 2;
$recruiter = 0;

	// Recruit-a-Friend disabled: new accounts are no longer attached to a recruiter.
	
	//register
  	if ($accountId = server_Account::register($username, $password, $email, $expansion, $recruiter))
  	{
		//unset the terms variable
		unset($_SESSION['TermsAccepted']);
		
		//Get visitor's IP Address
		$ip = $SECURITY->getip();
		$thetime = $CORE->getTime();
		$regStatus = 'active';
	  	
		//hash the secret answer
		$aHash = sha1($secretQuestion . ':' . strtolower($secretAnswer));

		//insert web record
		// Warcry account_data has NOT NULL columns without defaults on some MySQL/WAMP installs.
		// Include safe defaults so the CMS profile is created at the same time as the AzerothCore auth account.
		$webSalt = sha1(uniqid(mt_rand(), true));
		$selectedRealm = 1;
		if ($displayName == '') { $displayName = $username; }
		if ($country == '') { $country = 'US'; }
		if ($birthday == '') { $birthday = ''; }
		$gender = '';
		$empty = '';
		$zero = 0;
		$adminIp = '0.0.0.0';
		$event = 'NONE';
		$insert = $DB->prepare("REPLACE INTO `account_data`
		(`id`, `displayName`, `silver`, `gold`, `cooldowns`, `socialData`, `birthday`, `gender`, `country`, `secretQuestion`, `secretAnswer`, `avatar`, `avatarType`, `rank`, `last_ip`, `admin_last_ip`, `reg_ip`, `last_login`, `last_login2`, `admin_last_login`, `admin_last_login2`, `status`, `event`, `salt`, `selected_realm`, `bt_milestone`)
		VALUES
		(:accid, :displayName, 0, 0, :empty1, :empty2, :birthday, :gender, :country, :secretQuestion, :secretAnswer, :empty3, 0, 0, :lastip, :adminip, :regip, :lastlogin, :lastlogin2, :adminlogin, :adminlogin2, :status, :event, :websalt, :selectedrealm, 0);");
		$insert->bindParam(':accid', $accountId, PDO::PARAM_INT);
		$insert->bindParam(':displayName', $displayName, PDO::PARAM_STR);
		$insert->bindParam(':empty1', $empty, PDO::PARAM_STR);
		$insert->bindParam(':empty2', $empty, PDO::PARAM_STR);
		$insert->bindParam(':birthday', $birthday, PDO::PARAM_STR);
		$insert->bindParam(':gender', $gender, PDO::PARAM_STR);
		$insert->bindParam(':country', $country, PDO::PARAM_STR);
		$insert->bindParam(':secretQuestion', $secretQuestion, PDO::PARAM_INT);
		$insert->bindParam(':secretAnswer', $aHash, PDO::PARAM_STR);
		$insert->bindParam(':empty3', $empty, PDO::PARAM_STR);
		$insert->bindParam(':lastip', $ip, PDO::PARAM_STR);
		$insert->bindParam(':adminip', $adminIp, PDO::PARAM_STR);
		$insert->bindParam(':regip', $ip, PDO::PARAM_STR);
		$insert->bindParam(':lastlogin', $thetime, PDO::PARAM_STR);
		$insert->bindParam(':lastlogin2', $thetime, PDO::PARAM_STR);
		$insert->bindParam(':adminlogin', $thetime, PDO::PARAM_STR);
		$insert->bindParam(':adminlogin2', $thetime, PDO::PARAM_STR);
		$insert->bindParam(':status', $regStatus, PDO::PARAM_STR);
		$insert->bindParam(':event', $event, PDO::PARAM_STR);
		$insert->bindParam(':websalt', $webSalt, PDO::PARAM_STR);
		$insert->bindParam(':selectedrealm', $selectedRealm, PDO::PARAM_INT);
		if (!$insert->execute())
		{
			$info = $insert->errorInfo();
			@file_put_contents($config['RootPath'] . '/cache/azerothcore_register_error.log', '[' . date('Y-m-d H:i:s') . '] CMS account_data insert failed: ' . implode(' | ', $info) . PHP_EOL, FILE_APPEND);
			$ERRORS->Add('Account was created in Auth, but the CMS profile could not be created. Check cache/azerothcore_register_error.log.');
			$ERRORS->Check('/index.php?page=register');
		}
				######################################
		############ MAILING #################
		$CORE->load_CoreModule('phpmailer');
		
		//setup the PHPMailer class
		$mail = new PHPMailer();
		$mail->IsMail();
		$mail->From = $config['Email'];
		$mail->FromName =  'Warcry WoW - Info';
		$mail->AddAddress($email);
		
		//get the message html
		$message = file_get_contents($config['RootPath'] . '/resources/mails/register_mail.html');
				
		//break if the function failed to laod HTML
		if ($message)
		{				
			//replace the tags with info
			$search = array('{USERNAME}', '{DISPLAYNAME}', '{PASSWORD}');
			$replace = array($username, $displayName, $password);
			$message = str_replace($search, $replace, $message);
			
			$mail->WordWrap = 50;
			$mail->IsHTML(true);
			
			$mail->Subject = "Warcry WoW Registration";
			$mail->Body    = $message;
			//$mail->AltBody = "This is the body in plain text for non-HTML mail clients";

	  		$mail->Send();
		}

		######################################
		############# LOGIN ##################
		$accountRowRes = $AUTH_DB->prepare("SELECT * FROM `account` WHERE `id` = :id LIMIT 1");
		$accountRowRes->bindParam(':id', $accountId, PDO::PARAM_INT);
		$accountRowRes->execute();
		$accountRow = $accountRowRes->fetch(PDO::FETCH_ASSOC);
		$loginHash = server_Account::isAzerothCoreSchema() ? server_Account::makeSessionHashFromRow($accountRow) : server_Account::makeHash($username, $password);
		$CURUSER->setLoggedIn($accountId, $loginHash);
		
		//unset
		
		//Setup our welcoming notification
		$NOTIFICATIONS->SetTitle('Notification');
		$NOTIFICATIONS->SetHeadline('Congratulation!');
		$NOTIFICATIONS->SetText('Welcome and thank you for joining the Warcry community.<br>Your Warcry account has been automatically activated.<br>Please enjoy.');
		$NOTIFICATIONS->SetTextAlign('center');
		//$NOTIFICATIONS->SetAutoContinue(true);
		//$NOTIFICATIONS->SetContinueDelay(5);
		$NOTIFICATIONS->Apply();
		
		######################################
		########## Redirect ##################
		header("Location: ".$config['BaseURL']."/index.php?page=home");
  	}
	else
	{
		$details = method_exists('server_Account', 'getLastRegisterError') ? server_Account::getLastRegisterError() : '';
		if ($details != '')
		{
			$ERRORS->Add('Account creation failed: ' . htmlspecialchars($details, ENT_QUOTES, 'UTF-8'));
		}
		else
		{
			$ERRORS->Add('Website Failure, it seems the website is not functioning at the moment. If this problem persists please contact the administration.');
		}
	}

//unset

$ERRORS->Check('/index.php?page=register');

exit;