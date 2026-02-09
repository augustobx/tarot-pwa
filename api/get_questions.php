<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$guestId = $_SESSION['guest_id'] ?? null; // Assuming guest_id is also stored in session

if ($userId) {
    $questionsData = getUserQuestions($pdo, $userId);
    
    // Get preference for logged-in user
    $stmt = $pdo->prepare("SELECT preferencia_respuesta FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $preferencia = $stmt->fetchColumn() ?: 'larga';
    
    echo json_encode([
        'preguntas_restantes' => $questionsData['preguntas_restantes'],
        'preguntas_realizadas' => $questionsData['preguntas_realizadas'],
        'preferencia_respuesta' => $preferencia,
        'is_guest' => false
    ]);
} else {
    // Guest
    // For guests, we need to retrieve their preference, assuming it's stored in guest_sessions
    // If guest_id is not set, default to 'larga' and don't query the DB
    $preferencia = 'larga'; // Default preference for guests without a guest_id or session
    if ($guestId) {
        $stmt = $pdo->prepare("SELECT preferencia_respuesta FROM guest_sessions WHERE guest_id = ?");
        $stmt->execute([$guestId]);
        $guest_preferencia = $stmt->fetchColumn();
        if ($guest_preferencia !== false) {
            $preferencia = $guest_preferencia;
        }
    }
    
    echo json_encode([
        'preguntas_restantes' => null,
        'preguntas_realizadas' => null, // Changed from 0 to null for guests as per the provided edit
        'preferencia_respuesta' => $preferencia,
        'is_guest' => true
    ]);
}
?>
