<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cache.php';

$pageTitle = 'Alta de socio';
$familias = cache_get('familias_list');
if (!is_array($familias)) {
    $familias = $pdo->query("SELECT `Id`, `Apellidos` FROM `Familias Socios` ORDER BY `Apellidos`")->fetchAll(PDO::FETCH_ASSOC);
    cache_set('familias_list', $familias, CACHE_TTL_LISTS);
}
$niveles = cache_get('niveles_list');
if (!is_array($niveles)) {
    $niveles = $pdo->query("SELECT `Nivel`, `Curso` FROM `Niveles-Cursos` ORDER BY `Nivel`")->fetchAll(PDO::FETCH_ASSOC);
    cache_set('niveles_list', $niveles, CACHE_TTL_LISTS);
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $idFamilia = $_POST['id_familia'] !== '' ? (int) $_POST['id_familia'] : null;
    $nivel = $_POST['nivel'] !== '' ? (int) $_POST['nivel'] : null;
    $cuota = $_POST['cuota'] !== '' ? (float) str_replace(',', '.', $_POST['cuota']) : null;
    $estado = trim($_POST['estado'] ?? '');
    $movil = trim($_POST['movil'] ?? '');
    $fechaAdmision = trim($_POST['fecha_admision'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    } elseif ($idFamilia === null) {
        $error = 'La familia es obligatoria.';
    } elseif ($nivel === null) {
        $error = 'El nivel/curso es obligatorio.';
    } elseif ($cuota === null) {
        $error = 'La cuota es obligatoria.';
    } elseif ($estado === '') {
        $error = 'El estado es obligatorio.';
    } elseif ($movil === '') {
        $error = 'El móvil es obligatorio.';
    } elseif ($fechaAdmision === '') {
        $error = 'La fecha de admisión es obligatoria.';
    } else {
        try {
            $sql = "INSERT INTO `Socios` (
                `Nombre`, `IdFamilia`, `Nivel`, `Cuota`, `Socio/Ex Socio`, `Móvil del socio`, `Fecha de admisión`, `Observaciones`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $st = $pdo->prepare($sql);
            $st->execute([
                $nombre,
                $idFamilia,
                $nivel,
                $cuota,
                $estado ?: null,
                $movil ?: null,
                $fechaAdmision ?: null,
                $observaciones ?: null,
            ]);
            cache_invalidate_on_new_socio();
            header('Location: socios.php?ok=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<h1 class="tarfia-page-title">Alta de socio</h1>

<div class="tarfia-alert tarfia-alert-info" style="background: var(--tarfia-accent-light); border: 1px solid var(--tarfia-accent); color: var(--tarfia-text); border-radius: var(--tarfia-radius-sm); padding: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.75rem;">
    <i class="fas fa-info-circle" style="color: var(--tarfia-accent); font-size: 1.25rem;"></i>
    <span><strong>Importante:</strong> Antes de añadir un socio, asegúrate de que su familia ya existe en el sistema. 
    Si no existe, <a href="familias-alta.php" style="color: var(--tarfia-accent); font-weight: 600;">crea la familia primero</a>.</span>
</div>

<?php if ($error): ?>
    <script>document.addEventListener('DOMContentLoaded', function() { TarfiaToast.error(<?= json_encode($error) ?>); });</script>
<?php endif; ?>
<div class="tarfia-card">
    <div class="tarfia-card-body">
        <form method="post" action="socios-alta.php">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre" class="tarfia-form-label">Nombre <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="text" class="tarfia-input" id="nombre" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="id_familia" class="tarfia-form-label">
                        Familia <span style="color:var(--tarfia-danger)">*</span>
                        <a href="familias-alta.php" class="tarfia-link-help" title="Crear nueva familia">
                            <i class="fas fa-plus-circle"></i> Nueva familia
                        </a>
                    </label>
                    <select class="tarfia-select" id="id_familia" name="id_familia" required>
                        <option value="">— Seleccionar familia —</option>
                        <?php foreach ($familias as $f): ?>
                            <option value="<?= (int) $f['Id'] ?>" <?= (isset($_POST['id_familia']) && (string)$_POST['id_familia'] === (string)$f['Id']) ? 'selected' : '' ?>><?= htmlspecialchars($f['Apellidos'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="nivel" class="tarfia-form-label">Nivel / Curso <span style="color:var(--tarfia-danger)">*</span></label>
                    <select class="tarfia-select" id="nivel" name="nivel" required>
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($niveles as $n): ?>
                            <option value="<?= (int) $n['Nivel'] ?>" <?= (isset($_POST['nivel']) && (string)$_POST['nivel'] === (string)$n['Nivel']) ? 'selected' : '' ?>><?= htmlspecialchars($n['Curso']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="cuota" class="tarfia-form-label">Cuota <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="number" class="tarfia-input" id="cuota" name="cuota" placeholder="25" step="0.01" min="0" required value="<?= htmlspecialchars($_POST['cuota'] ?? '25') ?>">
                </div>
                <div class="col-md-4">
                    <label for="estado" class="tarfia-form-label">Estado <span style="color:var(--tarfia-danger)">*</span></label>
                    <select class="tarfia-select" id="estado" name="estado" required>
                        <option value="Socio" <?= (($_POST['estado'] ?? 'Socio') === 'Socio') ? 'selected' : '' ?>>Socio</option>
                        <option value="Ex Socio" <?= (($_POST['estado'] ?? '') === 'Ex Socio') ? 'selected' : '' ?>>Ex Socio</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="movil" class="tarfia-form-label">Móvil <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="tel" class="tarfia-input" id="movil" name="movil" required value="<?= htmlspecialchars($_POST['movil'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="fecha_admision" class="tarfia-form-label">Fecha de admisión <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="date" class="tarfia-input" id="fecha_admision" name="fecha_admision" required value="<?= htmlspecialchars($_POST['fecha_admision'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-12">
                    <label for="observaciones" class="tarfia-form-label">Observaciones</label>
                    <textarea class="tarfia-input" id="observaciones" name="observaciones" rows="2" style="resize:vertical"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="tarfia-btn tarfia-btn-primary">Guardar</button>
                    <a href="socios.php" class="tarfia-btn tarfia-btn-outline">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
