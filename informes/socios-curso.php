<?php
/**
 * Informe de socios por curso - optimizado para imprimir/PDF
 * Diseño elegante y consistente
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Obtener datos por nivel
$sql = "
    SELECT 
        n.`Nivel`,
        n.`Curso`,
        COUNT(s.`Id`) AS Total,
        SUM(CASE WHEN s.`Socio/Ex Socio` = 'Socio' THEN 1 ELSE 0 END) AS Activos,
        SUM(CASE WHEN s.`Socio/Ex Socio` = 'Ex Socio' THEN 1 ELSE 0 END) AS ExSocios
    FROM `Niveles-Cursos` n
    LEFT JOIN `Socios` s ON s.`Nivel` = n.`Nivel`
    GROUP BY n.`Nivel`, n.`Curso`
    ORDER BY n.`Nivel` ASC
";
$niveles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Totales
$totalSocios = 0;
$totalActivos = 0;
$totalEx = 0;
foreach ($niveles as $n) {
    $totalSocios += (int) $n['Total'];
    $totalActivos += (int) $n['Activos'];
    $totalEx += (int) $n['ExSocios'];
}

// Detalle por nivel (solo activos)
$detallePorNivel = [];
$stDetalle = $pdo->query("
    SELECT s.`Nombre`, s.`Nivel`, n.`Curso`, f.`Apellidos` AS Familia
    FROM `Socios` s
    LEFT JOIN `Familias Socios` f ON f.`Id` = s.`IdFamilia`
    LEFT JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
    WHERE s.`Socio/Ex Socio` = 'Socio'
    ORDER BY s.`Nivel` ASC, f.`Apellidos` ASC, s.`Nombre` ASC
");
foreach ($stDetalle->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $nivel = $row['Nivel'] ?? 0;
    if (!isset($detallePorNivel[$nivel])) {
        $detallePorNivel[$nivel] = [
            'curso' => $row['Curso'] ?? 'Nivel ' . $nivel,
            'socios' => []
        ];
    }
    $detallePorNivel[$nivel]['socios'][] = $row;
}

$fechaGeneracion = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Socios por Curso - Tarfia</title>
    <link rel="icon" type="image/png" href="../assets/img/logo-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --pdf-primary: #1a2744;
            --pdf-accent: #f7b32b;
            --pdf-success: #22c55e;
            --pdf-warning: #f59e0b;
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

        /* Toolbar */
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

        /* Container */
        .pdf-container {
            max-width: 210mm;
            margin: 80px auto 2rem;
            padding: 0 1rem;
        }

        /* Header */
        .pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1.25rem;
            border-bottom: 2px solid var(--pdf-primary);
            margin-bottom: 1.5rem;
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

        /* Stats grid */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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

        .stat-card-success .stat-value { color: #16a34a; }
        .stat-card-warning .stat-value { color: #d97706; }

        /* Table */
        .pdf-section {
            margin-bottom: 1.5rem;
        }

        .pdf-section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--pdf-primary);
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--pdf-border);
        }

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
            padding: 0.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        .pdf-table th:first-child {
            border-radius: 4px 0 0 4px;
            padding-left: 0.75rem;
        }

        .pdf-table th:last-child {
            border-radius: 0 4px 4px 0;
        }

        .pdf-table th.num {
            text-align: center;
            width: 70px;
        }

        .pdf-table tbody tr {
            border-bottom: 1px solid var(--pdf-border);
        }

        .pdf-table tbody tr:nth-child(even) {
            background: var(--pdf-surface);
        }

        .pdf-table td {
            padding: 0.5rem;
        }

        .pdf-table td:first-child {
            padding-left: 0.75rem;
        }

        .pdf-table td.num {
            text-align: center;
            font-weight: 500;
        }

        .pdf-table tfoot tr {
            background: var(--pdf-surface);
            font-weight: 600;
        }

        .num-success { color: #16a34a; }
        .num-warning { color: #d97706; }

        /* Detail sections */
        .nivel-grupo {
            margin-bottom: 1.25rem;
            page-break-inside: avoid;
        }

        .nivel-titulo {
            font-size: 0.8rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--pdf-primary) 0%, #2d3e5f 100%);
            color: white;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nivel-count {
            background: rgba(255,255,255,0.2);
            padding: 0.15rem 0.5rem;
            border-radius: 3px;
            font-size: 0.7rem;
        }

        .lista-socios {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.25rem 1rem;
            padding: 0.5rem 0.75rem;
            background: var(--pdf-surface);
            border-radius: 4px;
        }

        .socio-item {
            font-size: 0.75rem;
            padding: 0.25rem 0;
            border-bottom: 1px dotted var(--pdf-border);
        }

        .socio-familia {
            color: var(--pdf-muted);
            font-size: 0.65rem;
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

            .pdf-table thead,
            .stat-card,
            .nivel-titulo {
                -webkit-print-color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            .nivel-grupo {
                page-break-inside: avoid;
            }
        }

        /* Mobile */
        @media (max-width: 767px) {
            .pdf-toolbar {
                flex-direction: column;
                gap: 0.5rem;
                padding: 0.75rem 1rem;
            }
            .pdf-toolbar-actions {
                width: 100%;
            }
            .pdf-toolbar-btn {
                flex: 1;
                justify-content: center;
            }
            .pdf-container {
                margin-top: 110px;
            }
            .stats-row {
                grid-template-columns: 1fr;
            }
            .lista-socios {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Toolbar -->
    <div class="pdf-toolbar">
        <span class="pdf-toolbar-title">📊 Socios por Curso</span>
        <div class="pdf-toolbar-actions">
            <a href="../informes.php" class="pdf-toolbar-btn">
                ← Volver
            </a>
            <button onclick="window.print()" class="pdf-toolbar-btn pdf-toolbar-btn-primary">
                🖨️ Imprimir / PDF
            </button>
        </div>
    </div>

    <div class="pdf-container">
        <!-- Header -->
        <header class="pdf-header">
            <div class="pdf-logo">
                <img src="../assets/img/logo.png" alt="Tarfia">
            </div>
            <div class="pdf-title-block">
                <h1 class="pdf-title">Socios por Curso</h1>
                <p class="pdf-subtitle">Generado el <?= $fechaGeneracion ?></p>
            </div>
        </header>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($totalSocios) ?></div>
                <div class="stat-label">Total Registros</div>
            </div>
            <div class="stat-card stat-card-success">
                <div class="stat-value"><?= number_format($totalActivos) ?></div>
                <div class="stat-label">Socios Activos</div>
            </div>
            <div class="stat-card stat-card-warning">
                <div class="stat-value"><?= number_format($totalEx) ?></div>
                <div class="stat-label">Ex Socios</div>
            </div>
        </div>

        <!-- Resumen -->
        <section class="pdf-section">
            <h2 class="pdf-section-title">Resumen por Nivel</h2>
            <table class="pdf-table">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th class="num">Activos</th>
                        <th class="num">Ex Socios</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($niveles as $n): ?>
                    <tr>
                        <td><?= htmlspecialchars($n['Curso'] ?? 'Nivel ' . $n['Nivel']) ?></td>
                        <td class="num num-success"><?= (int) $n['Activos'] ?></td>
                        <td class="num num-warning"><?= (int) $n['ExSocios'] ?></td>
                        <td class="num"><?= (int) $n['Total'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>TOTAL</td>
                        <td class="num num-success"><?= $totalActivos ?></td>
                        <td class="num num-warning"><?= $totalEx ?></td>
                        <td class="num"><?= $totalSocios ?></td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <!-- Detalle -->
        <section class="pdf-section">
            <h2 class="pdf-section-title">Detalle de Socios Activos</h2>
            
            <?php foreach ($detallePorNivel as $nivel => $data): ?>
            <div class="nivel-grupo">
                <div class="nivel-titulo">
                    <span><?= htmlspecialchars($data['curso']) ?></span>
                    <span class="nivel-count"><?= count($data['socios']) ?> socios</span>
                </div>
                <div class="lista-socios">
                    <?php foreach ($data['socios'] as $s): ?>
                    <div class="socio-item">
                        <?= htmlspecialchars($s['Nombre']) ?>
                        <?php if ($s['Familia']): ?>
                            <span class="socio-familia">(<?= htmlspecialchars($s['Familia']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <!-- Footer -->
        <footer class="pdf-footer">
            <span>Tarfia · Asociación de Padres de Alumnos</span>
            <span>Página 1</span>
        </footer>
    </div>
</body>
</html>
