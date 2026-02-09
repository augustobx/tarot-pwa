<?php
// api/payment.php
require_once 'db.php';
require_once 'auth.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pack = $input['pack'] ?? 10;
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        echo json_encode(['error' => 'Debes iniciar sesión para comprar preguntas']);
        exit;
    }

    // Get pack price from config
    $stmt = $pdo->prepare("SELECT setting_value FROM config WHERE setting_key = ?");
    $stmt->execute(["pack_{$pack}_preguntas"]);
    $precio = $stmt->fetchColumn();

    if (!$precio) {
        echo json_encode(['error' => 'Pack no válido']);
        exit;
    }

    // Get MercadoPago credentials
    $stmt = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'mp_access_token'");
    $accessToken = $stmt->fetchColumn();

    if (!$accessToken) {
        echo json_encode(['error' => 'MercadoPago no configurado. Contacta al administrador.']);
        exit;
    }

    // Get user email
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $username = $stmt->fetchColumn();

    // Detectar la URL base del servidor (en lugar de hardcodear localhost)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host . '/tarot-pwa';

    // Create MercadoPago preference
    $externalReference = $userId . '_' . $pack . '_' . time();
    
    $preference = [
        'items' => [
            [
                'title' => "Pack de $pack preguntas - Oráculo Místico",
                'quantity' => 1,
                'unit_price' => floatval($precio),
                'currency_id' => 'ARS'
            ]
        ],
        'payer' => [
            'email' => $username . '@tarot-pwa.local' // Fallback email
        ],
        'back_urls' => [
            'success' => $baseUrl . '/payment_success.php',
            'failure' => $baseUrl . '/payment_failure.php',
            'pending' => $baseUrl . '/payment_pending.php'
        ],
        'auto_return' => 'approved',
        'external_reference' => $externalReference,
        // Solo incluir notification_url si NO es localhost
        // MercadoPago no puede acceder a localhost
    ];
    
    // Solo agregar webhook si no es localhost
    if (!in_array($host, ['localhost', '127.0.0.1', 'localhost:80', 'localhost:8080'])) {
        $preference['notification_url'] = $baseUrl . '/api/mp_webhook.php';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/checkout/preferences');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer $accessToken"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 201) {
        $data = json_decode($response, true);
        
        // Save transaction as pending
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, precio, cantidad_preguntas, status, mp_reference) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->execute([$userId, $precio, $pack, $data['id']]);
        
        echo json_encode([
            'init_point' => $data['init_point'],
            'preference_id' => $data['id']
        ]);
    } else {
        // Decodificar respuesta de error de MercadoPago
        $errorData = json_decode($response, true);
        $errorMessage = 'Error desconocido';
        
        if (isset($errorData['message'])) {
            $errorMessage = $errorData['message'];
        } elseif (isset($errorData['error'])) {
            $errorMessage = $errorData['error'];
        }
        
        // Log completo para debugging
        $logMessage = "MercadoPago Error:\n";
        $logMessage .= "HTTP Code: $httpCode\n";
        $logMessage .= "cURL Error: $curlError\n";
        $logMessage .= "Response: $response\n";
        $logMessage .= "Preference Data: " . json_encode($preference) . "\n";
        $logMessage .= "---\n";
        error_log($logMessage);
        
        echo json_encode([
            'error' => 'Error al crear preferencia de pago.',
            'details' => $errorMessage,
            'http_code' => $httpCode,
            'hint' => $httpCode === 401 ? 'Verifica que tu Access Token sea válido y esté activo en MercadoPago.' : 
                     ($httpCode === 400 ? 'Los datos enviados no son válidos. Verifica los precios en el panel.' : 
                     'Verifica la configuración de MercadoPago en el panel de administración.')
        ]);
    }
    exit;
}
?>
