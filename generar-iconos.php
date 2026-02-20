<?php
/**
 * Genera los iconos PWA y favicon para Tarfia Socios
 * Ejecutar UNA VEZ: http://servidor.local:8080/generar-iconos.php
 * Luego puedes borrar este archivo.
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Generador de Iconos PWA</h1>";

// Verificar GD
if (!extension_loaded('gd')) {
    die('<p style="color:red">❌ Error: La extensión GD de PHP no está instalada.</p>
         <p>Actívala en Web Station → PHP → Extensiones → GD</p>');
}

$outputDir = __DIR__ . '/assets/img/';
$logoSource = $outputDir . 'logo-pwa-source.png';

// Verificar que existe el logo fuente
if (!file_exists($logoSource)) {
    // Intentar usar logo-blanco.png como alternativa
    $logoSource = $outputDir . 'logo-blanco.png';
    if (!file_exists($logoSource)) {
        die('<p style="color:red">❌ Error: No se encontró el logo fuente.</p>
             <p>Asegúrate de que existe: assets/img/logo-pwa-source.png o assets/img/logo-blanco.png</p>');
    }
}

echo "<p>✓ Logo fuente encontrado: " . basename($logoSource) . "</p>";

// Colores Tarfia
$navyR = 26; $navyG = 39; $navyB = 68; // #1a2744

// Cargar logo
$logoInfo = getimagesize($logoSource);
$logoImg = imagecreatefrompng($logoSource);
if (!$logoImg) {
    die('<p style="color:red">❌ Error: No se pudo cargar el logo PNG.</p>');
}
imagealphablending($logoImg, true);
imagesavealpha($logoImg, true);

$logoW = imagesx($logoImg);
$logoH = imagesy($logoImg);

echo "<p>✓ Logo cargado: {$logoW}x{$logoH}px</p>";

// Generar iconos
$sizes = [
    ['size' => 192, 'name' => 'icon-192.png', 'padding' => 0.15],
    ['size' => 512, 'name' => 'icon-512.png', 'padding' => 0.15],
    ['size' => 180, 'name' => 'apple-touch-icon.png', 'padding' => 0.12],
    ['size' => 32, 'name' => 'favicon-32.png', 'padding' => 0.1],
    ['size' => 16, 'name' => 'favicon-16.png', 'padding' => 0.1],
];

foreach ($sizes as $config) {
    $size = $config['size'];
    $padding = $config['padding'];
    
    // Crear imagen con fondo navy
    $icon = imagecreatetruecolor($size, $size);
    $navy = imagecolorallocate($icon, $navyR, $navyG, $navyB);
    imagefill($icon, 0, 0, $navy);
    
    // Calcular tamaño del logo con padding
    $availableSize = $size * (1 - $padding * 2);
    
    // Mantener proporción del logo
    $ratio = min($availableSize / $logoW, $availableSize / $logoH);
    $newW = (int)($logoW * $ratio);
    $newH = (int)($logoH * $ratio);
    
    // Centrar
    $x = (int)(($size - $newW) / 2);
    $y = (int)(($size - $newH) / 2);
    
    // Copiar logo redimensionado
    imagecopyresampled($icon, $logoImg, $x, $y, 0, 0, $newW, $newH, $logoW, $logoH);
    
    // Guardar
    $filename = $outputDir . $config['name'];
    imagepng($icon, $filename, 9);
    imagedestroy($icon);
    
    echo "<p>✓ Creado: <strong>{$config['name']}</strong> ({$size}x{$size})</p>";
}

// Crear favicon.ico (multi-resolución)
echo "<h2>Creando favicon.ico...</h2>";

// Función para crear ICO (simplificada - solo 32x32)
$favicon32 = $outputDir . 'favicon-32.png';
if (file_exists($favicon32)) {
    // Leer el PNG de 32x32
    $png32 = file_get_contents($favicon32);
    
    // Crear ICO básico (header + 1 imagen)
    $ico = pack('vvv', 0, 1, 1); // Reserved, Type (1=ICO), Count
    
    // ICONDIRENTRY
    $ico .= pack('CCCCvvVV', 
        32, 32,  // Width, Height
        0,       // Color count
        0,       // Reserved
        1,       // Color planes
        32,      // Bits per pixel
        strlen($png32), // Size of image data
        22       // Offset to image data (6 + 16)
    );
    
    // Append PNG data directly (PNG ICO format)
    $ico .= $png32;
    
    file_put_contents($outputDir . 'favicon.ico', $ico);
    echo "<p>✓ Creado: <strong>favicon.ico</strong></p>";
    
    // También copiar a la raíz
    copy($outputDir . 'favicon.ico', __DIR__ . '/favicon.ico');
    echo "<p>✓ Copiado favicon.ico a la raíz</p>";
}

imagedestroy($logoImg);

// Limpiar archivos temporales
@unlink($outputDir . 'favicon-32.png');
@unlink($outputDir . 'favicon-16.png');

echo "<hr>";
echo "<h2 style='color:green'>✅ ¡Iconos generados correctamente!</h2>";
echo "<p>Archivos creados:</p>";
echo "<ul>";
echo "<li>assets/img/icon-192.png (PWA)</li>";
echo "<li>assets/img/icon-512.png (PWA)</li>";
echo "<li>assets/img/apple-touch-icon.png (iOS)</li>";
echo "<li>favicon.ico (navegadores)</li>";
echo "</ul>";

echo "<h3>Próximos pasos:</h3>";
echo "<ol>";
echo "<li>Verifica los iconos en <a href='assets/img/icon-192.png'>icon-192.png</a></li>";
echo "<li>Limpia la caché del navegador (Ctrl+Shift+R)</li>";
echo "<li>Borra este archivo (generar-iconos.php)</li>";
echo "</ol>";

echo "<p><a href='home.php' style='padding:10px 20px;background:#1a2744;color:white;text-decoration:none;border-radius:5px;'>← Volver al inicio</a></p>";
