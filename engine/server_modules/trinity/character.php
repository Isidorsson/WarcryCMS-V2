<?php
if (!defined('init_engine'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

class server_Character
{
	private $realm = 0;
	private $realm_config;
	private $DB;
	//The debuff applied to a dead character
	private $deathDebuffId = '8326';
		
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
			if ($this->DB = $CORE->RealmDatabaseConnection($id))
			{
				//set some variables
				$this->realm = $id;
				$this->realm_config = $realms_config[$id];
				
				return true;
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
	
	public function getAccountCharacters()
	{
		global $CURUSER;
				
		$res = $this->DB->prepare("SELECT guid, name, level, race, class, gender FROM `characters` WHERE `account` = :account ORDER BY level;");
		$accountId = (int)$CURUSER->get('id');
			$res->bindParam(':account', $accountId, PDO::PARAM_INT);
		$res->execute();
				
		if ($res->rowCount() > 0)
		{
			return $res;
		}
		else
		{
			return false;
		}
	}
	
	public function FindHightestLevelCharacter($acc)
	{
		$res = $this->DB->prepare("SELECT guid, name, level, class FROM `characters` WHERE `account` = :account ORDER BY level DESC LIMIT 1;");
		$res->bindParam(':account', $acc, PDO::PARAM_INT);
		$res->execute();
		
		if ($res->rowCount() > 0)
		{
			$return = $res->fetch();
		}
		else
		{
			$return = false;
		}
		unset($res);
		
		return $return;
	}
	
	public function isMyCharacter($guid = false, $name = false, $account = false)
    {
		global $CURUSER;
		
		if ($guid === false and $name === false)
		{
			return false;
		}
		
		if (!$account)
			$account = $CURUSER->get('id');
		
		$res = $this->DB->prepare("SELECT guid, account FROM `characters` WHERE ".($guid === false ? "`name` = :name" : "`guid` = :guid")." AND `account` = :account LIMIT 1;");
		if ($guid !== false)
		{
			$res->bindParam(':guid', $guid, PDO::PARAM_INT);
		}
		else
		{
			$res->bindParam(':name', $name, PDO::PARAM_STR);
		}
		$res->bindParam(':account', $account, PDO::PARAM_INT);
		$res->execute();
		
		if ($res->rowCount() == 0)
			return false;
		  
      	return true;
    }

	public function getCharacterName($guid)
    {
		$res = $this->DB->prepare("SELECT name FROM `characters` WHERE `guid` = :guid LIMIT 1;");
		$res->bindParam(':guid', $guid, PDO::PARAM_INT);
		$res->execute();
		
		$row = $res->fetch(PDO::FETCH_ASSOC);
		unset($res);
		
    	if (!$row)
		{
      	  	return false;
		}
		  
      return $row['name'];
    }
	
	private function wcCharacterSelectColumn($key)
    {
        // Static allowlist: alias => real database column. No user-controlled identifier is accepted.
        $allowed = array(
            'account'       => '`account` AS `account`',
            'guid'          => '`guid` AS `guid`',
            'name'          => '`name` AS `name`',
            'honorPoints'   => '`totalHonorPoints` AS `honorPoints`',
            'killsLifeTime' => '`totalKills` AS `killsLifeTime`',
            'online'        => '`online` AS `online`',
            'level'         => '`level` AS `level`',
            'class'         => '`class` AS `class`',
            'race'          => '`race` AS `race`',
            'gender'        => '`gender` AS `gender`',
            'gold'          => '`money` AS `gold`',
        );

        return (isset($allowed[$key]) ? $allowed[$key] : false);
    }

	public function getCharacterData($guid = false, $name = false, $columns = false)
    {
		if ($guid === false and $name === false)
		{
			return false;
		}
		
        $selectColumns = array();

		//check if we wanna get multiple columns
		if (is_array($columns))
		{
			foreach ($columns as $key)
			{
                $safeColumn = $this->wcCharacterSelectColumn($key);
                if ($safeColumn !== false)
                {
                    $selectColumns[] = $safeColumn;
                }
			}
		}
		else
		{
            $safeColumn = $this->wcCharacterSelectColumn($columns);
            if ($safeColumn !== false)
            {
                $selectColumns[] = $safeColumn;
            }
		}

        if (count($selectColumns) === 0)
        {
            return false;
        }

        $queryColumns = implode(', ', $selectColumns);

        // WHERE clause is selected only by server-side logic; values remain bound parameters.
        if ($guid !== false)
        {
            $sql = 'SELECT '.$queryColumns.' FROM `characters` WHERE `guid` = :guid LIMIT 1;';
            $res = $this->DB->prepare($sql);
            $res->bindParam(':guid', $guid, PDO::PARAM_INT);
        }
        else
        {
            $sql = 'SELECT '.$queryColumns.' FROM `characters` WHERE `name` = :name LIMIT 1;';
            $res = $this->DB->prepare($sql);
            $res->bindParam(':name', $name, PDO::PARAM_STR);
        }
		$res->execute();
		
		$row = $res->fetch(PDO::FETCH_ASSOC);
		unset($res);
		
    	if (!$row)
		{
      	  	return false;
		}
		
		//free memory
		unset($queryColumns);
		unset($selectColumns);
		  
      return $row;
    }
	
	public function isCharacterOnline($guid)
    {		
		$res = $this->DB->prepare("SELECT guid, online FROM `characters` WHERE `guid` = :guid LIMIT 1");
		$res->bindParam(':guid', $guid, PDO::PARAM_INT);
		$res->execute();
		
		$row = $res->fetch(PDO::FETCH_ASSOC);
		unset($res);

    	if ($row['online'] == '1')
		{
      	  return true;
		}
		  
      return false;
    }
	
	public function characterHasMoney($guid, $cost)
    { 
		global $CURUSER;
		
		$account = $CURUSER->get('id');
		
		$res = $this->DB->prepare("SELECT guid, account, money FROM `characters` WHERE `guid` = :guid AND `account` = :account LIMIT 1");
		$res->bindParam(':guid', $guid, PDO::PARAM_INT);
		$res->bindParam(':account', $account, PDO::PARAM_INT);
		$res->execute();
		
		$row = $res->fetch(PDO::FETCH_ASSOC);
		unset($res);
	 
		if (!$row)
		{
	  		return false;
		}
		else if ($row['money'] < $cost)
		{
	  		return false;
		}
	 
      return true;
    }
	
	public function ResolveGuild($guid)
	{
		//get the column translation
		$GMcolumns = CORE_COLUMNS::get('guild_member');
		
		//find out if the char is a guild member
		$res = $this->DB->prepare("SELECT `".$GMcolumns['guildid']."` AS guildid, `".$GMcolumns['guid']."` AS guid FROM `".$GMcolumns['self']."` WHERE `".$GMcolumns['guid']."` = :guid LIMIT 1;");
		$res->bindParam(':guid', $guid, PDO::PARAM_INT);
		$res->execute();
		
		if ($res->rowCount() > 0)
		{
			//we are a member of a guild
			$row = $res->fetch();
			unset($res);
			
			//get the column translation
			$GuildColumns = CORE_COLUMNS::get('guild');
			
			//resolve the guild name
			$res2 = $this->DB->prepare("SELECT `".$GuildColumns['name']."` AS name FROM `".$GuildColumns['self']."` WHERE `".$GuildColumns['guildid']."` = :guild LIMIT 1;");
			$res2->bindParam(':guild', $row['guildid'], PDO::PARAM_INT);
			$res2->execute();
			
			//check if we have found it
			if ($res2->rowCount() > 0)
			{
				//fetch
				$row2 = $res2->fetch();
				unset($res2);
				
				//return both the name and guildid
				return array('guildid' => $row['guildid'], 'name' => $row2['name']);
			}
			else
			{
				return false;
			}
		}
		else
		{
			//we are not member of any guild
			return false;
		}
		unset($res);
		
		return false;
	}
	
	public function Teleport($guid, $coords)
	{
		//if the coords are passed in array
		if (is_array($coords))
		{
			$position_x = $coords['position_x'];
			$position_y = $coords['position_y'];
			$position_z = $coords['position_z'];
			$map = $coords['map'];
		}
		else
		{
			//else passed as string
			list($position_x, $position_y, $position_z, $map) = explode(',', $coords);
		}
				
		try
		{
			$guid = (int)$guid;
			$position_x = (float)$position_x;
			$position_y = (float)$position_y;
			$position_z = (float)$position_z;
			$map = (int)$map;

			$update_res = $this->DB->prepare("UPDATE `characters` SET `position_x` = :x, `position_y` = :y, `position_z` = :z, `map` = :map WHERE `guid` = :guid LIMIT 1;");
			$ok = $update_res->execute(array(
				':guid' => $guid,
				':x' => $position_x,
				':y' => $position_y,
				':z' => $position_z,
				':map' => $map,
			));

			if (!$ok)
			{
				unset($update_res);
				return false;
			}

			// MySQL/PDO can return rowCount() = 0 when the character was already on these coordinates
			// or when the driver does not report changed rows reliably. Verify the saved values instead.
			$verify = $this->DB->prepare("SELECT `position_x`, `position_y`, `position_z`, `map` FROM `characters` WHERE `guid` = :guid LIMIT 1;");
			$verify->execute(array(':guid' => $guid));
			$row = $verify->fetch(PDO::FETCH_ASSOC);
			unset($update_res);
			unset($verify);

			if (!$row)
			{
				return false;
			}

			return ((int)$row['map'] === $map
				&& abs((float)$row['position_x'] - $position_x) < 0.50
				&& abs((float)$row['position_y'] - $position_y) < 0.50
				&& abs((float)$row['position_z'] - $position_z) < 0.50);
		}
		catch (Throwable $e)
		{
			return false;
		}
	}
	
	//////// ///////////////////////////////////////////
	//// Use by name prefered, pass guid as false
	public function Unstuck($guid = false, $name = false)
	{
		global $CORE;

		try
		{
			if ($guid !== false)
			{
				$res = $this->DB->prepare("SELECT `guid`, `name`, `online` FROM `characters` WHERE `guid` = :guid LIMIT 1;");
				$res->bindParam(':guid', $guid, PDO::PARAM_INT);
				$res->execute();
			}
			else
			{
				$res = $this->DB->prepare("SELECT `guid`, `name`, `online` FROM `characters` WHERE `name` = :name LIMIT 1;");
				$res->bindParam(':name', $name, PDO::PARAM_STR);
				$res->execute();
			}

			$row = $res->fetch(PDO::FETCH_ASSOC);
			unset($res);

			if (!$row)
			{
				return false;
			}

			$guid = (int)$row['guid'];
			$name = $row['name'];
			$isOnline = ((int)$row['online'] === 1);

			// Online characters must use SOAP. If SOAP is not configured, return a clear error.
			if ($isOnline)
			{
				$CORE->ExecuteSoapCommand(".revive ".$name, $this->realm);
				$soap = $CORE->ExecuteSoapCommand(".tele name ".$name." \$home", $this->realm);
				return (is_array($soap) && isset($soap['sent']) && $soap['sent'] === true) ? true : 'Your character is online. Please fully log out from the game, then try again.';
			}

			// Offline fallback: revive and move the character to its hearthstone/homebind position directly in DB.
			$home = false;
			$homeQueries = array(
				"SELECT `mapId` AS map, `posX` AS x, `posY` AS y, `posZ` AS z FROM `character_homebind` WHERE `guid` = :guid LIMIT 1",
				"SELECT `map` AS map, `position_x` AS x, `position_y` AS y, `position_z` AS z FROM `character_homebind` WHERE `guid` = :guid LIMIT 1"
			);

			foreach ($homeQueries as $sql)
			{
				try
				{
					$st = $this->DB->prepare($sql);
					$st->execute(array(':guid' => $guid));
					$home = $st->fetch(PDO::FETCH_ASSOC);
					unset($st);
					if ($home)
					{
						break;
					}
				}
				catch (Throwable $e)
				{
					$home = false;
				}
			}

			if (!$home)
			{
				return 'Unable to find the character homebind location.';
			}

			$this->DB->beginTransaction();

			$auras = $this->DB->prepare("DELETE FROM `character_aura` WHERE `guid` = :guid AND `spell` = :spell");
			$auras->execute(array(':guid' => $guid, ':spell' => (int)$this->deathDebuffId));
			unset($auras);

			try
			{
				$corpse = $this->DB->prepare("DELETE FROM `corpse` WHERE `guid` = :guid");
				$corpse->execute(array(':guid' => $guid));
				unset($corpse);
			}
			catch (Throwable $e) {}

			$upd = $this->DB->prepare("UPDATE `characters` SET `map` = :map, `position_x` = :x, `position_y` = :y, `position_z` = :z, `death_expire_time` = 0 WHERE `guid` = :guid LIMIT 1");
			$upd->execute(array(
				':map' => (int)$home['map'],
				':x' => (float)$home['x'],
				':y' => (float)$home['y'],
				':z' => (float)$home['z'],
				':guid' => $guid,
			));
			unset($upd);

			$this->DB->commit();
			return true;
		}
		catch (Throwable $e)
		{
			if ($this->DB && $this->DB->inTransaction())
			{
				$this->DB->rollBack();
			}
			return $e->getMessage();
		}
	}

	public function getRaceString($id)
	{
		switch($id)
		{
			case 1:
				return 'Human';
				break;
			case 2:
				return 'Orc';
				break;
			case 3:
				return 'Dwarf';
				break;
			case 4:
				return 'Night Elf';
				break;
			case 5:
				return 'Undead';
				break;
			case 6:
				return 'Tauren';
				break;
			case 7:
				return 'Gnome';
				break;
			case 8:
				return 'Troll';
				break;
			case 9:
				return 'Goblin';
				break;
			case 10:
				return 'Blood Elf';
				break;
			case 11:
				return 'Draenei';
				break;
			case 22:
				return 'Worgen';
				break;
			default:
				return false;
				break;
		}
		
		return false;
	}
	
	public function getClassString($id)
	{
		switch($id)
		{
			case 1:
				return 'Warrior';
				break;
			case 2:
				return 'Paladin';
				break;
			case 3:
				return 'Hunter';
				break;
			case 4:
				return 'Rogue';
				break;
			case 5:
				return 'Priest';
				break;
			case 6:
				return 'Death Knight';
				break;
			case 7:
				return 'Shaman';
				break;
			case 8:
				return 'Mage';
				break;
			case 9:
				return 'Warlock';
				break;
			case 11:
				return 'Druid';
				break;
		}
		
		return false;
	}
	
	public function ResolveFaction($race)
	{
		switch($race)
		{
			case 1:
				return FACTION_ALLIANCE;
				break;
			case 2:
				return FACTION_HORDE;
				break;
			case 3:
				return FACTION_ALLIANCE;
				break;
			case 4:
				return FACTION_ALLIANCE;
				break;
			case 5:
				return FACTION_HORDE;
				break;
			case 6:
				return FACTION_HORDE;
				break;
			case 7:
				return FACTION_ALLIANCE;
				break;
			case 8:
				return FACTION_HORDE;
				break;
			case 9:
				return FACTION_HORDE;
				break;
			case 10:
				return FACTION_HORDE;
				break;
			case 11:
				return FACTION_ALLIANCE;
				break;
			case 22:
				return FACTION_ALLIANCE;
				break;
			default:
				return false;
				break;
		}
		
		return false;
	}
	
	public function __destruct()
	{
		unset($this->realm);
		unset($this->realm_config);
		$this->DB = NULL;
		unset($this->DB);		
	}
}