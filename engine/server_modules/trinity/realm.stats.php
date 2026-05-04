<?php
if (!defined('init_engine'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

class server_RealmStats
{
	private $realm = 0;
	private $realm_config;
	private $REALM_DB;
	private $uptimeRow;
	
	//constructor
	public function __construct()
	{
		return true;
	}
	
	//returns true if everything went successful while setting up the realm
	public function setRealm($id)
	{
		global $realms_config, $CORE;
		
		if (isset($realms_config[$id]))
		{
			//try to connect to the database
			if ($this->REALM_DB = $CORE->RealmDatabaseConnection($id))
			{
				//set some variables
				$this->realm = $id;
				$this->realm_config = $realms_config[$id];
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	
	public function prepareUptimeRow()
	{
		global $AUTH_DB;
			
		$res = $AUTH_DB->prepare("SELECT starttime, uptime FROM `uptime` WHERE `realmid` = :id ORDER BY starttime DESC LIMIT 1;");
		$res->bindParam(':id', $this->realm, PDO::PARAM_INT);
		$res->execute();
		
		if ($res->rowCount() > 0)
		{
			$this->uptimeRow = $res->fetch();
		}
		else
		{
			$this->uptimeRow = false;
		}
		unset($res);
		
		return true;
	}
	
    public function getStatus()
	{
		// First try the configured worldserver socket. This is more reliable on a local
		// AzerothCore setup than the old uptime-table calculation.
		$address = isset($this->realm_config['address']) ? $this->realm_config['address'] : '127.0.0.1';
		$port = isset($this->realm_config['port']) ? (int)$this->realm_config['port'] : 8085;
		$sock = @fsockopen($address, $port, $errno, $errstr, 0.5);
		if ($sock)
		{
			@fclose($sock);
			return 'online';
		}
		
		// Fallback: if characters are marked online, the realm is effectively online.
		try
		{
			$online = $this->getOnline();
			if ((int)$online['total'] > 0)
			{
				return 'online';
			}
		}
		catch (Exception $e) {}
		
		// Last fallback: old uptime table check.
		if (!$this->uptimeRow)
		{
			return 'offline';
		}
		$updateTime = isset($this->realm_config['UPDATE_TIME']) ? $this->realm_config['UPDATE_TIME'] : 600;
		if (!is_int($updateTime) && !ctype_digit((string)$updateTime))
		{
			$updateTime = strtotime($updateTime, 0);
		}
		$time = (int)$this->uptimeRow['starttime'] + (int)$this->uptimeRow['uptime'] + (int)$updateTime;
		return ($time < time()) ? 'offline' : 'online';
    }
    	
    public function getUptime()
    {	 
	 	$num = $this->uptimeRow ? (int)$this->uptimeRow['uptime'] : 0;
	 
      	$day = floor($num/86400);
      	$hours = floor(($num - $day*86400)/3600);
      	$minutes = floor(($num - $day*86400 - $hours*3600)/60);
	   
	  	if ($day <= 0 and $hours <= 0)
		{
       		$return = $minutes . ($minutes > 1 ? ' minutes' : ' minute');
		}
	  	else if ($day <= 0)
		{
       		$return = $hours . ($hours > 1 ? ' hours' : ' hour') . ' and ' . $minutes . ($minutes > 1 ? ' minutes' : ' minute');
		}
	  	else
		{
       		$return = $day . ($day > 1 ? ' days ' : ' day ') . $hours . ($hours > 1 ? ' hours' : ' hour') . ' and ' . $minutes.' min';
		}

     	return $return;
    }

	public function getOnline()
	{
		$columns = CORE_COLUMNS::get('characters');
					
		//count the Alliance
		$res = $this->REALM_DB->prepare("SELECT COUNT(".$columns['guid'].") AS a FROM `".$columns['self']."` WHERE `".$columns['online']."` = '1' AND `".$columns['race']."` IN (1, 3, 4, 7, 11, 22)");
		$res->execute();
		$allyRes = $res->fetch(PDO::FETCH_ASSOC);
		unset($res);
	
		//Count the Horde
		$res = $this->REALM_DB->prepare("SELECT COUNT(".$columns['guid'].") AS h FROM `".$columns['self']."` WHERE `".$columns['online']."` = '1' AND `".$columns['race']."` IN (2, 5, 6, 8, 9, 10)");
		$res->execute();
		$hordeRes = $res->fetch(PDO::FETCH_ASSOC);
		unset($res);

		//get the count
		$allyCount = $allyRes['a'];
		$hordeCount = $hordeRes['h'];
		$totalCount = $allyCount + $hordeCount;
		
		return array('total' => $totalCount, 'alliance' => $allyCount, 'horde' => $hordeCount);
	}
	
	public function GetRealmDetails()
	{
		// AzerothCore live statistics. The old Warcry realm_stats table is often stale,
		// so this page now reads directly from the characters table.
		$columns = CORE_COLUMNS::get('characters');
		$table = $columns['self'];
		$raceColumn = $columns['race'];
		$classColumn = $columns['class'];
		
		$details = array(
			'alliance' => 0, 'horde' => 0,
			'bloodelfs' => 0, 'draeneis' => 0, 'dwarfs' => 0, 'gnomes' => 0, 'humans' => 0, 'nightelfs' => 0, 'orcs' => 0, 'taurens' => 0, 'trolls' => 0, 'undeads' => 0,
			'deathknights' => 0, 'druids' => 0, 'hunters' => 0, 'mages' => 0, 'paladins' => 0, 'priests' => 0, 'rogues' => 0, 'shamans' => 0, 'warlocks' => 0, 'warriors' => 0
		);
		
		try
		{
			$res = $this->REALM_DB->prepare("SELECT `".$raceColumn."` AS race, `".$classColumn."` AS class, COUNT(*) AS total FROM `".$table."` GROUP BY `".$raceColumn."`, `".$classColumn."`");
			$res->execute();
			while ($row = $res->fetch(PDO::FETCH_ASSOC))
			{
				$count = (int)$row['total'];
				$race = (int)$row['race'];
				$class = (int)$row['class'];
				
				if (in_array($race, array(1,3,4,7,11), true)) $details['alliance'] += $count;
				if (in_array($race, array(2,5,6,8,10), true)) $details['horde'] += $count;
				
				switch ($race)
				{
					case 1: $details['humans'] += $count; break;
					case 2: $details['orcs'] += $count; break;
					case 3: $details['dwarfs'] += $count; break;
					case 4: $details['nightelfs'] += $count; break;
					case 5: $details['undeads'] += $count; break;
					case 6: $details['taurens'] += $count; break;
					case 7: $details['gnomes'] += $count; break;
					case 8: $details['trolls'] += $count; break;
					case 10: $details['bloodelfs'] += $count; break;
					case 11: $details['draeneis'] += $count; break;
				}
				
				switch ($class)
				{
					case 1: $details['warriors'] += $count; break;
					case 2: $details['paladins'] += $count; break;
					case 3: $details['hunters'] += $count; break;
					case 4: $details['rogues'] += $count; break;
					case 5: $details['priests'] += $count; break;
					case 6: $details['deathknights'] += $count; break;
					case 7: $details['shamans'] += $count; break;
					case 8: $details['mages'] += $count; break;
					case 9: $details['warlocks'] += $count; break;
					case 11: $details['druids'] += $count; break;
				}
			}
			unset($res);
		}
		catch (Exception $e)
		{
			return false;
		}
		
		return (object)$details;
	}
	
	public function __destruct()
	{
		unset($this->realm);
		unset($this->realm_config);
		$this->REALM_DB = NULL;
		unset($this->REALM_DB);		
	}
}