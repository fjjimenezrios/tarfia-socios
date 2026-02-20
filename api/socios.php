<?php
/**
 * API DataTables server-side para Socios.
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
$familiaId = isset($_GET['familia_id']) && $_GET['familia_id'] !== '' ? (int) $_GET['familia_id'] : null;
$grupo = isset($_GET['grupo']) ? trim($_GET['grupo']) : '';

$orderCol = isset($_GET['order'][0]['column']) ? (int) $_GET['order'][0]['column'] : 0;
$orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';

// Columna 0 es el número de fila (no ordenable en servidor)
$columns = [null, 's.`Nombre`', 'f.`Apellidos`', 's.`Nivel`', 's.`Cuota`', 's.`Socio/Ex Socio`', 's.`Móvil del socio`', 's.`Fecha de admisión`'];
$orderColumn = isset($columns[$orderCol]) && $columns[$orderCol] !== null ? $columns[$orderCol] : 's.`Nombre`';

$where = [];
$params = [];

// Búsqueda global
if ($search !== '') {
    $where[] = "(s.`Nombre` LIKE ? OR f.`Apellidos` LIKE ? OR n.`Curso` LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

// Filtro por ID de familia (desde enlace)
if ($familiaId !== null) {
    $where[] = "s.`IdFamilia` = ?";
    $params[] = $familiaId;
}

// Filtros por columna (col 0 es número de fila, filtros empiezan en col 1)
if (!empty($colFilters['1'])) { // Nombre
    $where[] = "s.`Nombre` LIKE ?";
    $params[] = '%' . $colFilters['1'] . '%';
}
if (!empty($colFilters['2'])) { // Familia (texto)
    $where[] = "f.`Apellidos` LIKE ?";
    $params[] = '%' . $colFilters['2'] . '%';
}
if (!empty($colFilters['3'])) { // Curso
    $where[] = "n.`Curso` = ?";
    $params[] = $colFilters['3'];
}
if (!empty($colFilters['4'])) { // Cuota
    $where[] = "s.`Cuota` LIKE ?";
    $params[] = '%' . $colFilters['4'] . '%';
}
if (!empty($colFilters['5'])) { // Estado (búsqueda exacta, case insensitive)
    $where[] = "LOWER(TRIM(COALESCE(s.`Socio/Ex Socio`, ''))) = LOWER(?)";
    $params[] = trim($colFilters['5']);
}
if (!empty($colFilters['6'])) { // Móvil
    $where[] = "s.`Móvil del socio` LIKE ?";
    $params[] = '%' . $colFilters['6'] . '%';
}
if (!empty($colFilters['7'])) { // Fecha
    $where[] = "s.`Fecha de admisión` LIKE ?";
    $params[] = '%' . $colFilters['7'] . '%';
}

// Filtro de grupo y ordenación por nivel
$ordenarPorNivel = false;
if ($grupo === 'club') {
    // Club: 4EPO a 2ESO (niveles 0, 1, 2, 3, 4)
    $where[] = "s.`Nivel` IN (0, 1, 2, 3, 4)";
    $ordenarPorNivel = true;
} elseif ($grupo === 'sanrafael') {
    // San Rafael: 3ESO a 2BACH (niveles 5, 6, 7, 8)
    $where[] = "s.`Nivel` IN (5, 6, 7, 8)";
    $ordenarPorNivel = true;
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Ordenación: por nivel si hay grupo seleccionado, luego por nombre
if ($ordenarPorNivel) {
    $sqlOrder = "ORDER BY s.`Nivel` ASC, s.`Nombre` ASC";
} else {
    $sqlOrder = "ORDER BY $orderColumn $orderDir";
}

// Total sin filtro (cacheado 30 segundos)
$totalAll = cache_get('socios_total');
if ($totalAll === null) {
    $totalAll = (int) $pdo->query("SELECT COUNT(*) FROM `Socios`")->fetchColumn();
    cache_set('socios_total', $totalAll, 30);
}

// Usar SQL_CALC_FOUND_ROWS para obtener el total filtrado en una sola query
$sql = "
    SELECT SQL_CALC_FOUND_ROWS 
        s.`Id`, s.`Nombre`, s.`IdFamilia`, f.`Apellidos` AS familia, 
        s.`Nivel`, n.`Curso`, s.`Cuota`, s.`Socio/Ex Socio` AS estado, 
        s.`Móvil del socio` AS movil, s.`Fecha de admisión` AS fecha_admision
    FROM `Socios` s
    LEFT JOIN `Familias Socios` f ON f.`Id` = s.`IdFamilia`
    LEFT JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
    $sqlWhere
    $sqlOrder
    LIMIT $length OFFSET $start
";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// Obtener total filtrado (usando FOUND_ROWS - más rápido que COUNT separado)
$totalFiltered = (int) $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

$data = [];
$rowNum = $start;
foreach ($rows as $r) {
    $rowNum++;
    $familiaLink = $r['familia'] ? '<a href="socios.php?familia=' . (int) $r['IdFamilia'] . '" class="tarfia-familia-link">' . htmlspecialchars($r['familia']) . '</a>' : '—';
    $acciones = '<div class="tarfia-acciones">'
        . '<a href="socio-detalle.php?id=' . (int) $r['Id'] . '" class="tarfia-btn tarfia-btn-sm tarfia-btn-outline" title="Ver"><i class="fas fa-eye"></i></a> '
        . '<a href="socios-editar.php?id=' . (int) $r['Id'] . '" class="tarfia-btn tarfia-btn-sm tarfia-btn-primary" title="Editar"><i class="fas fa-pen"></i></a>'
        . '</div>';
    $data[] = [
        '<span class="tarfia-row-num">' . $rowNum . '</span>',
        htmlspecialchars($r['Nombre'] ?? '—'),
        $familiaLink,
        htmlspecialchars($r['Curso'] ?? '—'),
        $r['Cuota'] !== null ? number_format((float) $r['Cuota'], 2, ',', '.') . ' €' : '—',
        htmlspecialchars($r['estado'] ?? '—'),
        htmlspecialchars($r['movil'] ?? '—'),
        $r['fecha_admision'] ? date('d/m/Y', strtotime($r['fecha_admision'])) : '—',
        $acciones,
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalAll,
    'recordsFiltered' => $totalFiltered,
    'data' => $data,
], JSON_UNESCAPED_UNICODE);
