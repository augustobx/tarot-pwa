<?php
/**
 * Generador de imágenes para compartir lecturas en redes sociales
 * Crea una imagen estilizada con la pregunta y respuesta del oráculo
 */

session_start();
require_once 'db.php';

header('Content-Type: image/png');

// Obtener datos
$pregunta = $_GET['q'] ?? 'Tu pregunta';
$respuesta = $_GET['r'] ?? 'La respuesta del oráculo';
$tipo = $_GET['t'] ?? 'general'; // general, amor, dinero, trabajo

// Limitar longitud de texto
$pregunta = mb_substr($pregunta, 0, 100);
$respuesta = mb_substr($respuesta, 0, 300);

// Dimensiones de la imagen (Instagram Square)
$width = 1080;
$height = 1080;

// Crear imagen
$image = imagecreatetruecolor($width, $height);

// Colores según tipo de lectura
$backgrounds = [
    'amor' => ['#4a1942', '#2d0a29', '#fd79a8'],
    'dinero' => ['#1e3a20', '#0d1f0e', '#00b894'],
    'trabajo' => ['#2c3e50', '#1a252f', '#f39c12'],
    'general' => ['#2e1a47', '#1a0b2e', '#ffd700']
];

$theme = $backgrounds[$tipo] ?? $backgrounds['general'];
list($color1, $color2, $accent) = $theme;

// Convertir hex a RGB
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}

$rgb1 = hexToRgb($color1);
$rgb2 = hexToRgb($color2);
$rgbAccent = hexToRgb($accent);

// Crear gradiente de fondo
for ($i = 0; $i < $height; $i++) {
    $ratio = $i / $height;
    $r = $rgb1[0] + ($rgb2[0] - $rgb1[0]) * $ratio;
    $g = $rgb1[1] + ($rgb2[1] - $rgb1[1]) * $ratio;
    $b = $rgb1[2] + ($rgb2[2] - $rgb1[2]) * $ratio;
    
    $color = imagecolorallocate($image, $r, $g, $b);
    imagefilledrectangle($image, 0, $i, $width, $i + 1, $color);
}

// Colores para texto
$white = imagecolorallocate($image, 255, 255, 255);
$gold = imagecolorallocate($image, $rgbAccent[0], $rgbAccent[1], $rgbAccent[2]);
$lightPurple = imagecolorallocate($image, 188, 160, 217);

// Agregar estrellas decorativas
for ($i = 0; $i < 100; $i++) {
    $x = rand(0, $width);
    $y = rand(0, $height);
    $starSize = rand(1, 3);
    imagefilledellipse($image, $x, $y, $starSize, $starSize, $white);
}

// Paths a fuentes (usar fuentes del sistema o incluir en proyecto)
$fontTitle = __DIR__ . '/../assets/fonts/Cinzel-Bold.ttf';
$fontText = __DIR__ . '/../assets/fonts/Lato-Regular.ttf';

// Si no existen fuentes, usar números de fuente built-in
$useBuiltinFont = !file_exists($fontTitle);

// Logo/Título superior
if (!$useBuiltinFont) {
    imagettftext($image, 48, 0, 40, 120, $gold, $fontTitle, '🔮 Oráculo Místico');
} else {
    imagestring($image, 5, 40, 80, 'Oraculo Mistico', $gold);
}

// Caja para la pregunta
$boxY = 220;
$boxHeight = 200;
$boxColor = imagecolorallocatealpha($image, 0, 0, 0, 50); // Negro semi-transparente
imagefilledrectangle($image, 40, $boxY, $width - 40, $boxY + $boxHeight, $boxColor);

// Borde dorado
imagerectangle($image, 40, $boxY, $width - 40, $boxY + $boxHeight, $gold);
imagerectangle($image, 42, $boxY + 2, $width - 42, $boxY + $boxHeight - 2, $gold);

// Texto "Tu Pregunta:"
if (!$useBuiltinFont) {
    imagettftext($image, 24, 0, 60, $boxY + 50, $gold, $fontTitle, 'Tu Pregunta:');
} else {
    imagestring($image, 4, 60, $boxY + 20, 'Tu Pregunta:', $gold);
}

// La pregunta (word wrap)
$preguntaWrapped = wordwrap($pregunta, 50, "\n");
$lines = explode("\n", $preguntaWrapped);
$lineY = $boxY + 100;

foreach ($lines as $line) {
    if (!$useBuiltinFont) {
        imagettftext($image, 28, 0, 60, $lineY, $white, $fontText, $line);
        $lineY += 45;
    } else {
        imagestring($image, 3, 60, $lineY - 60, $line, $white);
        $lineY += 20;
    }
}

// Caja para la respuesta
$boxY2 = 480;
$boxHeight2 = 450;
imagefilledrectangle($image, 40, $boxY2, $width - 40, $boxY2 + $boxHeight2, $boxColor);
imagerectangle($image, 40, $boxY2, $width - 40, $boxY2 + $boxHeight2, $gold);
imagerectangle($image, 42, $boxY2 + 2, $width - 42, $boxY2 + $boxHeight2 - 2, $gold);

// Texto "El Oráculo Responde:"
if (!$useBuiltinFont) {
    imagettftext($image, 24, 0, 60, $boxY2 + 50, $gold, $fontTitle, 'El Oráculo Responde:');
} else {
    imagestring($image, 4, 60, $boxY2 + 20, 'El Oraculo Responde:', $gold);
}

// La respuesta (word wrap)
$respuestaWrapped = wordwrap($respuesta, 45, "\n");
$respuestaLines = explode("\n", $respuestaWrapped);
$lineY2 = $boxY2 + 110;

$maxLines = 10; // Limitar líneas
$counter = 0;
foreach ($respuestaLines as $line) {
    if ($counter >= $maxLines) {
        if (!$useBuiltinFont) {
            imagettftext($image, 24, 0, 60, $lineY2, $lightPurple, $fontText, '...');
        } else {
            imagestring($image, 3, 60, $lineY2 - 80, '...', $lightPurple);
        }
        break;
    }
    
    if (!$useBuiltinFont) {
        imagettftext($image, 26, 0, 60, $lineY2, $lightPurple, $fontText, $line);
        $lineY2 += 42;
    } else {
        imagestring($image, 3, 60, $lineY2 - 80, $line, $lightPurple);
        $lineY2 += 18;
    }
    $counter++;
}

// Footer con call-to-action
$footerY = 980;
if (!$useBuiltinFont) {
    imagettftext($image, 32, 0, 60, $footerY, $gold, $fontTitle, '✨ Consulta tu destino');
    imagettftext($image, 28, 0, 60, $footerY + 50, $white, $fontText, 'OráculoMístico.com');
} else {
    imagestring($image, 4, 60, $footerY - 30, 'Consulta tu destino', $gold);
    imagestring($image, 3, 60, $footerY, 'OraculoMistico.com', $white);
}

// Generar imagen
imagepng($image);
imagedestroy($image);
?>
