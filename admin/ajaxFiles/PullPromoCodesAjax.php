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
	if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_PROMO_CODES))
	{
		echo json_encode(array('error' => 'You dont have the required permissions.'));
		die;
	}
		
	function FormatCode($token, $format)
	{
		//split into markers
		$markers = str_split($format);
		$keyChar = str_split($token);
		
		$reduce = 0;
		$key = '';
		//let's put up our key
		foreach ($markers as $index => $marker)
		{
			if (strtolower($marker) == 'x')
			{
				$key .= $keyChar[$index - $reduce];
			}
			else
			{
				$key .= $markers[$index];
				$reduce++;
			}
		}
		unset($markers, $keyChar, $index, $marker, $reduce);
		
		return $key;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Easy set variables
	 */
	
	/* Array of database columns which should be read and sent back to DataTables. Use a space where
	 * you want to insert a non-database field (for example a counter or static image)
	 */
	$aColumns = array('id', 'token', 'usage', 'reward_type', 'reward_value', 'format', 'added');
	
	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "id";
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * If you just want to use the basic configuration for DataTables with PHP server-side, there is
	 * no need to edit below this line
	 */

	$sTable = 'promo_codes';

	list($rResult, $iFilteredTotal, $iTotal) = warcry_dt_query($DB, $aColumns, $sTable, $sIndexColumn);
	
	
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
		
		//Resolve the usage type
		switch ((int)$aRow['usage'])
		{
			case PCODE_USAGE_ONCE:
				$usage = 'Unique';
				break;
			case PCODE_USAGE_PER_ACC:
				$usage = 'Per Account';
				break;
			default:
				$usage = 'Unknown';
				break;
		}
		
		//Resolve the reward
		switch ((int)$aRow['reward_type'])
		{
			case PCODE_REWARD_CURRENCY_S:
				$reward = $aRow['reward_value'] . ' Silver Coins';
				break;
			case PCODE_REWARD_CURRENCY_G:
				$reward = $aRow['reward_value'] . ' Gold Coins';
				break;
			case PCODE_REWARD_ITEM:
				$reward = 'Item: ' . $aRow['reward_value'];
				break;
		}
		
		//Set the first two columns
		$row[0] = $aRow['id'];
		$row[1] = FormatCode($aRow['token'], $aRow['format']);
		$row[2] = $usage;
		$row[3] = $reward;
		$row[4] = $aRow['added'];
		$row[5] = '<a href="execute.php?take=delete&action=pcode&id='.$aRow['id'].'" onclick="return deletecheck(\'Are you sure you want to delete this code?\');" class="button icon remove danger">Remove</a>';
		
		//Now we have to pull 
		$output['aaData'][] = $row;
	}
	
	echo json_encode( $output );
?>