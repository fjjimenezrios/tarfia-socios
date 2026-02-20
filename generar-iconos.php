<?php
/**
 * Ejecutar UNA VEZ para generar los iconos PNG necesarios para PWA.
 * Acceder a: http://servidor.local:8080/generar-iconos.php
 * Luego borrar este archivo.
 */

$sizes = [192, 512];
$outputDir = __DIR__ . '/assets/img/';

if (!extension_loaded('gd')) {
    die('Error: La extensión GD de PHP no está instalada. Actívala en Web Station.');
}

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    
    // Colores Tarfia
    $navy = imagecolorallocate($img, 26, 39, 68);      // #1a2744
    $gold = imagecolorallocate($img, 247, 179, 43);    // #f7b32b  
    $white = imagecolorallocate($img, 255, 255, 255);
    
    // Fondo navy
    imagefill($img, 0, 0, $navy);
    
    // Escala
    $s = $size / 100;
    $cx = $size / 2;
    
    // Escudo dorado (forma simplificada)
    $points = [
        $cx, 8 * $s,                    // Punta superior
        $cx + 38 * $s, 18 * $s,         // Esquina derecha arriba
        $cx + 38 * $s, 58 * $s,         // Lado derecho
        $cx, 92 * $s,                   // Punta inferior
        $cx - 38 * $s, 58 * $s,         // Lado izquierdo
        $cx - 38 * $s, 18 * $s,         // Esquina izquierda arriba
    ];
    imagefilledpolygon($img, $points, $gold);
    
    // Barras blancas (derecha del escudo)
    $bw = 28 * $s;  // ancho barra
    $bh = 6 * $s;   // alto barra
    $bx = $cx + 4 * $s;
    
    imagefilledrectangle($img, $bx, 28 * $s, $bx + $bw, 28 * $s + $bh, $white);
    imagefilledrectangle($img, $bx, 40 * $s, $bx + $bw, 40 * $s + $bh, $white);
    imagefilledrectangle($img, $bx, 52 * $s, $bx + $bw, 52 * $s + $bh, $white);
    imagefilledrectangle($img, $bx, 64 * $s, $bx + 20 * $s, 64 * $s + $bh, $white);
    
    // Estrellas (izquierda) - círculos simples
    $starSize = 8 * $s;
    imagefilledellipse($img, $cx - 18 * $s, 35 * $s, $starSize, $starSize, $white);
    imagefilledellipse($img, $cx - 24 * $s, 50 * $s, $starSize, $starSize, $white);
    imagefilledellipse($img, $cx - 18 * $s, 65 * $s, $starSize, $starSize, $white);
    
    // Guardar PNG
    $filename = $outputDir . 'icon-' . $size . '.png';
    imagepng($img, $filename, 9);
    imagedestroy($img);
    
    echo "✓ Creado: icon-{$size}.png<br>";
}

echo "<br><strong>¡Iconos generados!</strong><br>";
echo "Ahora puedes borrar este archivo (generar-iconos.php).<br>";
echo "<br><a href='home.php'>Volver al inicio</a>";
