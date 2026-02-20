<?php
/**
 * API para dar de baja un socio (cambiar estado a Ex Socio).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    $response['message'] = 'ID de socio no válido';
    echo json_encode($response);
    exit;
}

try {
    // Verificar que el socio existe
    $st = $pdo->prepare("SELECT `Id`, `Socio/Ex Socio` FROM `Socios` WHERE `Id` = ?");
    $st->execute([$id]);
    $socio = $st->fetch();
    
    if (!$socio) {
        $response['message'] = 'Socio no encontrado';
        echo json_encode($response);
        exit;
    }
    
    if (($socio['Socio/Ex Socio'] ?? '') === 'Ex Socio') {
        $response['message'] = 'Este socio ya está dado de baja';
        echo json_encode($response);
        exit;
    }
    
    // Dar de baja: cambiar estado a Ex Socio y registrar fecha
    $sql = "UPDATE `Socios` SET `Socio/Ex Socio` = 'Ex Socio' WHERE `Id` = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
    
    cache_invalidate_on_new_socio();
    
    $response['success'] = true;
    $response['message'] = 'Socio dado de baja correctamente';
} catch (PDOException $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
