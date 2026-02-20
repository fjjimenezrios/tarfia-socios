<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user'])) {
    // Calcular ruta base para redirección correcta desde cualquier subdirectorio
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = $scriptDir;
    if (strpos($scriptDir, '/informes') !== false || strpos($scriptDir, '/api') !== false) {
        $baseUrl = dirname($scriptDir);
    }
    $baseUrl = rtrim($baseUrl, '/') . '/';
    
    header('Location: ' . $baseUrl . 'login.php');
    exit;
}
