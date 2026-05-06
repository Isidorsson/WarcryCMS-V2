<?php
	if (!defined('init_ajax'))
	{	
		header('HTTP/1.0 404 not found');
		exit;
	}
	
	if (!$CURUSER->isOnline())
	{
		echo json_encode(array('error' => 'You must be logged in.'));
		die;
	}
	
	//check for permissions
	if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_LOGS))
	{
		echo json_encode(array('error' => 'You dont have the required permissions.'));
		die;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Easy set variables
	 */
	
	/* Array of database columns which should be read and sent back to DataTables. Use a space where
	 * you want to insert a non-database field (for example a counter or static image)
	 */
	$aColumns = array('id', 'text', 'account', 'time', 'status');
	
	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "id";
	
	//Logs Source
	$sSource = 'STORE';
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * If you just want to use the basic configuration for DataTables with PHP server-side, there is
	 * no need to edit below this line
	 */

	$sTable = 'purchase_log';

	list($rResult, $iFilteredTotal, $iTotal) = warcry_dt_query($DB, $aColumns, $sTable, $sIndexColumn, array('source' => $sSource));
	
	
	/*
	 * Output
	 */
	$output = array(
		"sEcho" => isset($_GET['sEcho']) ? intval($_GET['sEcho']) : 0,
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	
	while ( $aRow = $rResult->fetch() )
	{
		$row = array();
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if ( $aColumns[$i] == "version" )
			{
				/* Special output formatting for 'version' column */
				$row[] = ($aRow[ $aColumns[$i] ]=="0") ? '-' : $aRow[ $aColumns[$i] ];
			}
		}
		
		//split the text
		$textArr = explode('| Update:', $aRow['text']);
		$text = '';
		foreach ($textArr as $val)
		{
			$text .= $val . '<br />';
		}
		
		//find the account
		$res2 = $DB->prepare("SELECT displayName FROM `account_data` WHERE `id` = :id LIMIT 1;");
		$res2->bindParam(':id', $aRow['account'], PDO::PARAM_INT);
		$res2->execute();
		//if we found it
		if ($res2->rowCount() > 0)
		{
			$row = $res2->fetch();
			$aRow['account'] = '<a href="index.php?page=user-preview&uid='.$aRow['account'].'">' . $row['displayName'] . '</a> [' . $aRow['account'] . ']';
			unset($row);
		}
		unset($res2);

		//Set the first two columns
		$row[0] = $aRow['id'];
		$row[1] = '
			<div class="datatable-expander" style="position: relative;">
				<p>'.$text.'</p>
				<span style="position: absolute; top: 1px; right: 0px;">
					<a href="#" onclick="return Toggle(this);">Open</a>
				</span>
			</div>';
		$row[2] = $aRow['account'];
		$row[3] = $aRow['time'];
		$row[4] = ucfirst($aRow['status']);

		//Now we have to pull 
		$output['aaData'][] = $row;
	}
	
	echo json_encode( $output );
?>