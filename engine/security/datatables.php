<?php
// Safe replacement for the legacy DataTables server-side boilerplate that
// concatenated $_GET['sSearch'] / $_GET['sSearch_*'] / $_GET['iSortCol_*']
// directly into SQL via the fake mysql_real_escape_string() stub.
//
// Builds the LIMIT, ORDER BY and WHERE clauses with whitelisted column names
// and bound parameters. Returns:
//   [PDOStatement $rResult, int $iFilteredTotal, int $iTotal]
// The caller iterates $rResult exactly as before, so existing display logic
// is untouched.

if (!function_exists('warcry_dt_query')) {
    /**
     * @param PDO    $db
     * @param array  $aColumns       Whitelist of column names (only [A-Za-z0-9_] allowed).
     * @param string $sTable         Table name (whitelisted via regex).
     * @param string $sIndexColumn   Column used for COUNT() of full set.
     * @return array{0: PDOStatement, 1: int, 2: int}
     */
    function warcry_dt_query(PDO $db, array $aColumns, string $sTable, string $sIndexColumn, array $extraFilters = array()): array
    {
        $identRe = '/^[A-Za-z0-9_]+$/';
        $safeColumns = array();
        foreach ($aColumns as $c) {
            if (is_string($c) && preg_match($identRe, $c)) {
                $safeColumns[] = $c;
            }
        }
        if (empty($safeColumns)) {
            throw new RuntimeException('warcry_dt_query: no valid columns supplied');
        }
        if (!preg_match($identRe, $sTable)) {
            throw new RuntimeException('warcry_dt_query: invalid table name');
        }
        if (!preg_match($identRe, $sIndexColumn)) {
            throw new RuntimeException('warcry_dt_query: invalid index column');
        }

        $start = isset($_GET['iDisplayStart']) ? max(0, (int)$_GET['iDisplayStart']) : 0;
        $length = isset($_GET['iDisplayLength']) ? (int)$_GET['iDisplayLength'] : 25;
        $useLimit = $length !== -1;
        if ($useLimit && ($length < 1 || $length > 1000)) {
            $length = 25;
        }

        $orderParts = array();
        if (isset($_GET['iSortCol_0']) && isset($_GET['iSortingCols'])) {
            $sortingCols = (int)$_GET['iSortingCols'];
            for ($i = 0; $i < $sortingCols; $i++) {
                $colIdx = isset($_GET['iSortCol_' . $i]) ? (int)$_GET['iSortCol_' . $i] : -1;
                if (!isset($aColumns[$colIdx])) {
                    continue;
                }
                $col = $aColumns[$colIdx];
                if (!preg_match($identRe, (string)$col)) {
                    continue;
                }
                $sortableKey = 'bSortable_' . $colIdx;
                if (!isset($_GET[$sortableKey]) || $_GET[$sortableKey] !== 'true') {
                    continue;
                }
                $dir = (isset($_GET['sSortDir_' . $i]) && strtolower($_GET['sSortDir_' . $i]) === 'asc') ? 'ASC' : 'DESC';
                $orderParts[] = "`$col` $dir";
            }
        }
        $sOrder = empty($orderParts) ? '' : 'ORDER BY ' . implode(', ', $orderParts);

        $whereClauses = array();
        $params = array();

        $globalSearch = isset($_GET['sSearch']) ? trim((string)$_GET['sSearch']) : '';
        if ($globalSearch !== '') {
            $orParts = array();
            foreach ($safeColumns as $col) {
                $orParts[] = "`$col` LIKE :wc_dt_q";
            }
            if (!empty($orParts)) {
                $whereClauses[] = '(' . implode(' OR ', $orParts) . ')';
                $params[':wc_dt_q'] = '%' . $globalSearch . '%';
            }
        }

        foreach ($safeColumns as $idx => $col) {
            $colIdx = array_search($col, $aColumns, true);
            if ($colIdx === false) {
                continue;
            }
            $searchableKey = 'bSearchable_' . $colIdx;
            $searchKey = 'sSearch_' . $colIdx;
            if (!isset($_GET[$searchableKey]) || $_GET[$searchableKey] !== 'true') {
                continue;
            }
            if (!isset($_GET[$searchKey]) || $_GET[$searchKey] === '') {
                continue;
            }
            $param = ':wc_dt_c' . $colIdx;
            $whereClauses[] = "`$col` LIKE $param";
            $params[$param] = '%' . trim((string)$_GET[$searchKey]) . '%';
        }

        foreach ($extraFilters as $col => $value) {
            if (!is_string($col) || !preg_match($identRe, $col)) {
                continue;
            }
            $param = ':wc_dt_x_' . $col;
            $whereClauses[] = "`$col` = $param";
            $params[$param] = (string)$value;
        }

        $sWhere = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

        $colList = '`' . implode('`, `', $safeColumns) . '`';
        $limitSql = $useLimit ? ' LIMIT :wc_dt_start, :wc_dt_len' : '';

        $sql = "SELECT $colList FROM `$sTable` $sWhere $sOrder$limitSql";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        if ($useLimit) {
            $stmt->bindValue(':wc_dt_start', $start, PDO::PARAM_INT);
            $stmt->bindValue(':wc_dt_len', $length, PDO::PARAM_INT);
        }
        $stmt->execute();

        $countSql = "SELECT COUNT(`$sIndexColumn`) FROM `$sTable` $sWhere";
        $countStmt = $db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $iFilteredTotal = (int)$countStmt->fetchColumn();

        $totalStmt = $db->query("SELECT COUNT(`$sIndexColumn`) FROM `$sTable`");
        $iTotal = (int)$totalStmt->fetchColumn();

        return array($stmt, $iFilteredTotal, $iTotal);
    }
}
