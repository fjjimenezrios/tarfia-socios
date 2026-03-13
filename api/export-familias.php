<?php
/**
 * Exportar familias a CSV.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$sql = "
    SELECT 
        f.`Apellidos`,
        f.`Nombre_padre`,
        f.`Apellidos_padre`,
        f.`Movil_Padre`,
        f.`Nombre_madre`,
        f.`Apellidos_madre`,
        f.`Movil_Madre`,
        f.`Telefono`,
        f.`e_mail`,
        f.`Direccion`,
        f.`Localidad`,
        (SELECT COUNT(*) FROM `Socios` s WHERE s.`IdFamilia` = f.`Id`) AS NumSocios
    FROM `Familias_Socios` f
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
        $row['Nombre_padre'] ?? '',
        $row['Apellidos_padre'] ?? '',
        $row['Movil_Padre'] ?? '',
        $row['Nombre_madre'] ?? '',
        $row['Apellidos_madre'] ?? '',
        $row['Movil_Madre'] ?? '',
        $row['Telefono'] ?? '',
        $row['e_mail'] ?? '',
        $row['Direccion'] ?? '',
        $row['Localidad'] ?? '',
        $row['NumSocios'] ?? 0,
    ], ';');
}

fclose($output);
