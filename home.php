<?php
try {
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/cache.php';
} catch (Throwable $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Error al cargar home</h1><pre>' . htmlspecialchars($e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString()) . '</pre>';
    exit;
}

$pageTitle = 'Inicio';
$errorHome = null;

$cached = cache_get('home');
if (is_array($cached)) {
    $totalSocios = (int) ($cached['totalSocios'] ?? 0);
    $totalFamilias = (int) ($cached['totalFamilias'] ?? 0);
    $porNivel = $cached['porNivel'] ?? [];
    $ultimosSocios = $cached['ultimosSocios'] ?? [];
    $chartLabels = $cached['chartLabels'] ?? [];
    $chartData = $cached['chartData'] ?? [];
} else {
    try {
        // Total de todos los socios (sin filtrar por estado)
        $st = $pdo->query("SELECT COUNT(*) AS n FROM `Socios`");
        $totalSocios = (int) $st->fetch()['n'];
        $st = $pdo->query("SELECT COUNT(*) AS n FROM `Familias Socios`");
        $totalFamilias = (int) $st->fetch()['n'];

        // Por nivel: TODOS los socios (sin filtrar por estado)
        $st = $pdo->query("
            SELECT s.`Nivel`, n.`Curso`, COUNT(*) AS cnt
            FROM `Socios` s
            LEFT JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
            GROUP BY s.`Nivel`, n.`Curso`
            ORDER BY s.`Nivel`
        ");
        $porNivel = $st->fetchAll(PDO::FETCH_ASSOC);
        
        // Datos para el gráfico
        $chartLabels = [];
        $chartData = [];
        foreach ($porNivel as $n) {
            $chartLabels[] = $n['Curso'] ?? 'Nivel ' . $n['Nivel'];
            $chartData[] = (int) $n['cnt'];
        }

        // Últimos socios añadidos
        $st = $pdo->query("
            SELECT s.`Id`, s.`Nombre`, s.`IdFamilia`, s.`Nivel`, s.`Cuota`, s.`Socio/Ex Socio`, s.`Fecha de admisión`, f.`Apellidos`
            FROM `Socios` s
            LEFT JOIN `Familias Socios` f ON f.`Id` = s.`IdFamilia`
            ORDER BY s.`Fecha de admisión` DESC, s.`Id` DESC
            LIMIT 10
        ");
        $ultimosSocios = $st->fetchAll(PDO::FETCH_ASSOC);

        cache_set('home', [
            'totalSocios' => $totalSocios,
            'totalFamilias' => $totalFamilias,
            'porNivel' => $porNivel,
            'ultimosSocios' => $ultimosSocios,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
        ], CACHE_TTL_HOME);
    } catch (PDOException $e) {
        $errorHome = $e->getMessage();
        $totalSocios = 0;
        $totalFamilias = 0;
        $porNivel = [];
        $ultimosSocios = [];
        $chartLabels = [];
        $chartData = [];
    }
}

require __DIR__ . '/includes/header.php';
if ($errorHome !== null):
?>
<script>document.addEventListener('DOMContentLoaded', function() { TarfiaToast.error(<?= json_encode($errorHome) ?>, 'Error de conexión'); });</script>
<div class="tarfia-alert tarfia-alert-danger" style="border-radius: var(--tarfia-radius-sm); padding: 1rem; margin-bottom: 1rem;">
    <strong>Error al cargar los datos:</strong><br>
    <code><?= htmlspecialchars($errorHome) ?></code>
    <hr>
    <small>Comprueba en <code>config/config.php</code> que DB_HOST, DB_NAME, DB_USER y DB_PASS sean correctos (mismo Synology: <code>localhost</code>).</small>
</div>
<?php endif; ?>
<h1 class="tarfia-page-title">Inicio</h1>

<!-- Accesos rápidos -->
<div class="tarfia-quick-access mb-4">
    <a href="socios-alta.php" class="tarfia-quick-btn">
        <span class="tarfia-quick-icon"><i class="fas fa-user-plus"></i></span>
        <span>Alta Socio</span>
    </a>
    <a href="familias-alta.php" class="tarfia-quick-btn">
        <span class="tarfia-quick-icon"><i class="fas fa-house-circle-check"></i></span>
        <span>Alta Familia</span>
    </a>
    <a href="cuotas.php" class="tarfia-quick-btn">
        <span class="tarfia-quick-icon"><i class="fas fa-euro-sign"></i></span>
        <span>Cuotas</span>
    </a>
    <a href="informes.php" class="tarfia-quick-btn">
        <span class="tarfia-quick-icon"><i class="fas fa-chart-pie"></i></span>
        <span>Informes</span>
    </a>
    <a href="informes/etiquetas.php" class="tarfia-quick-btn">
        <span class="tarfia-quick-icon"><i class="fas fa-tags"></i></span>
        <span>Etiquetas</span>
    </a>
</div>

<div class="tarfia-stats">
    <div class="tarfia-stat">
        <div class="tarfia-stat-label">Total socios</div>
        <div class="tarfia-stat-value"><?= $totalSocios ?></div>
        <a href="socios.php" class="tarfia-stat-link">Ver socios →</a>
    </div>
    <div class="tarfia-stat stat-success">
        <div class="tarfia-stat-label">Total familias</div>
        <div class="tarfia-stat-value"><?= $totalFamilias ?></div>
        <a href="familias.php" class="tarfia-stat-link">Ver familias →</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="tarfia-card">
            <div class="tarfia-card-header">
                <span>Distribución por curso</span>
            </div>
            <div class="tarfia-card-body">
                <canvas id="chartNivel" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="tarfia-card">
            <div class="tarfia-card-header">
                <span>Socios por nivel</span>
            </div>
            <div class="tarfia-card-body p-0">
                <ul class="list-unstyled mb-0 tarfia-nivel-list">
                    <?php foreach ($porNivel as $r): ?>
                        <li>
                            <a href="socios.php?nivel=<?= (int) $r['Nivel'] ?>" class="tarfia-nivel-link">
                                <span><?= htmlspecialchars($r['Curso'] ?? 'Nivel ' . $r['Nivel']) ?></span>
                                <strong><?= (int) $r['cnt'] ?></strong>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="tarfia-card">
    <div class="tarfia-card-header">
        <span>Últimos socios dados de alta</span>
        <a href="socios.php" class="tarfia-btn tarfia-btn-primary tarfia-btn-sm">Ver todos</a>
    </div>
    <div class="tarfia-table-wrap">
        <table class="tarfia-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Familia</th>
                    <th>Nivel</th>
                    <th>Fecha admisión</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimosSocios as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['Nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($s['Apellidos'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($s['Nivel'] ?? '—') ?></td>
                        <td><?= $s['Fecha de admisión'] ? date('d/m/Y', strtotime($s['Fecha de admisión'])) : '—' ?></td>
                        <td>
                            <a href="socio-detalle.php?id=<?= (int) ($s['Id'] ?? 0) ?>" class="tarfia-btn tarfia-btn-sm tarfia-btn-outline">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('chartNivel').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chartLabels ?? []) ?>,
            datasets: [{
                data: <?= json_encode($chartData ?? []) ?>,
                backgroundColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                    '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: { size: 12 }
                    }
                }
            }
        }
    });
});
</script>

<style>
.tarfia-quick-access {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.tarfia-quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    background: var(--tarfia-surface);
    border: 1px solid var(--tarfia-border);
    border-radius: var(--tarfia-radius);
    text-decoration: none;
    color: var(--tarfia-text);
    font-weight: 500;
    transition: all var(--tarfia-transition);
    min-width: 100px;
}
.tarfia-quick-btn:hover {
    background: var(--tarfia-surface-hover);
    border-color: var(--tarfia-accent);
    color: var(--tarfia-accent);
}
.tarfia-quick-icon {
    font-size: 1.5rem;
}
.tarfia-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--tarfia-border);
    font-weight: 600;
}
.tarfia-nivel-list {
    margin: 0;
    padding: 0;
}
.tarfia-nivel-list li {
    border-bottom: 1px solid var(--tarfia-border);
}
.tarfia-nivel-list li:last-child {
    border-bottom: none;
}
.tarfia-nivel-link {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    text-decoration: none;
    color: var(--tarfia-text);
    transition: background var(--tarfia-transition);
}
.tarfia-nivel-link:hover {
    background: var(--tarfia-surface-hover);
}
.tarfia-nivel-link strong {
    color: var(--tarfia-accent);
}
.stat-warning {
    border-left-color: var(--tarfia-warning) !important;
}
.stat-warning .tarfia-stat-value {
    color: var(--tarfia-warning);
}
</style>
<?php require __DIR__ . '/includes/footer.php'; ?>
