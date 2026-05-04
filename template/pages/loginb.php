<?php
if (!defined('init_pages'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

//check if we just had the login
if (!isset($_SESSION['JustLoggedIn']))
{
   header("Refresh: 0; url=".$config['BaseURL']."/index.php");
   exit();
}

$url = false;
//check if we have URL the user wanted to access before we ask to login
if (isset($_SESSION['url_bl']))
{
	//check if it is valid URL
	if($CORE->ValidateURLBeforeLogin($_SESSION['url_bl']))
	{
		$url = trim($_SESSION['url_bl']);
	}
	unset($_SESSION['url_bl']);
}

//default url
if (!$url)
{
	$url = $config['BaseURL'] . '/index.php';
}

//Set the title
$TPL->SetTitle('Sign In');
//Print the header
$TPL->LoadHeader();

?>

 <div class="sub-page-title">
  <div id="title"><h1>Login<p></p><span></span></h1></div>
 </div>
 
 <div class="container_2" align="center">
  <div class="vertical_center" align="center">
     
   <div class="container_3" align="center">
   		
        <div class="login-success">
            <h1>Login Successful</h1>
            <p>Please wait...</p>
        </div>
   
   </div>
   
  </div>
 </div>
 
<?php

	//Load the footer
	$TPL->LoadFooter();
	
	//Flush the page to the buffer
	$TPL->BufferFlush();
		
	####################################################################
	############ Find the account last vote date time ##################
	
	$res = $DB->prepare("SELECT * FROM `vote_data` WHERE `account` = :acc ORDER BY timestamp DESC LIMIT 1;");
	$res->bindParam(':acc', $CURUSER->get('id'), PDO::PARAM_INT);
	$res->execute();
	
	if ($res->rowCount() > 0)
	{
		$row = $res->fetch();
		$CURUSER->setLastVoteTime($row['timestamp']);
		unset($row);
	}
	unset($res);
	
	//unset the page pass
	unset($_SESSION['JustLoggedIn']);
	//redirect to the correct page
	echo '<meta http-equiv="refresh" content="1;URL=\'', $url, '\'">';
?>