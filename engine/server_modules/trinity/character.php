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
	
	public function getCharacterData($guid = false, $name = false, $columns = false)
    {
		if ($guid === false and $name === false)
		{
			return false;
		}
		
		$columnsData = CORE_COLUMNS::get('characters');
		//empty string
		$queryColumns = "";
		
		//check if we wanna get multiple columns
		if (is_array($columns))
		{
			foreach ($columns as $key)
			{
				//check if it's valid key
				if (isset($columnsData[$key]))
				{
					$queryColumns .= "`" . $columnsData[$key] . "` AS " . $key . ", ";
				}
			}
			//check if the query has any valid columns at all
			if ($queryColumns != "")
			{
				//remove the last "," symbol from the query
				$queryColumns = substr($queryColumns, 0, strlen($queryColumns) - 2);
			}
			else
				return false;
		}
		else
		{
			//check if the column is valid
			if (isset($columnsData[$columns]))
				$queryColumns = "`" . $columnsData[$columns] . "` AS " . $columns;
			else
				return false;
		}
		
		$res = $this->DB->prepare("SELECT ". $queryColumns . " FROM `characters` WHERE ".($guid === false ? "`name` = :name" : "`guid` = :guid")." LIMIT 1;");
		if ($guid !== false)
		{
			$res->bindParam(':guid', $guid, PDO::PARAM_INT);
		}
		else
		{
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
		unset($columnsData);
		  
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
		
		if ($guid !== false)
		{
			//get the player name
			$res = $this->DB->prepare("SELECT name FROM `characters` WHERE `guid` = :guid LIMIT 1;");
			$res->bindParam(':guid', $guid, PDO::PARAM_INT);
			$res->execute();
		
			$row = $res->fetch(PDO::FETCH_ASSOC);
			unset($res);
	 
			if (!$row)
			{
	  			return false;
			}
			else
			{
				$name = $row['name'];
			}
		}

		//try reviving the character aswell
 		$CORE->ExecuteSoapCommand(".revive ".$name, $this->realm);
 		/* Old Style
		$revive_res = $this->DB->prepare("DELETE FROM `character_aura` WHERE `guid` = :guid AND `spell` = :spell");
		$revive_res->bindParam(':guid', $guid, PDO::PARAM_INT);
		$revive_res->bindParam(':spell', $this->deathDebuffId, PDO::PARAM_INT);
		$revive_res->execute();
		unset($revive_res);
		*/	
		
		//unstuck using the soap teleport command
		$soap = $CORE->ExecuteSoapCommand(".tele name ".$name." \$home", $this->realm);

		if (!$soap['sent'])
		{
			return false;
		}
		
	  return true;
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