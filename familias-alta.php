<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cache.php';

$pageTitle = 'Alta de familia';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apellidos = trim($_POST['apellidos'] ?? '');
    $nombrePadre = trim($_POST['nombre_padre'] ?? '');
    $apellidosPadre = trim($_POST['apellidos_padre'] ?? '');
    $nombreMadre = trim($_POST['nombre_madre'] ?? '');
    $apellidosMadre = trim($_POST['apellidos_madre'] ?? '');
    $localidad = trim($_POST['localidad'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $movilPadre = trim($_POST['movil_padre'] ?? '');
    $movilMadre = trim($_POST['movil_madre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($apellidos === '') {
        $error = 'Los apellidos de la familia son obligatorios.';
    } elseif ($nombrePadre === '' || $apellidosPadre === '' || $movilPadre === '') {
        $error = 'Los datos del padre (nombre, apellidos y móvil) son obligatorios.';
    } elseif ($nombreMadre === '' || $apellidosMadre === '' || $movilMadre === '') {
        $error = 'Los datos de la madre (nombre, apellidos y móvil) son obligatorios.';
    } elseif ($email === '') {
        $error = 'El email es obligatorio.';
    } elseif ($direccion === '') {
        $error = 'La dirección es obligatoria.';
    } elseif ($localidad === '') {
        $error = 'La localidad es obligatoria.';
    } else {
        try {
            $sql = "INSERT INTO `Familias Socios` (
                `Apellidos`, `Nombre padre`, `Apellidos padre`, `Nombre madre`, `Apellidos madre`,
                `Localidad`, `Teléfono`, `Movil Padre`, `Movil Madre`, `e-mail`, `Dirección`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $st = $pdo->prepare($sql);
            $st->execute([
                $apellidos,
                $nombrePadre ?: null,
                $apellidosPadre ?: null,
                $nombreMadre ?: null,
                $apellidosMadre ?: null,
                $localidad ?: null,
                $telefono ?: null,
                $movilPadre ?: null,
                $movilMadre ?: null,
                $email ?: null,
                $direccion ?: null,
            ]);
            cache_invalidate_on_new_familia();
            header('Location: familias.php?ok=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="tarfia-flex mb-3">
    <h1 class="tarfia-page-title mb-0">Alta de familia</h1>
</div>

<?php if ($error): ?>
    <script>document.addEventListener('DOMContentLoaded', function() { TarfiaToast.error(<?= json_encode($error) ?>); });</script>
<?php endif; ?>

<div class="tarfia-card">
    <div class="tarfia-card-body">
        <form method="post" action="familias-alta.php">
            <div class="row g-3">
                <div class="col-12">
                    <label for="apellidos" class="tarfia-form-label">Apellidos de la familia <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="text" class="tarfia-input" id="apellidos" name="apellidos" required value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>">
                </div>
                
                <div class="col-12"><hr class="my-2"><h6 class="tarfia-muted">Datos del padre</h6></div>
                <div class="col-md-4">
                    <label for="nombre_padre" class="tarfia-form-label">Nombre <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="text" class="tarfia-input" id="nombre_padre" name="nombre_padre" required value="<?= htmlspecialchars($_POST['nombre_padre'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="apellidos_padre" class="tarfia-form-label">Apellidos <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="text" class="tarfia-input" id="apellidos_padre" name="apellidos_padre" required value="<?= htmlspecialchars($_POST['apellidos_padre'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="movil_padre" class="tarfia-form-label">Móvil <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="tel" class="tarfia-input" id="movil_padre" name="movil_padre" required value="<?= htmlspecialchars($_POST['movil_padre'] ?? '') ?>">
                </div>
                
                <div class="col-12"><hr class="my-2"><h6 class="tarfia-muted">Datos de la madre</h6></div>
                <div class="col-md-4">
                    <label for="nombre_madre" class="tarfia-form-label">Nombre <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="text" class="tarfia-input" id="nombre_madre" name="nombre_madre" required value="<?= htmlspecialchars($_POST['nombre_madre'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="apellidos_madre" class="tarfia-form-label">Apellidos <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="text" class="tarfia-input" id="apellidos_madre" name="apellidos_madre" required value="<?= htmlspecialchars($_POST['apellidos_madre'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="movil_madre" class="tarfia-form-label">Móvil <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="tel" class="tarfia-input" id="movil_madre" name="movil_madre" required value="<?= htmlspecialchars($_POST['movil_madre'] ?? '') ?>">
                </div>
                
                <div class="col-12"><hr class="my-2"><h6 class="tarfia-muted">Contacto y dirección</h6></div>
                <div class="col-md-6">
                    <label for="telefono" class="tarfia-form-label">Teléfono fijo</label>
                    <input type="tel" class="tarfia-input" id="telefono" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="email" class="tarfia-form-label">Email <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="email" class="tarfia-input" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="col-md-8">
                    <label for="direccion" class="tarfia-form-label">Dirección <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="text" class="tarfia-input" id="direccion" name="direccion" required value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="localidad" class="tarfia-form-label">Localidad <span style="color:var(--tarfia-danger)">*</span></label>
                    <input type="text" class="tarfia-input" id="localidad" name="localidad" required value="<?= htmlspecialchars($_POST['localidad'] ?? '') ?>">
                </div>
                
                <div class="col-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="tarfia-btn tarfia-btn-primary">Guardar</button>
                    <a href="familias.php" class="tarfia-btn tarfia-btn-outline">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
