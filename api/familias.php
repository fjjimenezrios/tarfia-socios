<?php
/**
 * API DataTables server-side para Familias.
 * Responde JSON compatible con DataTables.
 * Optimizado para rendimiento.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=0');

$draw = isset($_GET['draw']) ? (int) $_GET['draw'] : 1;
$start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$length = isset($_GET['length']) ? (int) $_GET['length'] : 25;
$search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

// Filtros por columna
$colFilters = isset($_GET['col_filters']) && is_array($_GET['col_filters']) ? $_GET['col_filters'] : [];

$orderCol = isset($_GET['order'][0]['column']) ? (int) $_GET['order'][0]['column'] : 0;
$orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';

// Columna 0 es el número de fila (no ordenable en servidor)
$columns = [null, '`Apellidos`', '`Nombre padre`', '`Nombre madre`', '`Localidad`', '`Teléfono`', '`e-mail`'];
$orderColumn = isset($columns[$orderCol]) && $columns[$orderCol] !== null ? $columns[$orderCol] : '`Apellidos`';

$where = [];
$params = [];

// Búsqueda global
if ($search !== '') {
    $where[] = "(`Apellidos` LIKE ? OR `Nombre padre` LIKE ? OR `Nombre madre` LIKE ? OR `Localidad` LIKE ? OR `e-mail` LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

// Filtros por columna (col 0 es número de fila, filtros empiezan en col 1)
if (!empty($colFilters['1'])) { // Apellidos
    $where[] = "`Apellidos` LIKE ?";
    $params[] = '%' . $colFilters['1'] . '%';
}
if (!empty($colFilters['2'])) { // Padre
    $where[] = "CONCAT(COALESCE(`Nombre padre`, ''), ' ', COALESCE(`Apellidos padre`, '')) LIKE ?";
    $params[] = '%' . $colFilters['2'] . '%';
}
if (!empty($colFilters['3'])) { // Madre
    $where[] = "CONCAT(COALESCE(`Nombre madre`, ''), ' ', COALESCE(`Apellidos madre`, '')) LIKE ?";
    $params[] = '%' . $colFilters['3'] . '%';
}
if (!empty($colFilters['4'])) { // Localidad
    $where[] = "`Localidad` LIKE ?";
    $params[] = '%' . $colFilters['4'] . '%';
}
if (!empty($colFilters['5'])) { // Teléfono
    $where[] = "`Teléfono` LIKE ?";
    $params[] = '%' . $colFilters['5'] . '%';
}
if (!empty($colFilters['6'])) { // Móviles
    $where[] = "(COALESCE(`Movil Padre`, '') LIKE ? OR COALESCE(`Movil Madre`, '') LIKE ?)";
    $params[] = '%' . $colFilters['6'] . '%';
    $params[] = '%' . $colFilters['6'] . '%';
}
if (!empty($colFilters['7'])) { // Email
    $where[] = "`e-mail` LIKE ?";
    $params[] = '%' . $colFilters['7'] . '%';
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total sin filtro (cacheado 30 segundos)
$totalAll = cache_get('familias_total');
if ($totalAll === null) {
    $totalAll = (int) $pdo->query("SELECT COUNT(*) FROM `Familias Socios`")->fetchColumn();
    cache_set('familias_total', $totalAll, 30);
}

// Datos con SQL_CALC_FOUND_ROWS y conteo de socios en subquery (todo en una sola query)
$sql = "
    SELECT SQL_CALC_FOUND_ROWS 
        f.`Id`, f.`Apellidos`, f.`Nombre padre`, f.`Apellidos padre`, 
        f.`Nombre madre`, f.`Apellidos madre`, f.`Localidad`, f.`Teléfono`, 
        f.`Movil Madre`, f.`Movil Padre`, f.`e-mail`,
        (SELECT COUNT(*) FROM `Socios` s WHERE s.`IdFamilia` = f.`Id`) AS num_socios
    FROM `Familias Socios` f
    $sqlWhere
    ORDER BY $orderColumn $orderDir
    LIMIT $length OFFSET $start
";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// Total filtrado (usando FOUND_ROWS)
$totalFiltered = (int) $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

$data = [];
$rowNum = $start;
foreach ($rows as $r) {
    $rowNum++;
    $padre = trim(($r['Nombre padre'] ?? '') . ' ' . ($r['Apellidos padre'] ?? ''));
    $madre = trim(($r['Nombre madre'] ?? '') . ' ' . ($r['Apellidos madre'] ?? ''));
    $moviles = trim(($r['Movil Padre'] ?? '') . ' / ' . ($r['Movil Madre'] ?? ''), ' /');
    $numSocios = (int) ($r['num_socios'] ?? 0);
    $sociosBadge = '<span class="tarfia-badge">' . $numSocios . '</span>';
    $acciones = '<div class="tarfia-acciones">'
        . '<a href="familia-detalle.php?id=' . (int) $r['Id'] . '" class="tarfia-btn tarfia-btn-sm tarfia-btn-outline" title="Ver"><i class="fas fa-eye"></i></a> '
        . '<a href="familias-editar.php?id=' . (int) $r['Id'] . '" class="tarfia-btn tarfia-btn-sm tarfia-btn-primary" title="Editar"><i class="fas fa-pen"></i></a>'
        . '</div>';
    
    $data[] = [
        '<span class="tarfia-row-num">' . $rowNum . '</span>',
        htmlspecialchars($r['Apellidos'] ?? '—'),
        htmlspecialchars($padre ?: '—'),
        htmlspecialchars($madre ?: '—'),
        htmlspecialchars($r['Localidad'] ?? '—'),
        htmlspecialchars($r['Teléfono'] ?? '—'),
        htmlspecialchars($moviles ?: '—'),
        htmlspecialchars($r['e-mail'] ?? '—'),
        $sociosBadge,
        $acciones,
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalAll,
    'recordsFiltered' => $totalFiltered,
    'data' => $data,
], JSON_UNESCAPED_UNICODE);
