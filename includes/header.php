<?php
// Calcular la ruta base para assets
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if (strpos($baseUrl, '/informes') !== false || strpos($baseUrl, '/api') !== false) {
    $baseUrl = dirname($baseUrl);
}
$baseUrl = rtrim($baseUrl, '/') . '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a2744">
    <meta name="description" content="Base de datos de socios y familias de Tarfía">
    <!-- Open Graph / WhatsApp / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="TarfíaDB">
    <meta property="og:description" content="Base de datos de socios y familias de Tarfía">
    <meta property="og:image" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $baseUrl ?>assets/img/og-image.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="TarfíaDB">
    <meta name="twitter:description" content="Base de datos de socios y familias de Tarfía">
    <meta name="twitter:image" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $baseUrl ?>assets/img/og-image.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TarfíaDB">
    <meta name="application-name" content="TarfíaDB">
    <meta name="msapplication-TileColor" content="#1a2744">
    <link rel="manifest" href="<?= $baseUrl ?>manifest.json">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= $baseUrl ?>favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= $baseUrl ?>assets/img/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= $baseUrl ?>assets/img/icon-512.png">
    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $baseUrl ?>assets/img/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="192x192" href="<?= $baseUrl ?>assets/img/icon-192.png">
    <title>TarfíaDB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="//cdn.datatables.net/2.3.7/css/dataTables.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <?php $cssVersion = filemtime(__DIR__ . '/../assets/css/app.css') ?: time(); ?>
    <link href="<?= $baseUrl ?>assets/css/app.css?v=<?= $cssVersion ?>" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<!-- Overlay para sidebar móvil -->
<div class="tarfia-sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar móvil -->
<aside class="tarfia-sidebar" id="sidebar">
    <div class="tarfia-sidebar-header">
        <img src="<?= $baseUrl ?>assets/img/logo-blanco.png" alt="Tarfia" class="tarfia-sidebar-logo">
        <button type="button" class="tarfia-sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <nav class="tarfia-sidebar-nav">
        <a href="<?= $baseUrl ?>home.php" class="<?= ($pageTitle ?? '') === 'Inicio' ? 'active' : '' ?>"><i class="fas fa-home"></i> Inicio</a>
        <a href="<?= $baseUrl ?>socios.php" class="<?= in_array($pageTitle ?? '', ['Socios', 'Alta de socio']) ? 'active' : '' ?>"><i class="fas fa-users"></i> Socios</a>
        <a href="<?= $baseUrl ?>familias.php" class="<?= ($pageTitle ?? '') === 'Familias' ? 'active' : '' ?>"><i class="fas fa-house-user"></i> Familias</a>
        <a href="<?= $baseUrl ?>cuotas.php" class="<?= ($pageTitle ?? '') === 'Cuotas' ? 'active' : '' ?>"><i class="fas fa-euro-sign"></i> Cuotas</a>
        <a href="<?= $baseUrl ?>informes.php" class="<?= ($pageTitle ?? '') === 'Informes' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Informes</a>
    </nav>
    <div class="tarfia-sidebar-footer">
        <div class="tarfia-sidebar-user">
            <i class="fas fa-user-circle"></i>
            <span><?= htmlspecialchars($_SESSION['user'] ?? '') ?></span>
        </div>
        <div class="tarfia-sidebar-actions">
            <button type="button" class="tarfia-sidebar-theme" id="sidebarThemeToggle" title="Cambiar tema">
                <i class="fas fa-moon" id="sidebarThemeIcon"></i>
                <span>Tema</span>
            </button>
            <a href="<?= $baseUrl ?>login.php?logout=1" class="tarfia-sidebar-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Salir</span>
            </a>
        </div>
    </div>
</aside>

<!-- Topbar -->
<nav class="tarfia-nav">
    <div class="container">
        <button type="button" class="tarfia-nav-toggle" aria-label="Menú" id="navToggle">
            <i class="fas fa-bars"></i>
        </button>
        <a class="tarfia-brand" href="<?= $baseUrl ?>home.php">
            <img src="<?= $baseUrl ?>assets/img/logo-blanco.png" alt="Tarfia" class="tarfia-logo">
        </a>
        <ul class="tarfia-nav-links">
            <li><a href="<?= $baseUrl ?>home.php" class="<?= ($pageTitle ?? '') === 'Inicio' ? 'active' : '' ?>"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="<?= $baseUrl ?>socios.php" class="<?= in_array($pageTitle ?? '', ['Socios', 'Alta de socio']) ? 'active' : '' ?>"><i class="fas fa-users"></i> Socios</a></li>
            <li><a href="<?= $baseUrl ?>familias.php" class="<?= ($pageTitle ?? '') === 'Familias' ? 'active' : '' ?>"><i class="fas fa-house-user"></i> Familias</a></li>
            <li><a href="<?= $baseUrl ?>cuotas.php" class="<?= ($pageTitle ?? '') === 'Cuotas' ? 'active' : '' ?>"><i class="fas fa-euro-sign"></i> Cuotas</a></li>
            <li><a href="<?= $baseUrl ?>informes.php" class="<?= ($pageTitle ?? '') === 'Informes' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Informes</a></li>
        </ul>
        <div class="tarfia-nav-user">
            <button type="button" class="tarfia-theme-toggle" id="themeToggle" title="Cambiar tema">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>
            <span class="user-name"><?= htmlspecialchars($_SESSION['user'] ?? '') ?></span>
            <a href="<?= $baseUrl ?>login.php?logout=1" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</nav>
<main class="tarfia-main">
    <div class="container">
