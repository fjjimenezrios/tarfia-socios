<?php
/**
 * Etiquetas postales para familias - formato estándar 3 columnas
 * Diseño elegante consistente con los PDFs
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Filtrar solo familias con socios activos
$soloActivos = isset($_GET['activos']) && $_GET['activos'] === '1';

$sql = "
    SELECT DISTINCT
        f.`Id`,
        f.`Apellidos`,
        f.`Dirección` AS Direccion,
        f.`Localidad`,
        COALESCE(f.`Nombre padre`, f.`Nombre madre`) AS NombreContacto
    FROM `Familias Socios` f
";

if ($soloActivos) {
    $sql .= "
    INNER JOIN `Socios` s ON s.`IdFamilia` = f.`Id`
        AND s.`Socio/Ex Socio` = 'Socio'
    ";
}

$sql .= " WHERE f.`Dirección` IS NOT NULL AND TRIM(f.`Dirección`) != ''";
$sql .= " ORDER BY f.`Apellidos` ASC";

$familias = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$titulo = $soloActivos ? 'Etiquetas - Socios Activos' : 'Etiquetas - Todas las Familias';
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
            line-height: 1.4;
            color: var(--pdf-text);
            background: #fff;
        }

        /* Barra de herramientas */
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

        .pdf-toolbar-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .pdf-toolbar-title {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .pdf-toolbar-filter {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
        }

        .pdf-toolbar-filter input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .pdf-toolbar-filter label {
            cursor: pointer;
            opacity: 0.9;
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

        /* Info bar */
        .pdf-info {
            margin-top: 70px;
            padding: 0.75rem 1.5rem;
            background: var(--pdf-surface);
            border-bottom: 1px solid var(--pdf-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: var(--pdf-muted);
        }

        .pdf-info-badge {
            background: var(--pdf-primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Contenedor de etiquetas */
        .etiquetas-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            padding: 15mm 10mm;
            max-width: 210mm;
            margin: 0 auto;
        }

        /* Cada etiqueta - tamaño Avery L7160 */
        .etiqueta {
            width: 63.5mm;
            height: 38.1mm;
            padding: 4mm 5mm;
            overflow: hidden;
            border: 1px dashed #ccc;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        .etiqueta-nombre {
            font-weight: 700;
            font-size: 10pt;
            color: var(--pdf-primary);
            margin-bottom: 2mm;
            line-height: 1.2;
        }

        .etiqueta-direccion {
            font-size: 9pt;
            color: var(--pdf-text);
            line-height: 1.3;
        }

        .etiqueta-localidad {
            font-size: 9pt;
            font-weight: 500;
            margin-top: 1.5mm;
            color: var(--pdf-text);
        }

        /* Print styles */
        @media print {
            .pdf-toolbar,
            .pdf-info {
                display: none !important;
            }

            body {
                padding: 0;
                background: white;
            }

            .etiquetas-container {
                margin: 0;
                padding: 0;
                max-width: none;
            }

            .etiqueta {
                border: none;
                page-break-inside: avoid;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }

        /* Empty state */
        .pdf-empty {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--pdf-muted);
        }

        .pdf-empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .pdf-empty h3 {
            font-size: 1.125rem;
            color: var(--pdf-text);
            margin-bottom: 0.5rem;
        }

        /* Mobile responsive toolbar */
        @media (max-width: 767px) {
            .pdf-toolbar {
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem 1rem;
            }
            .pdf-toolbar-left {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
            }
            .pdf-toolbar-actions {
                width: 100%;
            }
            .pdf-toolbar-btn {
                flex: 1;
                justify-content: center;
            }
            .pdf-info {
                margin-top: 130px;
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
            .etiquetas-container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            .etiqueta {
                width: 100%;
                height: auto;
                min-height: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Barra de herramientas -->
    <div class="pdf-toolbar">
        <div class="pdf-toolbar-left">
            <span class="pdf-toolbar-title">📬 Etiquetas Postales</span>
            <div class="pdf-toolbar-filter">
                <input type="checkbox" id="filtroActivos" <?= $soloActivos ? 'checked' : '' ?> onchange="aplicarFiltro()">
                <label for="filtroActivos">Solo familias con socios activos</label>
            </div>
        </div>
        <div class="pdf-toolbar-actions">
            <a href="../informes.php" class="pdf-toolbar-btn">
                ← Volver
            </a>
            <button onclick="window.print()" class="pdf-toolbar-btn pdf-toolbar-btn-primary">
                🖨️ Imprimir Etiquetas
            </button>
        </div>
    </div>

    <!-- Info bar -->
    <div class="pdf-info">
        <div>
            <span class="pdf-info-badge"><?= number_format(count($familias)) ?></span>
            etiquetas · Formato Avery L7160 (63.5 × 38.1 mm) · 3 columnas por hoja
        </div>
        <div>Generado: <?= $fechaGeneracion ?></div>
    </div>

    <?php if (count($familias) > 0): ?>
    <!-- Etiquetas -->
    <div class="etiquetas-container">
        <?php foreach ($familias as $f): ?>
        <div class="etiqueta">
            <div class="etiqueta-nombre">
                Familia <?= htmlspecialchars($f['Apellidos'] ?? '') ?>
            </div>
            <?php if (!empty($f['Direccion'])): ?>
            <div class="etiqueta-direccion">
                <?= htmlspecialchars($f['Direccion']) ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($f['Localidad'])): ?>
            <div class="etiqueta-localidad">
                <?= htmlspecialchars($f['Localidad']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="pdf-empty">
        <div class="pdf-empty-icon">📭</div>
        <h3>No hay etiquetas para generar</h3>
        <p>No se encontraron familias con dirección<?= $soloActivos ? ' que tengan socios activos' : '' ?>.</p>
    </div>
    <?php endif; ?>

    <script>
    function aplicarFiltro() {
        var activos = document.getElementById('filtroActivos').checked;
        window.location.href = 'etiquetas.php' + (activos ? '?activos=1' : '');
    }
    </script>
</body>
</html>
