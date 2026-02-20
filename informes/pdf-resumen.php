<?php
/**
 * Generador de PDF - Resumen por Curso
 * Dashboard visual elegante
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Obtener datos
$niveles = $pdo->query("SELECT `Nivel`, `Curso` FROM `Niveles-Cursos` ORDER BY `Nivel`")->fetchAll(PDO::FETCH_ASSOC);
$nivelesMap = [];
foreach ($niveles as $n) {
    $nivelesMap[(int) $n['Nivel']] = $n['Curso'];
}

// Estadísticas por nivel
$statsPorNivel = [];
$sql = "SELECT 
    s.`Nivel`,
    s.`Socio/Ex Socio` AS Socio,
    COUNT(*) as total
FROM `Socios` s
GROUP BY s.`Nivel`, s.`Socio/Ex Socio`
ORDER BY s.`Nivel`";

foreach ($pdo->query($sql) as $row) {
    $nivel = (int) $row['Nivel'];
    $estado = $row['Socio'] ?? '';
    if (!isset($statsPorNivel[$nivel])) {
        $statsPorNivel[$nivel] = ['socios' => 0, 'exsocios' => 0, 'total' => 0];
    }
    if ($estado === 'Socio') {
        $statsPorNivel[$nivel]['socios'] += (int) $row['total'];
    } elseif ($estado === 'Ex Socio') {
        $statsPorNivel[$nivel]['exsocios'] += (int) $row['total'];
    }
    $statsPorNivel[$nivel]['total'] += (int) $row['total'];
}

// Totales globales
$totalSocios = 0;
$totalExSocios = 0;
$totalGeneral = 0;
foreach ($statsPorNivel as $stats) {
    $totalSocios += $stats['socios'];
    $totalExSocios += $stats['exsocios'];
    $totalGeneral += $stats['total'];
}

// Stats de familias
$totalFamilias = $pdo->query("SELECT COUNT(*) FROM `Familias Socios`")->fetchColumn();

// Cuotas
$cuotaAnual = 25;
$familiasSocios = $pdo->query("
    SELECT COUNT(DISTINCT s.`IdFamilia`) as total
    FROM `Socios` s
    WHERE s.`Socio/Ex Socio` = 'Socio'
    AND s.`Nivel` BETWEEN 0 AND 8
")->fetchColumn();
$ingresosMensuales = (int) $familiasSocios * $cuotaAnual;

// Calcular máximo para barras
$totales = array_column($statsPorNivel, 'total');
$maxTotal = !empty($totales) ? max($totales) : 1;

$titulo = 'Resumen por Curso';
$fechaGeneracion = date('d/m/Y H:i');
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
            --pdf-success: #22c55e;
            --pdf-warning: #f59e0b;
            --pdf-info: #0ea5e9;
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

        /* Cards de estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--pdf-surface);
            border: 1px solid var(--pdf-border);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }

        .stat-card-accent {
            border-left: 3px solid var(--pdf-accent);
        }

        .stat-card-success {
            border-left: 3px solid var(--pdf-success);
        }

        .stat-card-warning {
            border-left: 3px solid var(--pdf-warning);
        }

        .stat-card-info {
            border-left: 3px solid var(--pdf-info);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--pdf-primary);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.7rem;
            color: var(--pdf-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Sección */
        .pdf-section {
            margin-bottom: 1.5rem;
        }

        .pdf-section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--pdf-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--pdf-border);
        }

        /* Tabla con barras */
        .chart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .chart-table th {
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--pdf-muted);
            text-transform: uppercase;
            padding: 0.5rem;
            border-bottom: 1px solid var(--pdf-border);
        }

        .chart-table td {
            padding: 0.5rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--pdf-border);
        }

        .chart-curso {
            font-weight: 500;
            white-space: nowrap;
            width: 100px;
        }

        .chart-bar-cell {
            width: 50%;
        }

        .chart-bar-container {
            display: flex;
            height: 20px;
            background: var(--pdf-surface);
            border-radius: 3px;
            overflow: hidden;
        }

        .chart-bar-socios {
            background: linear-gradient(90deg, #22c55e, #16a34a);
            height: 100%;
            transition: width 0.3s;
        }

        .chart-bar-exsocios {
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            height: 100%;
            transition: width 0.3s;
        }

        .chart-numbers {
            text-align: right;
            width: 120px;
        }

        .chart-num-socios {
            color: #16a34a;
            font-weight: 600;
        }

        .chart-num-exsocios {
            color: #d97706;
            font-weight: 500;
        }

        .chart-num-total {
            color: var(--pdf-text);
            font-weight: 600;
        }

        /* Leyenda */
        .chart-legend {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin-top: 1rem;
            font-size: 0.75rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }

        .legend-dot-socios {
            background: #22c55e;
        }

        .legend-dot-exsocios {
            background: #fbbf24;
        }

        /* Footer */
        .pdf-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--pdf-border);
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: var(--pdf-muted);
        }

        /* Print */
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

            .stat-card,
            .chart-bar-socios,
            .chart-bar-exsocios,
            .legend-dot {
                -webkit-print-color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
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
                <p class="pdf-subtitle">Generado el <?= $fechaGeneracion ?></p>
            </div>
        </header>

        <!-- Stats globales -->
        <div class="stats-grid">
            <div class="stat-card stat-card-accent">
                <div class="stat-value"><?= number_format($totalGeneral) ?></div>
                <div class="stat-label">Total Alumnos</div>
            </div>
            <div class="stat-card stat-card-success">
                <div class="stat-value"><?= number_format($totalSocios) ?></div>
                <div class="stat-label">Socios Activos</div>
            </div>
            <div class="stat-card stat-card-info">
                <div class="stat-value"><?= number_format($totalFamilias) ?></div>
                <div class="stat-label">Familias</div>
            </div>
            <div class="stat-card stat-card-warning">
                <div class="stat-value"><?= number_format($ingresosMensuales) ?> €</div>
                <div class="stat-label">Cuotas / Año</div>
            </div>
        </div>

        <!-- Desglose por curso -->
        <section class="pdf-section">
            <h2 class="pdf-section-title">Desglose por Curso</h2>
            
            <table class="chart-table">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Distribución</th>
                        <th style="text-align: right;">Socios</th>
                        <th style="text-align: right;">Ex Socios</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($niveles as $n): ?>
                        <?php 
                        $nivel = (int) $n['Nivel'];
                        $stats = $statsPorNivel[$nivel] ?? ['socios' => 0, 'exsocios' => 0, 'total' => 0];
                        $pctSocios = $maxTotal > 0 ? ($stats['socios'] / $maxTotal) * 100 : 0;
                        $pctExSocios = $maxTotal > 0 ? ($stats['exsocios'] / $maxTotal) * 100 : 0;
                        ?>
                        <tr>
                            <td class="chart-curso"><?= htmlspecialchars($n['Curso']) ?></td>
                            <td class="chart-bar-cell">
                                <div class="chart-bar-container">
                                    <div class="chart-bar-socios" style="width: <?= round($pctSocios) ?>%"></div>
                                    <div class="chart-bar-exsocios" style="width: <?= round($pctExSocios) ?>%"></div>
                                </div>
                            </td>
                            <td class="chart-numbers">
                                <span class="chart-num-socios"><?= $stats['socios'] ?></span>
                            </td>
                            <td class="chart-numbers">
                                <span class="chart-num-exsocios"><?= $stats['exsocios'] ?></span>
                            </td>
                            <td class="chart-numbers">
                                <span class="chart-num-total"><?= $stats['total'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight: 600; background: var(--pdf-surface);">
                        <td class="chart-curso">TOTAL</td>
                        <td></td>
                        <td class="chart-numbers">
                            <span class="chart-num-socios"><?= $totalSocios ?></span>
                        </td>
                        <td class="chart-numbers">
                            <span class="chart-num-exsocios"><?= $totalExSocios ?></span>
                        </td>
                        <td class="chart-numbers">
                            <span class="chart-num-total"><?= $totalGeneral ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="chart-legend">
                <div class="legend-item">
                    <span class="legend-dot legend-dot-socios"></span>
                    <span>Socios Activos</span>
                </div>
                <div class="legend-item">
                    <span class="legend-dot legend-dot-exsocios"></span>
                    <span>Ex Socios</span>
                </div>
            </div>
        </section>

        <footer class="pdf-footer">
            <span>Tarfia · Asociación de Padres de Alumnos</span>
            <span>Página 1 de 1</span>
        </footer>
    </div>
</body>
</html>
