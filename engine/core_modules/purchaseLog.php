<?php
if (!defined('init_engine'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

class purchaseLog
{
	private $lastLogId = NULL;
	
	public function __construct()
	{
		return true;
	}
	
	public function add($source, $message, $status)
	{
		global $DB, $CORE, $CURUSER;
		
		$account = $CURUSER->get('id');
		$time = $CORE->getTime();
		
		$insert = $DB->prepare("INSERT INTO `purchase_log` (`account`, `source`, `text`, `time`, `status`) VALUES (:account, :source, :text, :time, :status);");
		$insert->bindParam(':account', $account, PDO::PARAM_INT);
		$insert->bindParam(':source', $source, PDO::PARAM_STR);
		$insert->bindParam(':text', $message, PDO::PARAM_STR);
		$insert->bindParam(':time', $time, PDO::PARAM_STR);
		$insert->bindParam(':status', $status, PDO::PARAM_STR);
		$insert->execute();
		
		//check if the record was inserted
		if ($insert->rowCount() > 0)
		{
			//update the last log id var
			$this->lastLogId = $DB->lastInsertId();
			
			unset($insert);
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function update($logId, $message, $status = false)
	{
		global $DB;
		
		$logId = (int)($logId ? $logId : $this->lastLogId);
		if ($logId <= 0)
		{
			return false;
		}
		
		/*
		 * Do not use SQL CONCAT() here. On some databases the purchase_log.text
		 * column is latin1 while the PDO connection/parameter is utf8, which causes
		 * an "Illegal mix of collations" error during purchases. Build the final
		 * log text in PHP and save it with bound parameters only.
		 */
		$select = $DB->prepare("SELECT `text` FROM `purchase_log` WHERE `id` = :logId LIMIT 1;");
		$select->bindValue(':logId', $logId, PDO::PARAM_INT);
		$select->execute();
		$currentText = $select->fetchColumn();
		unset($select);
		
		if ($currentText === false)
		{
			return false;
		}
		
		$message = (string)$message;
		$newText = (string)$currentText . ' | Update: ' . $message;
		
		$sql = "UPDATE `purchase_log` SET `text` = :text" . ($status !== false ? ", `status` = :status" : "") . " WHERE `id` = :logId LIMIT 1;";
		$update = $DB->prepare($sql);
		$update->bindValue(':text', $newText, PDO::PARAM_STR);
		if ($status !== false)
		{
			$update->bindValue(':status', $status, PDO::PARAM_STR);
		}
		$update->bindValue(':logId', $logId, PDO::PARAM_INT);
		
		if (!$update->execute())
		{
			unset($update);
			return false;
		}
		
		unset($update);
		return true;
	}

	public function __destrruct()
	{
		return true;
	}
}