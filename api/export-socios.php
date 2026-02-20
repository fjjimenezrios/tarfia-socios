<?php
/**
 * Exportar socios a CSV.
 * Respeta los filtros activos (grupo, estado, nivel).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Parámetros de filtro
$grupo = isset($_GET['grupo']) ? trim($_GET['grupo']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$nivel = isset($_GET['nivel']) ? trim($_GET['nivel']) : '';
$familiaId = isset($_GET['familia']) ? (int) $_GET['familia'] : 0;

$where = [];
$params = [];

// Filtro de grupo
if ($grupo === 'club') {
    $where[] = "s.`Nivel` IN (0, 1, 2, 3, 4)";
} elseif ($grupo === 'sanrafael') {
    $where[] = "s.`Nivel` IN (5, 6, 7, 8)";
}

// Filtro de estado
if ($estado !== '') {
    $where[] = "LOWER(TRIM(COALESCE(s.`Socio/Ex Socio`, ''))) = LOWER(?)";
    $params[] = $estado;
}

// Filtro de nivel
if ($nivel !== '') {
    $where[] = "n.`Curso` = ?";
    $params[] = $nivel;
}

// Filtro de familia
if ($familiaId > 0) {
    $where[] = "s.`IdFamilia` = ?";
    $params[] = $familiaId;
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT 
        s.`Nombre`,
        f.`Apellidos` AS Familia,
        n.`Curso`,
        s.`Cuota`,
        s.`Socio/Ex Socio` AS Estado,
        s.`Móvil del socio` AS Movil,
        s.`Fecha de admisión` AS FechaAdmision,
        s.`Observaciones`
    FROM `Socios` s
    LEFT JOIN `Familias Socios` f ON f.`Id` = s.`IdFamilia`
    LEFT JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
    $sqlWhere
    ORDER BY f.`Apellidos` ASC, s.`Nombre` ASC
";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// Configurar headers para descarga CSV
$filename = 'socios_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM para Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Cabeceras
fputcsv($output, ['Nombre', 'Familia', 'Curso', 'Cuota', 'Estado', 'Móvil', 'Fecha Admisión', 'Observaciones'], ';');

// Datos
foreach ($rows as $row) {
    $cuota = $row['Cuota'] !== null ? number_format((float) $row['Cuota'], 2, ',', '') : '';
    $fecha = $row['FechaAdmision'] ? date('d/m/Y', strtotime($row['FechaAdmision'])) : '';
    
    fputcsv($output, [
        $row['Nombre'] ?? '',
        $row['Familia'] ?? '',
        $row['Curso'] ?? '',
        $cuota,
        $row['Estado'] ?? '',
        $row['Movil'] ?? '',
        $fecha,
        $row['Observaciones'] ?? '',
    ], ';');
}

fclose($output);
