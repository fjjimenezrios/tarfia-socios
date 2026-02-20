<?php
/**
 * Generador de PDF - Listado de Familias
 * Diseño elegante, minimalista y funcional
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Parámetros
$columnasStr = $_GET['cols'] ?? 'padre,madre,direccion,localidad,moviles';
$columnas = array_filter(explode(',', $columnasStr));

// Siempre incluir apellidos
if (!in_array('apellidos', $columnas)) {
    array_unshift($columnas, 'apellidos');
}

// Query
$sql = "SELECT 
    f.`Id`,
    f.`Apellidos`,
    f.`Nombre padre` AS Padre,
    f.`Nombre madre` AS Madre,
    f.`Dirección` AS Direccion,
    f.`Localidad`,
    f.`Teléfono` AS Telefono,
    f.`Movil Padre` AS MovilPadre,
    f.`Movil Madre` AS MovilMadre,
    f.`e-mail` AS E_mail,
    (SELECT COUNT(*) FROM `Socios` s WHERE s.`IdFamilia` = f.`Id`) as NumSocios
FROM `Familias Socios` f
ORDER BY f.`Apellidos` ASC";

$familias = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Listado de Familias';
$subtituloStr = 'Todos los registros';
$fechaGeneracion = date('d/m/Y H:i');
$totalRegistros = count($familias);

// Mapeo de columnas
$columnasConfig = [
    'apellidos' => ['label' => 'Familia', 'width' => 'auto'],
    'padre' => ['label' => 'Padre', 'width' => 'auto'],
    'madre' => ['label' => 'Madre', 'width' => 'auto'],
    'direccion' => ['label' => 'Dirección', 'width' => 'auto'],
    'localidad' => ['label' => 'Localidad', 'width' => '100px'],
    'telefono' => ['label' => 'Teléfono', 'width' => '90px'],
    'moviles' => ['label' => 'Móviles', 'width' => '120px'],
    'email' => ['label' => 'Email', 'width' => 'auto'],
    'numsocios' => ['label' => 'Socios', 'width' => '50px'],
];

function getCellValueFam($familia, $col) {
    switch ($col) {
        case 'apellidos':
            return htmlspecialchars($familia['Apellidos'] ?? '');
        case 'padre':
            return htmlspecialchars($familia['Padre'] ?? '');
        case 'madre':
            return htmlspecialchars($familia['Madre'] ?? '');
        case 'direccion':
            return htmlspecialchars($familia['Direccion'] ?? '');
        case 'localidad':
            return htmlspecialchars($familia['Localidad'] ?? '');
        case 'telefono':
            return htmlspecialchars($familia['Telefono'] ?? '');
        case 'moviles':
            $mp = $familia['MovilPadre'] ?? '';
            $mm = $familia['MovilMadre'] ?? '';
            $moviles = array_filter([$mp, $mm]);
            return htmlspecialchars(implode(' / ', $moviles));
        case 'email':
            return htmlspecialchars($familia['E_mail'] ?? '');
        case 'numsocios':
            return (int) ($familia['NumSocios'] ?? 0);
        default:
            return '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> - Tarfia</title>
    <link rel="icon" type="image/png" href="../assets/img/logo-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --pdf-primary: #1a2744;
            --pdf-accent: #f7b32b;
            --pdf-text: #1e293b;
            --pdf-muted: #64748b;
            --pdf-border: #e2e8f0;
            --pdf-surface: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: var(--pdf-text);
            background: #fff;
        }

        .pdf-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--pdf-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        .pdf-toolbar-title {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .pdf-toolbar-actions {
            display: flex;
            gap: 0.5rem;
        }

        .pdf-toolbar-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .pdf-toolbar-btn:hover {
            background: rgba(255,255,255,0.25);
        }

        .pdf-toolbar-btn-primary {
            background: var(--pdf-accent);
            border-color: var(--pdf-accent);
            color: var(--pdf-primary);
        }

        .pdf-toolbar-btn-primary:hover {
            background: #e5a327;
        }

        .pdf-container {
            max-width: 210mm;
            margin: 80px auto 2rem;
            padding: 0 1rem;
        }

        .pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1.25rem;
            border-bottom: 2px solid var(--pdf-primary);
            margin-bottom: 1.5rem;
        }

        .pdf-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .pdf-logo img {
            height: 50px;
            width: auto;
        }

        .pdf-title-block {
            text-align: right;
        }

        .pdf-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--pdf-primary);
            margin-bottom: 0.25rem;
        }

        .pdf-subtitle {
            font-size: 0.85rem;
            color: var(--pdf-muted);
        }

        .pdf-meta {
            display: flex;
            justify-content: space-between;
            background: var(--pdf-surface);
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            font-size: 0.8rem;
        }

        .pdf-meta-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .pdf-meta-label {
            color: var(--pdf-muted);
        }

        .pdf-meta-value {
            font-weight: 600;
            color: var(--pdf-text);
        }

        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .pdf-table thead {
            background: var(--pdf-primary);
            color: white;
        }

        .pdf-table th {
            padding: 0.5rem 0.4rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .pdf-table th:first-child {
            border-radius: 4px 0 0 4px;
            padding-left: 0.6rem;
        }

        .pdf-table th:last-child {
            border-radius: 0 4px 4px 0;
            padding-right: 0.6rem;
        }

        .pdf-table tbody tr {
            border-bottom: 1px solid var(--pdf-border);
        }

        .pdf-table tbody tr:nth-child(even) {
            background: var(--pdf-surface);
        }

        .pdf-table tbody tr:hover {
            background: #f1f5f9;
        }

        .pdf-table td {
            padding: 0.4rem;
            vertical-align: middle;
        }

        .pdf-table td:first-child {
            padding-left: 0.6rem;
        }

        .pdf-table td:last-child {
            padding-right: 0.6rem;
        }

        .pdf-row-num {
            color: var(--pdf-muted);
            font-size: 0.65rem;
            width: 25px;
            text-align: center;
        }

        .pdf-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--pdf-border);
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: var(--pdf-muted);
        }

        @media print {
            .pdf-toolbar {
                display: none !important;
            }

            .pdf-container {
                margin: 0;
                padding: 8mm;
                max-width: none;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .pdf-table thead {
                background: var(--pdf-primary) !important;
                -webkit-print-color-adjust: exact !important;
            }

            .pdf-table tbody tr:nth-child(even) {
                background: var(--pdf-surface) !important;
            }

            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            .pdf-header {
                page-break-after: avoid;
            }

            .pdf-table {
                page-break-inside: auto;
            }

            .pdf-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }

        .pdf-empty {
            text-align: center;
            padding: 3rem;
            color: var(--pdf-muted);
        }

        .pdf-empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="pdf-toolbar">
        <span class="pdf-toolbar-title">Vista previa del informe</span>
        <div class="pdf-toolbar-actions">
            <a href="generar.php" class="pdf-toolbar-btn">
                ← Volver
            </a>
            <button onclick="window.print()" class="pdf-toolbar-btn pdf-toolbar-btn-primary">
                🖨️ Imprimir / Guardar PDF
            </button>
        </div>
    </div>

    <div class="pdf-container">
        <header class="pdf-header">
            <div class="pdf-logo">
                <img src="../assets/img/logo.png" alt="Tarfia">
            </div>
            <div class="pdf-title-block">
                <h1 class="pdf-title"><?= htmlspecialchars($titulo) ?></h1>
                <p class="pdf-subtitle"><?= htmlspecialchars($subtituloStr) ?></p>
            </div>
        </header>

        <div class="pdf-meta">
            <div class="pdf-meta-item">
                <span class="pdf-meta-label">Total familias:</span>
                <span class="pdf-meta-value"><?= number_format($totalRegistros) ?></span>
            </div>
            <div class="pdf-meta-item">
                <span class="pdf-meta-label">Generado:</span>
                <span class="pdf-meta-value"><?= $fechaGeneracion ?></span>
            </div>
        </div>

        <?php if (count($familias) > 0): ?>
        <table class="pdf-table">
            <thead>
                <tr>
                    <th class="pdf-row-num">#</th>
                    <?php foreach ($columnas as $col): ?>
                        <?php if (isset($columnasConfig[$col])): ?>
                            <th style="<?= $columnasConfig[$col]['width'] !== 'auto' ? 'width: ' . $columnasConfig[$col]['width'] : '' ?>">
                                <?= htmlspecialchars($columnasConfig[$col]['label']) ?>
                            </th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($familias as $i => $familia): ?>
                <tr>
                    <td class="pdf-row-num"><?= $i + 1 ?></td>
                    <?php foreach ($columnas as $col): ?>
                        <?php if (isset($columnasConfig[$col])): ?>
                            <td><?= getCellValueFam($familia, $col) ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="pdf-empty">
            <div class="pdf-empty-icon">📋</div>
            <p>No se encontraron registros</p>
        </div>
        <?php endif; ?>

        <footer class="pdf-footer">
            <span>Tarfia · Asociación de Padres de Alumnos</span>
            <span>Página 1 de 1</span>
        </footer>
    </div>
</body>
</html>
