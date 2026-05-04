<?PHP
if (!defined('init_executes'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

$CORE->loggedInOrReturn();

$CORE->load_CoreModule('coin.activity');

$siteid = (isset($_GET['site']) ? (int)$_GET['site'] : false);

//get the cooldown on this website
$cooldown = $CURUSER->getCooldown('votingsite'.$siteid);
$cooldownTime = $config['VOTE']['Cooldown'];

//points per vote ?
$pointsPerVote = $config['VOTE']['PPV'];
//ip check?
$ipCheck = $config['VOTE']['IP_CHECK'];

//vote sites data
$VoteData = new VoteSitesData();

//prepare multi errors
$ERRORS->NewInstance('vote');
//bind the onsuccess message
$ERRORS->onSuccess('Congratulation, you have recieved '.$pointsPerVote.' Silver coins.', '/index.php?page=vote');

if (!$siteid)
{
	$ERRORS->Add("Please select a valid voting website.");
}
if (!$voteSitesData = $VoteData->get($siteid))
{
	$ERRORS->Add("Please select a valid voting website.");
}
unset($VoteData);
//check the cooldown
if (time() < $cooldown)
{
	$ERRORS->Add("The voting website is on cooldown.");
}

$ERRORS->Check('/index.php?page=vote');

if ($ipCheck == true)
{
	$IPcooldown = $CURUSER->getVoteIPCooldown($siteid);
}

##############################################
### Script Start

	//add new record so we could later have statistics per month
	$insert = $DB->prepare("INSERT INTO `vote_data` (`account`, `siteid`, `timestamp`) VALUES (:acc, :site, :time);");
	$accId = (int)$CURUSER->get('id');
	$voteTime = $CORE->getTime();
	$insert->bindParam(':acc', $accId, PDO::PARAM_INT);
	$insert->bindParam(':site', $siteid, PDO::PARAM_INT);
	$insert->bindParam(':time', $voteTime, PDO::PARAM_STR);
	$insert->execute();	
	unset($insert);
	
	//Update counter
	$year = date('Y');
	$month = date('n');
	
	$insert = $DB->prepare("INSERT IGNORE INTO `votecounter` (`account`, `year`, `month`) VALUES (:acc, :year, :month);");
	$accId = (int)$CURUSER->get('id');
	$insert->bindParam(':acc', $accId, PDO::PARAM_INT);
	$insert->bindParam(':year', $year, PDO::PARAM_INT);
	$insert->bindParam(':month', $month, PDO::PARAM_INT);
	$insert->execute();
	unset($insert);
	
	$update = $DB->prepare("UPDATE `votecounter` SET `counter` = `counter` + 1 WHERE `account` = :acc AND `year` = :year AND `month` = :month LIMIT 1;");
	$accId = (int)$CURUSER->get('id');
	$update->bindParam(':acc', $accId, PDO::PARAM_INT);
	$update->bindParam(':year', $year, PDO::PARAM_INT);
	$update->bindParam(':month', $month, PDO::PARAM_INT);
	$update->execute();
	unset($update);
				
	
	//save the last vote time
	$CURUSER->setLastVoteTime($CORE->getTime());

	if($ipCheck == true and time() < $IPcooldown)
	{
		//set the cooldown
		$CURUSER->setCooldown('votingsite'.$siteid, strtotime('+'.$cooldownTime));
		$ERRORS->Add("The website failed to update your Silver coins. Reason: Someone has already voted from this IP.");
	}
	else
	{
		//update the user points
		$update = $DB->prepare("UPDATE `account_data` SET `silver` = silver + :points WHERE `id` = :acc LIMIT 1;");
		$accId = (int)$CURUSER->get('id');
	$update->bindParam(':acc', $accId, PDO::PARAM_INT);
		$update->bindParam(':points', $pointsPerVote, PDO::PARAM_INT);
		$update->execute();
			
		//check if the points ware updated
		if ($update->rowCount() > 0)
		{
			//log into coin activity
			$ca = new CoinActivity();
			$ca->set_SourceType(CA_SOURCE_TYPE_REWARD);
			$ca->set_SourceString($voteSitesData['name'] . ' Vote');
			$ca->set_CoinsType(CA_COIN_TYPE_SILVER);
			$ca->set_ExchangeType(CA_EXCHANGE_TYPE_PLUS);
			$ca->set_Amount($pointsPerVote);
			$ca->execute();
			unset($ca);
	
			//set the cooldown
			$CURUSER->setCooldown('votingsite'.$siteid, strtotime('+'.$cooldownTime));
			$ERRORS->triggerSuccess();
		}
		else
		{
			$ERRORS->Add("The website failed to update your Silver coins.");
		}
	unset($update);
	}

$ERRORS->Check('/index.php?page=vote');

exit;