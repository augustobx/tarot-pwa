<?php
require_once 'db.php';
require_once 'auth.php';
session_start();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$guestId = session_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$preferencia = $input['preferencia'] ?? 'larga';

// Validar opción
if (!in_array($preferencia, ['corta', 'larga'])) {
    echo json_encode(['error' => 'Preferencia inválida']);
    exit;
}

try {
    if ($userId) {
        $stmt = $pdo->prepare("UPDATE users SET preferencia_respuesta = ? WHERE id = ?");
        $stmt->execute([$preferencia, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE guest_sessions SET preferencia_respuesta = ? WHERE guest_id = ?");
        $stmt->execute([$preferencia, $guestId]);
    }
    
    echo json_encode([
        'success' => true,
        'preferencia' => $preferencia
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al guardar preferencias']);
}
?>
