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

if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_ARTICLES))
{
    echo json_encode(array('error' => 'You dont have the required permissions.'));
    die;
}

$aColumns = array('id', 'title', 'short_text', 'views', 'added', 'author', 'comments');
$sIndexColumn = 'id';
$sTable = 'articles';

$sLimit = '';
if (isset($_GET['iDisplayStart'], $_GET['iDisplayLength']) && $_GET['iDisplayLength'] != '-1')
{
    $sLimit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
}

$sOrder = '';
if (isset($_GET['iSortCol_0']))
{
    $orderParts = array();
    for ($i = 0; $i < intval($_GET['iSortingCols']); $i++)
    {
        $colIndex = intval($_GET['iSortCol_'.$i]);
        if (isset($aColumns[$colIndex]) && isset($_GET['bSortable_'.$colIndex]) && $_GET['bSortable_'.$colIndex] == 'true')
        {
            $orderParts[] = '`' . $aColumns[$colIndex] . '` ' . ($_GET['sSortDir_'.$i] === 'asc' ? 'asc' : 'desc');
        }
    }
    if (count($orderParts) > 0)
    {
        $sOrder = 'ORDER BY ' . implode(', ', $orderParts);
    }
}

$sWhere = '';
if (isset($_GET['sSearch']) && $_GET['sSearch'] !== '')
{
    $like = $DB->quote('%' . $_GET['sSearch'] . '%');
    $whereParts = array();
    foreach ($aColumns as $column)
    {
        $whereParts[] = '`' . $column . '` LIKE ' . $like;
    }
    $sWhere = 'WHERE (' . implode(' OR ', $whereParts) . ')';
}

for ($i = 0; $i < count($aColumns); $i++)
{
    if (isset($_GET['bSearchable_'.$i], $_GET['sSearch_'.$i]) && $_GET['bSearchable_'.$i] == 'true' && $_GET['sSearch_'.$i] !== '')
    {
        $sWhere .= ($sWhere == '' ? 'WHERE ' : ' AND ');
        $sWhere .= '`' . $aColumns[$i] . '` LIKE ' . $DB->quote('%' . $_GET['sSearch_'.$i] . '%') . ' ';
    }
}

$sQuery = "
    SELECT SQL_CALC_FOUND_ROWS `" . str_replace(' , ', ' ', implode('`, `', $aColumns)) . "`
    FROM $sTable
    $sWhere
    $sOrder
    $sLimit
";
$rResult = $DB->query($sQuery);

$rResultFilterTotal = $DB->query('SELECT FOUND_ROWS()');
$aResultFilterTotal = $rResultFilterTotal->fetch(PDO::FETCH_NUM);
$iFilteredTotal = $aResultFilterTotal[0];

$rResultTotal = $DB->query('SELECT COUNT(`' . $sIndexColumn . '`) FROM ' . $sTable);
$aResultTotal = $rResultTotal->fetch(PDO::FETCH_NUM);
$iTotal = $aResultTotal[0];

$output = array(
    'sEcho' => isset($_GET['sEcho']) ? intval($_GET['sEcho']) : 0,
    'iTotalRecords' => $iTotal,
    'iTotalDisplayRecords' => $iFilteredTotal,
    'aaData' => array()
);

while ($aRow = $rResult->fetch(PDO::FETCH_ASSOC))
{
    $authorName = 'Unknown';
    $res2 = $DB->prepare('SELECT displayName FROM `account_data` WHERE `id` = :id LIMIT 1;');
    $res2->bindParam(':id', $aRow['author'], PDO::PARAM_INT);
    $res2->execute();
    if ($res2->rowCount() > 0)
    {
        $author = $res2->fetch(PDO::FETCH_ASSOC);
        $authorName = $author['displayName'];
    }

    $countComments = $DB->prepare('SELECT COUNT(*) FROM `article_comments` WHERE `article` = :id;');
    $countComments->bindParam(':id', $aRow['id'], PDO::PARAM_INT);
    $countComments->execute();
    $commentTotal = (int)$countComments->fetchColumn();

    $status = ((int)$aRow['comments'] === 1) ? '<span style="color:#69d36f;font-weight:bold;">Comments Enabled</span>' : '<span style="color:#ff6b6b;font-weight:bold;">Comments Disabled</span>';

    $row = array();
    $row[] = (int)$aRow['id'];
    $row[] = htmlspecialchars(stripslashes($aRow['title']), ENT_QUOTES, 'UTF-8');
    $row[] = '<div style="max-width:500px;white-space:normal;">' . htmlspecialchars(stripslashes($aRow['short_text']), ENT_QUOTES, 'UTF-8') . '</div>';
    $row[] = (int)$aRow['views'];
    $row[] = htmlspecialchars($aRow['added'], ENT_QUOTES, 'UTF-8');
    $row[] = '<a href="index.php?page=user-preview&uid=' . (int)$aRow['author'] . '">' . htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8') . '</a> [' . (int)$aRow['author'] . ']';
    $row[] = $commentTotal;
    $row[] = $status;
    $row[] = '<span class="button-group">'
        . '<a href="index.php?page=edit-article&id=' . (int)$aRow['id'] . '" class="button icon edit">Edit</a>'
        . '<a href="execute.php?take=flush_article_comments&scope=article&id=' . (int)$aRow['id'] . '" onclick="return deletecheck(\'Are you sure you want to delete all comments for this article?\');" class="button icon remove">Flush Comments</a>'
        . '<a href="execute.php?take=delete&action=article&id=' . (int)$aRow['id'] . '" onclick="return deletecheck(\'Are you sure you want to delete this article?\');" class="button icon remove danger">Remove</a>'
        . '</span>';

    $output['aaData'][] = $row;
}

echo json_encode($output);
?>
