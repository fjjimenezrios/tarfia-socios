<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: familias.php');
    exit;
}

// Obtener datos de la familia
$st = $pdo->prepare("SELECT * FROM `Familias Socios` WHERE `Id` = ?");
$st->execute([$id]);
$familia = $st->fetch();

if (!$familia) {
    header('Location: familias.php');
    exit;
}

$pageTitle = 'Familia ' . ($familia['Apellidos'] ?? '');

// Obtener socios de la familia
$st = $pdo->prepare("
    SELECT s.*, n.`Curso`
    FROM `Socios` s
    LEFT JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
    WHERE s.`IdFamilia` = ?
    ORDER BY s.`Nivel` ASC, s.`Nombre` ASC
");
$st->execute([$id]);
$socios = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="tarfia-flex mb-3">
    <h1 class="tarfia-page-title mb-0">Familia <?= htmlspecialchars($familia['Apellidos'] ?? '') ?></h1>
    <div class="d-flex gap-2">
        <a href="familias-editar.php?id=<?= $id ?>" class="tarfia-btn tarfia-btn-primary">Editar</a>
        <a href="familias.php" class="tarfia-btn tarfia-btn-outline">Volver</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="tarfia-card">
            <div class="tarfia-card-header">
                <h2 class="tarfia-card-title">Datos del padre</h2>
            </div>
            <div class="tarfia-card-body">
                <div class="tarfia-detail-grid-2">
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Nombre completo</span>
                        <span class="tarfia-detail-value">
                            <?php 
                            $padre = trim(($familia['Nombre padre'] ?? '') . ' ' . ($familia['Apellidos padre'] ?? ''));
                            echo htmlspecialchars($padre ?: '—');
                            ?>
                        </span>
                    </div>
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Móvil</span>
                        <span class="tarfia-detail-value">
                            <?php if ($familia['Movil Padre']): ?>
                                <a href="tel:<?= htmlspecialchars($familia['Movil Padre']) ?>"><?= htmlspecialchars($familia['Movil Padre']) ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="tarfia-card">
            <div class="tarfia-card-header">
                <h2 class="tarfia-card-title">Datos de la madre</h2>
            </div>
            <div class="tarfia-card-body">
                <div class="tarfia-detail-grid-2">
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Nombre completo</span>
                        <span class="tarfia-detail-value">
                            <?php 
                            $madre = trim(($familia['Nombre madre'] ?? '') . ' ' . ($familia['Apellidos madre'] ?? ''));
                            echo htmlspecialchars($madre ?: '—');
                            ?>
                        </span>
                    </div>
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Móvil</span>
                        <span class="tarfia-detail-value">
                            <?php if ($familia['Movil Madre']): ?>
                                <a href="tel:<?= htmlspecialchars($familia['Movil Madre']) ?>"><?= htmlspecialchars($familia['Movil Madre']) ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="tarfia-card">
            <div class="tarfia-card-header">
                <h2 class="tarfia-card-title">Contacto</h2>
            </div>
            <div class="tarfia-card-body">
                <div class="tarfia-detail-grid-2">
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Teléfono fijo</span>
                        <span class="tarfia-detail-value">
                            <?php if ($familia['Teléfono']): ?>
                                <a href="tel:<?= htmlspecialchars($familia['Teléfono']) ?>"><?= htmlspecialchars($familia['Teléfono']) ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Email</span>
                        <span class="tarfia-detail-value">
                            <?php if ($familia['e-mail']): ?>
                                <a href="mailto:<?= htmlspecialchars($familia['e-mail']) ?>"><?= htmlspecialchars($familia['e-mail']) ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="tarfia-card">
            <div class="tarfia-card-header">
                <h2 class="tarfia-card-title">Dirección</h2>
            </div>
            <div class="tarfia-card-body">
                <div class="tarfia-detail-grid-2">
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Dirección</span>
                        <span class="tarfia-detail-value"><?= htmlspecialchars($familia['Dirección'] ?? '—') ?></span>
                    </div>
                    <div class="tarfia-detail-item">
                        <span class="tarfia-detail-label">Localidad</span>
                        <span class="tarfia-detail-value"><?= htmlspecialchars($familia['Localidad'] ?? '—') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12">
        <div class="tarfia-card">
            <div class="tarfia-card-header d-flex justify-content-between align-items-center">
                <h2 class="tarfia-card-title">Socios de la familia (<?= count($socios) ?>)</h2>
                <a href="socios-alta.php?familia=<?= $id ?>" class="tarfia-btn tarfia-btn-sm tarfia-btn-success">Añadir socio</a>
            </div>
            <?php if ($socios): ?>
            <div class="tarfia-table-wrap">
                <table class="tarfia-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Curso</th>
                            <th>Estado</th>
                            <th>Cuota</th>
                            <th>Móvil</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($socios as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['Nombre'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($s['Curso'] ?? '—') ?></td>
                            <td>
                                <?php 
                                $estado = $s['Socio/Ex Socio'] ?? '';
                                $estadoClass = $estado === 'Ex Socio' ? 'tarfia-status-inactive' : 'tarfia-status-active';
                                ?>
                                <span class="<?= $estadoClass ?>"><?= htmlspecialchars($estado ?: '—') ?></span>
                            </td>
                            <td><?= $s['Cuota'] !== null ? number_format((float) $s['Cuota'], 2, ',', '.') . ' €' : '—' ?></td>
                            <td><?= htmlspecialchars($s['Móvil del socio'] ?? '—') ?></td>
                            <td>
                                <div class="tarfia-acciones">
                                    <a href="socio-detalle.php?id=<?= (int) $s['Id'] ?>" class="tarfia-btn tarfia-btn-sm tarfia-btn-outline">Ver</a>
                                    <a href="socios-editar.php?id=<?= (int) $s['Id'] ?>" class="tarfia-btn tarfia-btn-sm tarfia-btn-primary">Editar</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="tarfia-card-body">
                <p class="tarfia-muted mb-0">Esta familia no tiene socios registrados.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.tarfia-detail-grid-2 {
    display: grid;
    grid-template-columns: 1fr;
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
.tarfia-detail-value a {
    color: var(--tarfia-accent);
    text-decoration: none;
}
.tarfia-detail-value a:hover {
    text-decoration: underline;
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
    font-size: 1rem;
    font-weight: 600;
}
</style>
<?php require __DIR__ . '/includes/footer.php'; ?>
