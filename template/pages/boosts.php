<?php
if (!defined('init_pages'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

$CORE->loggedInOrReturn();

//Predefine the realm id
$RealmId = $CURUSER->GetRealm();

//Set the title
$TPL->SetTitle('Boosts');
//Print the header
$TPL->LoadHeader();

$Boosts = new BoostsData();

?>
<div class="content_holder">

    <div class="sub-page-title">
        <div id="title"><h1>Account Panel<p></p><span></span></h1></div>
      
        <div class="quick-menu">
            <a class="arrow" href="#"></a>
            <ul class="dropdown-qmenu">
                <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=store">Store</a></li>
                <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=teleporter">Teleporter</a></li>
                <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=buycoins">Buy Coins</a></li>
                <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=vote">Vote</a></li>
                <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=pstore">Premium Store</a></li>
                <li><a href="<?php echo $config['BaseURL']; ?>/index.php?page=unstuck">Unstuck</a></li>
                <li id="messages-ddm">
                    <a href="<?php echo $config['BaseURL']; ?>/index.php?page=pm">
                        <b>55</b> <i>Private Messages</i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
 
  	<div class="container_2 account" align="center">
     	<div class="cont-image">
			
            <?php
			if ($error = $ERRORS->DoPrint('purchase_boost'))
			{
				echo $error, '<br><br>';
			}			
			if ($error = $ERRORS->successPrint('purchase_boost'))
			{
				echo $error, '<br><br>';
			}			
			unset($error);
			?>
            
            <div class="container_3 account_sub_header">
                <div class="grad">
                    <div class="page-title">Boosts</div>
                    <a href="<?php echo $config['BaseURL'], '/index.php?page=account'; ?>">Back to account</a>
                </div>
            </div>
          
            <div class="page-desc-holder">
                Boost auras applied to your account and are active on all <br/>of your characters.
                Some of the auras does not apply when you are in Battleground, Arena,<br/> Dungeon or Instance.
            </div>
          	
            <?php
				$ActiveBoosts = array();
				
				//Find the active boosts for this account/realm.
				// AzerothCore does not ship this custom CMS table, and older Warcry patches used
				// different column names. This block supports both schemas safely.
				if (!function_exists('warcry_boost_columns'))
				{
					function warcry_boost_columns(PDO $db)
					{
						$db->exec("CREATE TABLE IF NOT EXISTS `player_boosts` (
							`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
							`account_Id` INT UNSIGNED NOT NULL,
							`boosts` INT UNSIGNED NOT NULL,
							`setdate` INT UNSIGNED NOT NULL DEFAULT 0,
							`unsetdate` INT UNSIGNED NOT NULL DEFAULT 0,
							`active` TINYINT(1) NOT NULL DEFAULT 1,
							PRIMARY KEY (`id`),
							KEY `idx_account_active` (`account_Id`, `active`),
							KEY `idx_boost` (`boosts`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

						$cols = array();
						foreach ($db->query("SHOW COLUMNS FROM `player_boosts`") as $c)
						{
							$cols[$c['Field']] = true;
						}

						return array(
							'account' => isset($cols['account_Id']) ? 'account_Id' : (isset($cols['account']) ? 'account' : 'account_Id'),
							'boost'   => isset($cols['boosts']) ? 'boosts' : (isset($cols['boost']) ? 'boost' : 'boosts'),
							'set'     => isset($cols['setdate']) ? 'setdate' : null,
							'unset'   => isset($cols['unsetdate']) ? 'unsetdate' : (isset($cols['expire']) ? 'expire' : 'unsetdate'),
							'active'  => isset($cols['active']) ? 'active' : null
						);
					}
				}

				if ($RealmDb = $CORE->RealmDatabaseConnection($RealmId))
				{
					$bc = warcry_boost_columns($RealmDb);
					$whereActive = $bc['active'] ? " AND `".$bc['active']."` = '1'" : '';
					$orderBy = $bc['unset'] ? " ORDER BY `".$bc['unset']."` ASC" : '';
					$res = $RealmDb->prepare("SELECT * FROM `player_boosts` WHERE `".$bc['account']."` = :acc".$whereActive.$orderBy);
					$boostAccountId = (int)$CURUSER->get('id');
					$res->bindParam(':acc', $boostAccountId, PDO::PARAM_INT);
					$res->execute();
					
					while ($arr = $res->fetch())
					{
						$expires = isset($arr[$bc['unset']]) ? (int)$arr[$bc['unset']] : 0;
						$time = $CORE->getTime(true);
						if ($expires > 0 && $time->getTimestamp() > $expires)
						{
							continue;
						}
						$arr['boosts'] = isset($arr[$bc['boost']]) ? $arr[$bc['boost']] : 0;
						$arr['unsetdate'] = $expires;
						$ActiveBoosts[] = $arr;
					}
					unset($res);
				}
				unset($RealmDb);
			?>
            
            <!-- Boosts -->  
            <div class="container_3 account-wide" align="center">
                <div class="boosts_page">
                
                    <!-- Purchase Aura -->
                    <div class="purchase_boost">
                        
                        <div class="top_info">
                            Please select the boost you need, then select the period of time you want this aura to be active and then select the currency you want to pay with. 
                            You cant purchase boost that is already active on your account.
                        </div>
                        
                        <ul class="select_boost">
                            
                            <?php
							
							//Loop through our boosts
							foreach ($Boosts->data as $BoostId => $BoostData)
							{
								$isActive = false;
								foreach ($ActiveBoosts as $key => $bb)
								{
									if ((int)$bb['boosts'] == $BoostId)
									{
										$isActive = true;
										break;
									}
								}
								
								echo '
								<li ', ($isActive ? 'class="disabled"' : ''), '>
									<a href="#" data-boost-id="', $BoostId, '">
										<div class="icon" style="background-image:url(', $BoostData['icon'], ');"></div>
										<div class="info">
											<h2>', $BoostData['name'], '</h2>
											<h3>', $BoostData['description'], '</h3>
										</div>
										<p>This boost is already active!</p>
									</a>
								</li>';
							}
							
							?>
                            
                            <div class="clear"></div>
                        </ul>

                        <form method="post" action="<?php echo $config['BaseURL']; ?>/execute.php?take=purchase_boost" id="boosts-complete-form">
                            <div class="select-currency select-period" id="select-duration" align="right">
                                <span>Select boost duration</span>
                                <label class="label_radio"><div></div><input type="radio" name="duration" value="<?php echo BOOST_DURATION_10; ?>" checked="checked" /><p class="dr"><b>10</b> Days</p></label>
                                <label class="label_radio"><div></div><input type="radio" name="duration" value="<?php echo BOOST_DURATION_15; ?>" /><p class="dr"><b>15</b> Days</p></label>
                                <label class="label_radio"><div></div><input type="radio" name="duration" value="<?php echo BOOST_DURATION_30; ?>" /><p class="dr"><b>30</b> Days</p></label>
                            </div>

                            <input type="submit" value="Purchase" class="purchase_btn" />
                            
                            <div class="select-currency" id="select-currency">
                                <span>Currency:</span>
                                <label class="label_radio">
                                	<div></div>
                                    <input type="radio" name="currency" value="<?php echo CURRENCY_SILVER; ?>" data-price-value="<?php echo $config['BOOSTS']['PRICEING'][BOOST_DURATION_10][CURRENCY_SILVER]; ?>" />
                                    <p id="sc"><b id="price"><?php echo $config['BOOSTS']['PRICEING'][BOOST_DURATION_10][CURRENCY_SILVER]; ?></b> Silver Coins</p>
                                </label>
                                <label class="label_radio">
                                	<div></div>
                                    <input type="radio" name="currency" value="<?php echo CURRENCY_GOLD; ?>" checked="checked" data-price-value="<?php echo $config['BOOSTS']['PRICEING'][BOOST_DURATION_10][CURRENCY_GOLD]; ?>" />
                                    <p id="gc"><b id="price"><?php echo $config['BOOSTS']['PRICEING'][BOOST_DURATION_10][CURRENCY_GOLD]; ?></b> Gold Coins</p>
                                </label>
                            </div>
                        
                            <input type="hidden" name="boost" value="0" id="selected-boost-id" />
                        </form>

                        <div class="clear"></div>
                        
                    </div>
                    <!-- Purchase Aura.End -->
                        
                    <div class="active_boosts">
                        <h1>Active boosts</h1>
                        <ul class="active_boosts">
                        	<?php
							//Loop through the active boosts
							foreach ($ActiveBoosts as $key => $BoostRecord)
							{
								//Get the boost details
								$BoostDetails = $Boosts->get((int)$BoostRecord['boosts']);
								//Get the time left in single measure
								$timeLeft = $CORE->singleMeasureTimeLeft((int)$BoostRecord['unsetdate']);
								
								echo '
								<li>
									<div class="icon" style="background-image:url(', $BoostDetails['icon'], ');"></div>
									<p>', $timeLeft, ' left</p>
								</li>';
								
								unset($timeLeft, $BoostDetails);
							}
							unset($key, $BoostRecord, $ActiveBoosts);
							?>
                        </ul>
                    </div>
                    <div class="clear"></div>
                                 
                </div>
            </div>
            <!-- Boosts.End -->
    
        </div>
	</div>

</div>

<?php
	unset($Boosts);
	
	//Add some javascripts to the loader
	$TPL->AddFooterJs('template/js/page.boosts.js');
	//Print the footer
	$TPL->LoadFooter();
?>