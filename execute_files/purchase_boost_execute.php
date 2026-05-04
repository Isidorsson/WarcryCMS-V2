<?php
if (!defined('init_executes'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

//load the register module
$CORE->load_CoreModule('accounts.finances');
$CORE->load_CoreModule('purchaseLog');

$ERRORS->NewInstance('purchase_boost');
$ERRORS->onSuccess('Your Boosts have been successfully applied, please re-log.', '/index.php?page=boosts');

$RealmId = $CURUSER->GetRealm();
$BoostId = isset($_POST['boost']) ? (int)$_POST['boost'] : false;
$currency = isset($_POST['currency']) ? (int)$_POST['currency'] : false;
$DurationId = isset($_POST['duration']) ? (int)$_POST['duration'] : false;

$finance = new AccountFinances();
$logs = new purchaseLog();
$BoostsStorage = new BoostsData();

function warcry_boost_columns_exec(PDO $db)
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

if (!$BoostId)
{
    $ERRORS->Add('Please select boost first.');
}
else if (!($BoostDetails = $BoostsStorage->get($BoostId)))
{
    $ERRORS->Add('The selected boost is invalid.');
}

if (!$currency)
{
    $ERRORS->Add('Please select a currency for the purchase.');
}
else if (!$finance->IsValidCurrency($currency))
{
    $ERRORS->Add('Error, invalid currency selected.');
}

if (!$DurationId)
{
    $ERRORS->Add('Please select boost duration.');
}
else if (!in_array($DurationId, array(BOOST_DURATION_10, BOOST_DURATION_15, BOOST_DURATION_30)))
{
    $ERRORS->Add('The selected boost duration is invalid.');
}

$ERRORS->Check('/index.php?page=boosts');

$finance->SetCurrency($currency);
$finance->SetAmount($config['BOOSTS']['PRICEING'][$DurationId][$currency]);

if ($BalanceError = $finance->CheckBalance())
{
    if (is_array($BalanceError))
    {
        foreach ($BalanceError as $currencyName)
        {
            $ERRORS->Add("You do not have enough " . ucfirst($currencyName) . " Coins.");
        }
    }
    else
    {
        $ERRORS->Add('Error, the website failed to verify your account balance.');
    }
}
unset($BalanceError);
$ERRORS->Check('/index.php?page=boosts');

$time = $CORE->getTime(true);
$nowTs = (int)$time->getTimestamp();
$logs->add('BOOSTS', 'Starting log session for the Boost Purchase service. Using currency: '.$currency.' and duration: '.$DurationId.', selected realm: '.$RealmId.'.', 'pending');

if ($RealmDB = $CORE->RealmDatabaseConnection($RealmId))
{
    $bc = warcry_boost_columns_exec($RealmDB);
    $boostAccountId = (int)$CURUSER->get('id');

    $res = $RealmDB->prepare("SELECT * FROM `player_boosts` WHERE `".$bc['account']."` = :acc AND `".$bc['boost']."` = :boost LIMIT 1;");
    $res->bindParam(':acc', $boostAccountId, PDO::PARAM_INT);
    $res->bindParam(':boost', $BoostId, PDO::PARAM_INT);
    $res->execute();

    if ($res->rowCount() > 0)
    {
        $arr = $res->fetch(PDO::FETCH_ASSOC);
        $existingExpire = isset($arr[$bc['unset']]) ? (int)$arr[$bc['unset']] : 0;
        $existingActive = $bc['active'] ? (int)$arr[$bc['active']] : 1;

        if ($existingActive && $existingExpire > $nowTs)
        {
            $ERRORS->Add('The selected boost is already active, please wait until it has expired.');
            $logs->update(false, 'The selected boost is already active.', 'error');
        }
        else
        {
            $delete = $RealmDB->prepare("DELETE FROM `player_boosts` WHERE `".$bc['account']."` = :acc AND `".$bc['boost']."` = :boost LIMIT 1;");
            $delete->bindParam(':acc', $boostAccountId, PDO::PARAM_INT);
            $delete->bindParam(':boost', $BoostId, PDO::PARAM_INT);
            $delete->execute();
        }
    }
    unset($res);

    $ERRORS->Check('/index.php?page=boosts');

    $DurationStrings = array(
        BOOST_DURATION_10 => '10 days',
        BOOST_DURATION_15 => '15 days',
        BOOST_DURATION_30 => '30 days'
    );
    $Expires = $nowTs + strtotime($DurationStrings[$DurationId], 0);

    $columns = array('`'.$bc['account'].'`', '`'.$bc['boost'].'`', '`'.$bc['unset'].'`');
    $values = array(':acc', ':boost', ':expire');
    if ($bc['set']) { $columns[] = '`'.$bc['set'].'`'; $values[] = ':setdate'; }
    if ($bc['active']) { $columns[] = '`'.$bc['active'].'`'; $values[] = '1'; }

    $insert = $RealmDB->prepare("INSERT INTO `player_boosts` (".implode(',', $columns).") VALUES (".implode(',', $values).");");
    $insert->bindParam(':acc', $boostAccountId, PDO::PARAM_INT);
    $insert->bindParam(':boost', $BoostId, PDO::PARAM_INT);
    $insert->bindParam(':expire', $Expires, PDO::PARAM_INT);
    if ($bc['set']) { $insert->bindParam(':setdate', $nowTs, PDO::PARAM_INT); }
    $insert->execute();

    if ($insert->rowCount() > 0)
    {
        $logs->update(false, 'The boost has been inserted with expire time: '.$Expires.' ['.$DurationStrings[$DurationId].'].', 'pending');
        $Charge = $finance->Charge("Purchased Boost", CA_SOURCE_TYPE_PURCHASE);
        if ($Charge === true)
        {
            $logs->update(false, 'The user has been charged for the purchase.', 'ok');
        }
        else
        {
            $logs->update(false, 'The user was not charged for the purchase, website failed to update.', 'error');
        }
        unset($finance);
        $ERRORS->triggerSuccess();
    }
    else
    {
        $ERRORS->Add('The website failed to set your boost. Please contact the administration.');
        $logs->update(false, 'The website failed to insert the boost record.', 'error');
    }
    unset($insert, $DurationStrings);
}
else
{
    $ERRORS->Add("The website failed to connect to the server. Please contact the administration.");
}
unset($RealmDB);

$ERRORS->Check('/index.php?page=boosts');
exit;
