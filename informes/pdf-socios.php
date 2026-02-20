<?php
/**
 * Generador de PDF - Listado de Socios
 * Diseño elegante, minimalista y funcional
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Parámetros
$tipo = $_GET['tipo'] ?? 'socios';
$nivelFiltro = $_GET['nivel'] ?? '';
$estadoFiltro = $_GET['estado'] ?? '';
$grupoFiltro = $_GET['grupo'] ?? '';
$columnasStr = $_GET['cols'] ?? 'familia,curso,estado';
$columnas = array_filter(explode(',', $columnasStr));

// Siempre incluir nombre
if (!in_array('nombre', $columnas)) {
    array_unshift($columnas, 'nombre');
}

// Niveles
$nivelesMap = [];
$nivelesQuery = $pdo->query("SELECT `Nivel`, `Curso` FROM `Niveles-Cursos` ORDER BY `Nivel`");
foreach ($nivelesQuery as $n) {
    $nivelesMap[(int) $n['Nivel']] = $n['Curso'];
}

// Construir query
$where = [];
$params = [];

if ($nivelFiltro !== '') {
    $where[] = 's.`Nivel` = ?';
    $params[] = (int) $nivelFiltro;
}

if ($estadoFiltro === 'socio') {
    $where[] = 's.`Socio/Ex Socio` = ?';
    $params[] = 'Socio';
} elseif ($estadoFiltro === 'exsocio') {
    $where[] = 's.`Socio/Ex Socio` = ?';
    $params[] = 'Ex Socio';
}

if ($grupoFiltro === 'club') {
    $where[] = 's.`Nivel` BETWEEN 0 AND 4';
} elseif ($grupoFiltro === 'sanrafael') {
    $where[] = 's.`Nivel` BETWEEN 5 AND 8';
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT 
    s.`Id`,
    s.`Nombre`,
    s.`Nivel`,
    s.`Socio/Ex Socio` AS Socio,
    s.`Cuota`,
    s.`Móvil del socio` AS Movil,
    s.`Fecha de admisión` AS Fecha_admision,
    s.`Observaciones`,
    f.`Apellidos` as FamiliaApellidos,
    f.`Id` AS ID_Familia
FROM `Socios` s
LEFT JOIN `Familias Socios` f ON s.`IdFamilia` = f.`Id`
$whereClause
ORDER BY s.`Nivel` ASC, s.`Nombre` ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$socios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Título del informe
$titulo = 'Listado de Socios';
$subtitulo = [];

if ($nivelFiltro !== '' && isset($nivelesMap[(int) $nivelFiltro])) {
    $subtitulo[] = $nivelesMap[(int) $nivelFiltro];
}
if ($estadoFiltro === 'socio') {
    $subtitulo[] = 'Solo Socios activos';
} elseif ($estadoFiltro === 'exsocio') {
    $subtitulo[] = 'Solo Ex Socios';
}
if ($grupoFiltro === 'club') {
    $subtitulo[] = 'Club (4EPO – 2ESO)';
} elseif ($grupoFiltro === 'sanrafael') {
    $subtitulo[] = 'San Rafael (3ESO – 2BACH)';
}

$subtituloStr = count($subtitulo) > 0 ? implode(' · ', $subtitulo) : 'Todos los registros';
$fechaGeneracion = date('d/m/Y H:i');
$totalRegistros = count($socios);

// Mapeo de columnas
$columnasConfig = [
    'nombre' => ['label' => 'Nombre', 'width' => 'auto'],
    'familia' => ['label' => 'Familia', 'width' => 'auto'],
    'curso' => ['label' => 'Curso', 'width' => '80px'],
    'estado' => ['label' => 'Estado', 'width' => '70px'],
    'cuota' => ['label' => 'Cuota', 'width' => '60px'],
    'movil' => ['label' => 'Móvil', 'width' => '100px'],
    'fecha' => ['label' => 'F. Admisión', 'width' => '90px'],
    'observaciones' => ['label' => 'Observaciones', 'width' => '150px'],
];

function getCellValue($socio, $col, $nivelesMap) {
    switch ($col) {
        case 'nombre':
            return htmlspecialchars($socio['Nombre'] ?? '');
        case 'familia':
            return htmlspecialchars($socio['FamiliaApellidos'] ?? '');
        case 'curso':
            $nivel = (int) ($socio['Nivel'] ?? -1);
            return $nivelesMap[$nivel] ?? '';
        case 'estado':
            return htmlspecialchars($socio['Socio'] ?? '');
        case 'cuota':
            return $socio['Cuota'] ? number_format((float) $socio['Cuota'], 0) . ' €' : '';
        case 'movil':
            return htmlspecialchars($socio['Movil'] ?? '');
        case 'fecha':
            if (!empty($socio['Fecha_admision'])) {
                try {
                    $d = new DateTime($socio['Fecha_admision']);
                    return $d->format('d/m/Y');
                } catch (Exception $e) {
                    return '';
                }
            }
            return '';
        case 'observaciones':
            $obs = $socio['Observaciones'] ?? '';
            return htmlspecialchars(mb_strlen($obs) > 50 ? mb_substr($obs, 0, 50) . '...' : $obs);
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

        /* Barra de herramientas (no se imprime) */
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

        /* Contenedor principal */
        .pdf-container {
            max-width: 210mm;
            margin: 80px auto 2rem;
            padding: 0 1rem;
        }

        /* Cabecera del documento */
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

        .pdf-logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--pdf-primary);
            letter-spacing: -0.5px;
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

        /* Metadatos */
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

        /* Tabla */
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .pdf-table thead {
            background: var(--pdf-primary);
            color: white;
        }

        .pdf-table th {
            padding: 0.6rem 0.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .pdf-table th:first-child {
            border-radius: 4px 0 0 4px;
            padding-left: 0.75rem;
        }

        .pdf-table th:last-child {
            border-radius: 0 4px 4px 0;
            padding-right: 0.75rem;
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
            padding: 0.5rem;
            vertical-align: middle;
        }

        .pdf-table td:first-child {
            padding-left: 0.75rem;
        }

        .pdf-table td:last-child {
            padding-right: 0.75rem;
        }

        /* Número de fila */
        .pdf-row-num {
            color: var(--pdf-muted);
            font-size: 0.7rem;
            width: 30px;
            text-align: center;
        }

        /* Badge estado */
        .pdf-badge {
            display: inline-block;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .pdf-badge-socio {
            background: #dcfce7;
            color: #166534;
        }

        .pdf-badge-exsocio {
            background: #fef3c7;
            color: #92400e;
        }

        /* Pie de documento */
        .pdf-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--pdf-border);
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: var(--pdf-muted);
        }

        /* Estilos de impresión */
        @media print {
            .pdf-toolbar {
                display: none !important;
            }

            .pdf-container {
                margin: 0;
                padding: 10mm;
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

            .pdf-badge {
                -webkit-print-color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
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

        /* Sin resultados */
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
    <!-- Barra de herramientas -->
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

    <!-- Documento PDF -->
    <div class="pdf-container">
        <!-- Cabecera -->
        <header class="pdf-header">
            <div class="pdf-logo">
                <img src="../assets/img/logo.png" alt="Tarfia">
            </div>
            <div class="pdf-title-block">
                <h1 class="pdf-title"><?= htmlspecialchars($titulo) ?></h1>
                <p class="pdf-subtitle"><?= htmlspecialchars($subtituloStr) ?></p>
            </div>
        </header>

        <!-- Metadatos -->
        <div class="pdf-meta">
            <div class="pdf-meta-item">
                <span class="pdf-meta-label">Total registros:</span>
                <span class="pdf-meta-value"><?= number_format($totalRegistros) ?></span>
            </div>
            <div class="pdf-meta-item">
                <span class="pdf-meta-label">Generado:</span>
                <span class="pdf-meta-value"><?= $fechaGeneracion ?></span>
            </div>
        </div>

        <?php if (count($socios) > 0): ?>
        <!-- Tabla -->
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
                <?php foreach ($socios as $i => $socio): ?>
                <tr>
                    <td class="pdf-row-num"><?= $i + 1 ?></td>
                    <?php foreach ($columnas as $col): ?>
                        <?php if (isset($columnasConfig[$col])): ?>
                            <td>
                                <?php if ($col === 'estado'): ?>
                                    <?php 
                                    $estado = $socio['Socio'] ?? '';
                                    $badgeClass = $estado === 'Socio' ? 'pdf-badge-socio' : 'pdf-badge-exsocio';
                                    ?>
                                    <span class="pdf-badge <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span>
                                <?php else: ?>
                                    <?= getCellValue($socio, $col, $nivelesMap) ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="pdf-empty">
            <div class="pdf-empty-icon">📋</div>
            <p>No se encontraron registros con los filtros seleccionados</p>
        </div>
        <?php endif; ?>

        <!-- Pie -->
        <footer class="pdf-footer">
            <span>Tarfia · Asociación de Padres de Alumnos</span>
            <span>Página 1 de 1</span>
        </footer>
    </div>
</body>
</html>
