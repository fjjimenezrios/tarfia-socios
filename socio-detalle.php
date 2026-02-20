<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cache.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: socios.php');
    exit;
}

// Obtener datos del socio
$st = $pdo->prepare("
    SELECT s.*, f.`Apellidos` AS familia_apellidos, f.`Id` AS familia_id, n.`Curso`
    FROM `Socios` s
    LEFT JOIN `Familias Socios` f ON f.`Id` = s.`IdFamilia`
    LEFT JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
    WHERE s.`Id` = ?
");
$st->execute([$id]);
$socio = $st->fetch();

if (!$socio) {
    header('Location: socios.php');
    exit;
}

$pageTitle = 'Ficha de ' . ($socio['Nombre'] ?? 'Socio');

$breadcrumbs = [
    ['url' => 'home.php', 'label' => 'Inicio'],
    ['url' => 'socios.php', 'label' => 'Socios'],
    ['label' => $socio['Nombre'] ?? 'Ficha']
];

// Obtener hermanos si tiene familia
$hermanos = [];
if ($socio['IdFamilia']) {
    $st = $pdo->prepare("
        SELECT s.`Id`, s.`Nombre`, n.`Curso`, s.`Socio/Ex Socio` AS estado
        FROM `Socios` s
        LEFT JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
        WHERE s.`IdFamilia` = ? AND s.`Id` != ?
        ORDER BY s.`Nivel` ASC
    ");
    $st->execute([$socio['IdFamilia'], $id]);
    $hermanos = $st->fetchAll();
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumbs.php';
?>
<div class="tarfia-flex mb-3">
    <h1 class="tarfia-page-title mb-0">Ficha del socio</h1>
    <div class="d-flex gap-2">
        <a href="socios-editar.php?id=<?= $id ?>" class="tarfia-btn tarfia-btn-primary">Editar</a>
        <a href="socios.php" class="tarfia-btn tarfia-btn-outline">Volver</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="tarfia-card">
            <div class="tarfia-card-header">
                <h2 class="tarfia-card-title"><?= htmlspecialchars($socio['Nombre'] ?? '—') ?></h2>
                <?php if ($socio['familia_apellidos']): ?>
                    <span class="tarfia-muted">Familia <?= htmlspecialchars($socio['familia_apellidos']) ?></span>
                <?php endif; ?>
            </div>
            <div class="tarfia-card-body">
                <div class="tarfia-detail-grid">
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Estado</span>
                        <span class="tarfia-detail-value">
                            <?php 
                            $estado = $socio['Socio/Ex Socio'] ?? '';
                            $estadoClass = $estado === 'Ex Socio' ? 'tarfia-status-inactive' : 'tarfia-status-active';
                            ?>
                            <span class="<?= $estadoClass ?>"><?= htmlspecialchars($estado ?: 'Sin especificar') ?></span>
                        </span>
                    </div>
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Curso</span>
                        <span class="tarfia-detail-value"><?= htmlspecialchars($socio['Curso'] ?? '—') ?></span>
                    </div>
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Cuota</span>
                        <span class="tarfia-detail-value"><?= $socio['Cuota'] !== null ? number_format((float) $socio['Cuota'], 2, ',', '.') . ' €' : '—' ?></span>
                    </div>
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Móvil</span>
                        <span class="tarfia-detail-value"><?= htmlspecialchars($socio['Móvil del socio'] ?? '—') ?></span>
                    </div>
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Fecha de admisión</span>
                        <span class="tarfia-detail-value"><?= $socio['Fecha de admisión'] ? date('d/m/Y', strtotime($socio['Fecha de admisión'])) : '—' ?></span>
                    </div>
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Familia</span>
                        <span class="tarfia-detail-value">
                            <?php if ($socio['familia_id']): ?>
                                <a href="familia-detalle.php?id=<?= (int) $socio['familia_id'] ?>" class="tarfia-familia-link"><?= htmlspecialchars($socio['familia_apellidos']) ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($socio['Observaciones']): ?>
                <div class="mt-3">
                    <span class="tarfia-detail-label">Observaciones</span>
                    <p class="tarfia-detail-value mt-1"><?= nl2br(htmlspecialchars($socio['Observaciones'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <?php if ($hermanos): ?>
        <div class="tarfia-card">
            <div class="tarfia-card-header">
                <h3 class="tarfia-card-title" style="font-size: 1rem;">Hermanos</h3>
            </div>
            <div class="tarfia-card-body p-0">
                <ul class="tarfia-list">
                    <?php foreach ($hermanos as $h): ?>
                    <li class="tarfia-list-item">
                        <a href="socio-detalle.php?id=<?= (int) $h['Id'] ?>">
                            <?= htmlspecialchars($h['Nombre']) ?>
                        </a>
                        <span class="tarfia-muted"><?= htmlspecialchars($h['Curso'] ?? '') ?></span>
                        <?php if (($h['estado'] ?? '') === 'Ex Socio'): ?>
                            <span class="tarfia-status-inactive" style="font-size:0.75rem">(Ex)</span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.tarfia-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}
.tarfia-detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.tarfia-detail-label {
    font-size: 0.8rem;
    color: var(--tarfia-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}
.tarfia-detail-value {
    font-size: 1rem;
    color: var(--tarfia-text);
}
.tarfia-status-active {
    color: var(--tarfia-success);
    font-weight: 500;
}
.tarfia-status-inactive {
    color: var(--tarfia-danger);
    font-weight: 500;
}
.tarfia-card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--tarfia-border);
}
.tarfia-card-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}
.tarfia-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.tarfia-list-item {
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--tarfia-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
}
.tarfia-list-item:last-child {
    border-bottom: none;
}
.tarfia-list-item a {
    color: var(--tarfia-accent);
    text-decoration: none;
    font-weight: 500;
}
.tarfia-list-item a:hover {
    text-decoration: underline;
}
</style>
<?php require __DIR__ . '/includes/footer.php'; ?>
