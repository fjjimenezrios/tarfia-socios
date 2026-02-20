<?php
/**
 * Exportar familias a CSV.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$sql = "
    SELECT 
        f.`Apellidos`,
        f.`Nombre padre`,
        f.`Apellidos padre`,
        f.`Movil Padre`,
        f.`Nombre madre`,
        f.`Apellidos madre`,
        f.`Movil Madre`,
        f.`Teléfono`,
        f.`e-mail`,
        f.`Dirección`,
        f.`Localidad`,
        (SELECT COUNT(*) FROM `Socios` s WHERE s.`IdFamilia` = f.`Id`) AS NumSocios
    FROM `Familias Socios` f
    ORDER BY f.`Apellidos` ASC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Configurar headers para descarga CSV
$filename = 'familias_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM para Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Cabeceras
fputcsv($output, [
    'Apellidos', 'Nombre Padre', 'Apellidos Padre', 'Móvil Padre',
    'Nombre Madre', 'Apellidos Madre', 'Móvil Madre',
    'Teléfono', 'Email', 'Dirección', 'Localidad', 'Nº Socios'
], ';');

// Datos
foreach ($rows as $row) {
    fputcsv($output, [
        $row['Apellidos'] ?? '',
        $row['Nombre padre'] ?? '',
        $row['Apellidos padre'] ?? '',
        $row['Movil Padre'] ?? '',
        $row['Nombre madre'] ?? '',
        $row['Apellidos madre'] ?? '',
        $row['Movil Madre'] ?? '',
        $row['Teléfono'] ?? '',
        $row['e-mail'] ?? '',
        $row['Dirección'] ?? '',
        $row['Localidad'] ?? '',
        $row['NumSocios'] ?? 0,
    ], ';');
}

fclose($output);
