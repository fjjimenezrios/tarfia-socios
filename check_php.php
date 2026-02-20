<?php
/**
 * Comprueba si PHP tiene las extensiones necesarias para la app.
 * Abre en el navegador: http://servidor.local:8080/check_php.php
 * Borra o protege este archivo en producción.
 */
header('Content-Type: text/html; charset=utf-8');
echo '<h1>Comprobación PHP — Tarfia Socios</h1>';
echo '<p>Versión PHP: <strong>' . phpversion() . '</strong></p>';

$needed = ['pdo', 'pdo_mysql', 'session', 'json'];
echo '<h2>Extensiones necesarias</h2><ul>';
foreach ($needed as $ext) {
    $ok = extension_loaded($ext);
    echo '<li><strong>' . htmlspecialchars($ext) . '</strong>: ' . ($ok ? '✓ cargada' : '✗ no cargada') . '</li>';
}
echo '</ul>';

if (!extension_loaded('pdo_mysql')) {
    echo '<p style="color:red;"><strong>Falta pdo_mysql.</strong> En Synology: Panel de control → Servicios web / Web Station → PHP → activa <em>PDO MySQL</em>.</p>';
}

echo '<h2>Todas las extensiones cargadas</h2><pre>' . implode(', ', get_loaded_extensions()) . '</pre>';
