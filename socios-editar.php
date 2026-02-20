<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cache.php';

$pageTitle = 'Editar socio';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: socios.php');
    exit;
}

// Obtener datos del socio
$st = $pdo->prepare("SELECT * FROM `Socios` WHERE `Id` = ?");
$st->execute([$id]);
$socio = $st->fetch();

if (!$socio) {
    header('Location: socios.php');
    exit;
}

// Listas para dropdowns
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
            $sql = "UPDATE `Socios` SET 
                `Nombre` = ?, 
                `IdFamilia` = ?, 
                `Nivel` = ?, 
                `Cuota` = ?, 
                `Socio/Ex Socio` = ?, 
                `Móvil del socio` = ?, 
                `Fecha de admisión` = ?, 
                `Observaciones` = ?
                WHERE `Id` = ?";
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
                $id
            ]);
            cache_invalidate_on_new_socio();
            $success = true;
            
            // Recargar datos actualizados
            $st = $pdo->prepare("SELECT * FROM `Socios` WHERE `Id` = ?");
            $st->execute([$id]);
            $socio = $st->fetch();
        } catch (PDOException $e) {
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

$breadcrumbs = [
    ['url' => 'home.php', 'label' => 'Inicio'],
    ['url' => 'socios.php', 'label' => 'Socios'],
    ['label' => 'Editar']
];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumbs.php';
?>
<div class="tarfia-flex mb-3">
    <h1 class="tarfia-page-title mb-0">Editar socio</h1>
</div>

<?php if ($error): ?>
    <script>document.addEventListener('DOMContentLoaded', function() { TarfiaToast.error(<?= json_encode($error) ?>); });</script>
<?php endif; ?>
<?php if ($success): ?>
    <script>document.addEventListener('DOMContentLoaded', function() { TarfiaToast.success('Cambios guardados correctamente'); });</script>
<?php endif; ?>

<div class="tarfia-card">
    <div class="tarfia-card-body">
        <form method="post" action="socios-editar.php?id=<?= $id ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre" class="tarfia-form-label">Nombre <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="text" class="tarfia-input" id="nombre" name="nombre" required value="<?= htmlspecialchars($socio['Nombre'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="id_familia" class="tarfia-form-label">Familia <span style="color:var(--tarfia-danger)">*</span></label>
                    <select class="tarfia-select" id="id_familia" name="id_familia" required>
                        <option value="">— Seleccionar familia —</option>
                        <?php foreach ($familias as $f): ?>
                            <option value="<?= (int) $f['Id'] ?>" <?= ((string) $socio['IdFamilia'] === (string) $f['Id']) ? 'selected' : '' ?>><?= htmlspecialchars($f['Apellidos'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="nivel" class="tarfia-form-label">Nivel / Curso <span style="color:var(--tarfia-danger)">*</span></label>
                    <select class="tarfia-select" id="nivel" name="nivel" required>
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($niveles as $n): ?>
                            <option value="<?= (int) $n['Nivel'] ?>" <?= ((string) $socio['Nivel'] === (string) $n['Nivel']) ? 'selected' : '' ?>><?= htmlspecialchars($n['Curso']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="cuota" class="tarfia-form-label">Cuota <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="number" class="tarfia-input" id="cuota" name="cuota" placeholder="25" step="0.01" min="0" required value="<?= htmlspecialchars($socio['Cuota'] ?? '25') ?>">
                </div>
                <div class="col-md-4">
                    <label for="estado" class="tarfia-form-label">Estado <span style="color:var(--tarfia-danger)">*</span></label>
                    <select class="tarfia-select" id="estado" name="estado" required>
                        <option value="Socio" <?= ($socio['Socio/Ex Socio'] ?? '') === 'Socio' ? 'selected' : '' ?>>Socio</option>
                        <option value="Ex Socio" <?= ($socio['Socio/Ex Socio'] ?? '') === 'Ex Socio' ? 'selected' : '' ?>>Ex Socio</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="movil" class="tarfia-form-label">Móvil <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="tel" class="tarfia-input" id="movil" name="movil" required value="<?= htmlspecialchars($socio['Móvil del socio'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="fecha_admision" class="tarfia-form-label">Fecha de admisión <span style="color:var(--tarfia-danger)">*</span></label>
                    <?php 
                    $fechaAdmision = '';
                    if (!empty($socio['Fecha de admisión'])) {
                        $ts = strtotime($socio['Fecha de admisión']);
                        if ($ts !== false) {
                            $fechaAdmision = date('Y-m-d', $ts);
                        }
                    }
                    ?>
                    <input type="date" class="tarfia-input" id="fecha_admision" name="fecha_admision" required value="<?= htmlspecialchars($fechaAdmision) ?>">
                </div>
                <div class="col-12">
                    <label for="observaciones" class="tarfia-form-label">Observaciones</label>
                    <textarea class="tarfia-input" id="observaciones" name="observaciones" rows="2" style="resize:vertical"><?= htmlspecialchars($socio['Observaciones'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="tarfia-btn tarfia-btn-primary">Guardar cambios</button>
                    <a href="socios.php" class="tarfia-btn tarfia-btn-outline">Volver al listado</a>
                    <a href="socio-detalle.php?id=<?= $id ?>" class="tarfia-btn tarfia-btn-outline">Ver ficha</a>
                    <?php if (($socio['Socio/Ex Socio'] ?? '') !== 'Ex Socio'): ?>
                    <button type="button" class="tarfia-btn tarfia-btn-danger" id="btnDarBaja">Dar de baja</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btnBaja = document.getElementById('btnDarBaja');
    if (btnBaja) {
        btnBaja.addEventListener('click', function() {
            TarfiaToast.confirm({
                title: 'Dar de baja',
                message: '¿Estás seguro de que quieres dar de baja a este socio? Se cambiará su estado a "Ex Socio".',
                confirmText: 'Dar de baja',
                cancelText: 'Cancelar',
                danger: true
            }).then(function(confirmed) {
                if (!confirmed) return;
                
                var loadingToast = TarfiaToast.loading('Procesando baja...');
                var formData = new FormData();
                formData.append('id', <?= $id ?>);
                
                fetch('api/socio-baja.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        TarfiaToast.update(loadingToast, {
                            type: 'success',
                            message: 'Socio dado de baja correctamente'
                        });
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        TarfiaToast.update(loadingToast, {
                            type: 'error',
                            message: data.message || 'Error al procesar la baja'
                        });
                    }
                })
                .catch(function(error) {
                    TarfiaToast.update(loadingToast, {
                        type: 'error',
                        message: 'Error de conexión con el servidor'
                    });
                });
            });
        });
    }
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
