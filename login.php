<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['user'] ?? '');
    $pass = $_POST['pass'] ?? '';
    if ($user === AUTH_USER && AUTH_PASS_HASH !== '' && password_verify($pass, AUTH_PASS_HASH)) {
        $_SESSION['user'] = $user;
        header('Location: home.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}

if (!empty($_SESSION['user'])) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — Tarfia Socios</title>
    <link rel="icon" type="image/png" href="assets/img/logo-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="tarfia-login-wrap">
    <div class="tarfia-login-card">
        <div class="tarfia-login-logo">
            <img src="assets/img/logo.png" alt="Tarfia" style="max-width: 200px; height: auto; margin-bottom: 1.5rem;">
        </div>
        <?php if ($error): ?>
            <script>document.addEventListener('DOMContentLoaded', function() { TarfiaToast.error(<?= json_encode($error) ?>); });</script>
        <?php endif; ?>
        <form method="post" action="login.php" class="tarfia-login">
            <label for="user" class="tarfia-form-label">Usuario</label>
            <input type="text" class="tarfia-input" id="user" name="user" required autofocus>
            <label for="pass" class="tarfia-form-label">Contraseña</label>
            <input type="password" class="tarfia-input" id="pass" name="pass" required>
            <button type="submit" class="tarfia-btn tarfia-btn-primary">Entrar</button>
        </form>
    </div>
</div>
</body>
</html>
