<?php
require_once 'db.php';

function registerUser($pdo, $username, $password) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'El usuario ya existe.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    if ($stmt->execute([$username, $hash])) {
        return ['success' => true, 'user_id' => $pdo->lastInsertId()];
    }
    return ['success' => false, 'message' => 'Error al registrar.'];
}

function loginUser($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT id, password_hash, preguntas_restantes FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        return ['success' => true, 'user_id' => $user['id'], 'balance' => $user['preguntas_restantes']];
    }
    return ['success' => false, 'message' => 'Credenciales inválidas.'];
}

function getUserBalance($pdo, $userId) {
    // Deprecated: Use getUserQuestions instead
    $stmt = $pdo->prepare("SELECT preguntas_restantes FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: 0;
}

function getUserQuestions($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT preguntas_restantes, preguntas_realizadas FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['preguntas_restantes' => 0, 'preguntas_realizadas' => 0];
}
?>
