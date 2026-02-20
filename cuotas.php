<?php
try {
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/cache.php';
} catch (Throwable $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Error al cargar cuotas</h1><pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()) . '</pre>';
    exit;
}

$pageTitle = 'Cuotas';
$errorCuotas = null;

// Desactivar caché temporalmente para depuración
$cached = false; // cache_get('cuotas_data');
if (false && is_array($cached)) {
    $numFamiliasPagan = $cached['numFamiliasPagan'];
    $totalMensual = $cached['totalMensual'];
    $totalAnual = $cached['totalAnual'];
    $porNivel = $cached['porNivel'];
    $familiasPagan = $cached['familiasPagan'];
} else {
    try {
        // Condición: incluir TODOS excepto "Ex Socio" (comparación flexible)
        $condSocio = "TRIM(COALESCE(`Socio/Ex Socio`, '')) NOT LIKE '%Ex Socio%'";
        $condSocioJoin = "TRIM(COALESCE(s.`Socio/Ex Socio`, '')) NOT LIKE '%Ex Socio%'";
        
        // Niveles que pagan: 4EPO (0) a 2BACH (8)
        $nivelesQuePagan = [0, 1, 2, 3, 4, 5, 6, 7, 8];
        $placeholders = implode(',', $nivelesQuePagan);

        // UNA sola consulta optimizada para totales y desglose por nivel
        $st = $pdo->query("
            SELECT 
                s.`Nivel`,
                n.`Curso`,
                COUNT(DISTINCT s.`IdFamilia`) AS num_familias,
                SUM(fam_cuota.cuota) AS total_nivel
            FROM `Socios` s
            INNER JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
            INNER JOIN (
                SELECT `IdFamilia`, MAX(`Cuota`) AS cuota
                FROM `Socios`
                WHERE $condSocio AND `Nivel` IN ($placeholders) AND `IdFamilia` IS NOT NULL
                GROUP BY `IdFamilia`
            ) fam_cuota ON fam_cuota.`IdFamilia` = s.`IdFamilia`
            WHERE $condSocioJoin AND s.`Nivel` IN ($placeholders)
            GROUP BY s.`Nivel`, n.`Curso`
            ORDER BY s.`Nivel`
        ");
        $porNivel = $st->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular totales
        $numFamiliasPagan = 0;
        $totalMensual = 0;
        $seenFamilias = [];
        
        // Query simplificada para totales globales
        $st = $pdo->query("
            SELECT COUNT(*) AS num_familias, SUM(cuota) AS total
            FROM (
                SELECT `IdFamilia`, MAX(`Cuota`) AS cuota
                FROM `Socios`
                WHERE $condSocio AND `Nivel` IN ($placeholders) AND `IdFamilia` IS NOT NULL
                GROUP BY `IdFamilia`
            ) t
        ");
        $totales = $st->fetch(PDO::FETCH_ASSOC);
        $numFamiliasPagan = (int) ($totales['num_familias'] ?? 0);
        $totalMensual = (float) ($totales['total'] ?? 0);
        $totalAnual = $totalMensual * 12;

        // Lista de familias que pagan (con detalle) - una sola query
        $st = $pdo->query("
            SELECT 
                f.`Id`,
                f.`Apellidos`,
                MAX(s.`Cuota`) AS cuota,
                GROUP_CONCAT(DISTINCT s.`Nombre` ORDER BY s.`Nombre` SEPARATOR ', ') AS socios,
                GROUP_CONCAT(DISTINCT n.`Curso` ORDER BY s.`Nivel` SEPARATOR ', ') AS niveles
            FROM `Familias Socios` f
            INNER JOIN `Socios` s ON s.`IdFamilia` = f.`Id`
            INNER JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
            WHERE $condSocioJoin AND s.`Nivel` IN ($placeholders)
            GROUP BY f.`Id`, f.`Apellidos`
            ORDER BY f.`Apellidos`
        ");
        $familiasPagan = $st->fetchAll(PDO::FETCH_ASSOC);
        
        // Guardar en caché
        cache_set('cuotas_data', [
            'numFamiliasPagan' => $numFamiliasPagan,
            'totalMensual' => $totalMensual,
            'totalAnual' => $totalAnual,
            'porNivel' => $porNivel,
            'familiasPagan' => $familiasPagan,
        ], CACHE_TTL_HOME);
    }

catch (PDOException $e) {
        $errorCuotas = $e->getMessage();
        $numFamiliasPagan = 0;
        $totalMensual = 0;
        $totalAnual = 0;
        $porNivel = [];
        $familiasPagan = [];
    }
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($errorCuotas !== null): ?>
<script>document.addEventListener('DOMContentLoaded', function() { TarfiaToast.error(<?= json_encode($errorCuotas) ?>, 'Error de datos'); });</script>
<?php endif; ?>

<h1 class="tarfia-page-title">Cuotas</h1>

<div class="tarfia-stats">
    <div class="tarfia-stat">
        <div class="tarfia-stat-label">Familias que pagan</div>
        <div class="tarfia-stat-value"><?= $numFamiliasPagan ?></div>
        <small class="tarfia-muted">Con socio en 4EPO–2BACH</small>
    </div>
    <div class="tarfia-stat stat-success">
        <div class="tarfia-stat-label">Ingresos mensuales</div>
        <div class="tarfia-stat-value"><?= number_format($totalMensual, 2, ',', '.') ?> €</div>
        <small class="tarfia-muted">Cuota por familia</small>
    </div>
    <div class="tarfia-stat stat-info">
        <div class="tarfia-stat-label">Ingresos anuales</div>
        <div class="tarfia-stat-value"><?= number_format($totalAnual, 2, ',', '.') ?> €</div>
        <small class="tarfia-muted">12 meses</small>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="tarfia-card">
            <div class="tarfia-card-header">Desglose por nivel</div>
            <div class="tarfia-card-body">
                <table class="tarfia-table">
                    <thead>
                        <tr>
                            <th>Nivel</th>
                            <th class="text-end">Familias</th>
                            <th class="text-end">Total/mes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porNivel as $n): ?>
                            <tr>
                                <td>
                                    <a href="socios.php?nivel=<?= (int) $n['Nivel'] ?>" class="tarfia-nivel-link-table">
                                        <?= htmlspecialchars($n['Curso'] ?? 'Nivel ' . $n['Nivel']) ?>
                                    </a>
                                </td>
                                <td class="text-end"><?= (int) $n['num_familias'] ?></td>
                                <td class="text-end"><?= number_format((float) $n['total_nivel'], 2, ',', '.') ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: 700; background: var(--tarfia-surface-hover);">
                            <td>Total</td>
                            <td class="text-end"><?= $numFamiliasPagan ?></td>
                            <td class="text-end"><?= number_format($totalMensual, 2, ',', '.') ?> €</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="tarfia-card">
            <div class="tarfia-card-header">
                <span>Familias que pagan cuota</span>
                <span class="tarfia-muted"><?= count($familiasPagan) ?> familias</span>
            </div>
            <div class="tarfia-table-wrap" style="max-height: 400px; overflow-y: auto;">
                <table class="tarfia-table" id="tablaFamiliasCuotas">
                    <thead>
                        <tr>
                            <th>Familia</th>
                            <th>Socios (4EPO–2BACH)</th>
                            <th>Niveles</th>
                            <th class="text-end">Cuota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($familiasPagan as $f): ?>
                            <tr>
                                <td>
                                    <a href="socios.php?familia=<?= (int) $f['Id'] ?>" class="tarfia-familia-link">
                                        <?= htmlspecialchars($f['Apellidos'] ?? '—') ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($f['socios'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($f['niveles'] ?? '—') ?></td>
                                <td class="text-end"><?= $f['cuota'] !== null ? number_format((float) $f['cuota'], 2, ',', '.') . ' €' : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="tarfia-card">
    <div class="tarfia-card-header">Resumen de ingresos</div>
    <div class="tarfia-card-body">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="text-center">
                    <div class="tarfia-muted mb-1">Cuota media por familia</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--tarfia-accent);">
                        <?= $numFamiliasPagan > 0 ? number_format($totalMensual / $numFamiliasPagan, 2, ',', '.') : '0,00' ?> €
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="text-center">
                    <div class="tarfia-muted mb-1">Ingresos trimestrales</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--tarfia-success);">
                        <?= number_format($totalMensual * 3, 2, ',', '.') ?> €
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="tarfia-muted mb-1">Ingresos semestrales</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--tarfia-info);">
                        <?= number_format($totalMensual * 6, 2, ',', '.') ?> €
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-end { text-align: right; }
.text-center { text-align: center; }
.tarfia-nivel-link-table {
    color: var(--tarfia-accent);
    text-decoration: none;
    font-weight: 500;
}
.tarfia-nivel-link-table:hover {
    color: var(--tarfia-accent-hover);
    text-decoration: underline;
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
