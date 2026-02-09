<?php
require_once 'db.php';

// MercadoPago Webhook/IPN Handler
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log all webhooks for debugging
file_put_contents(__DIR__ . '/../mp_webhook.log', date('Y-m-d H:i:s') . " - " . $input . "\n", FILE_APPEND);

if ($data && isset($data['type']) && $data['type'] === 'payment') {
    $paymentId = $data['data']['id'];
    
    // Get access token
    $stmt = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'mp_access_token'");
    $accessToken = $stmt->fetchColumn();
    
    if (!$accessToken) {
        http_response_code(200);
        exit;
    }
    
    // Get payment details from MercadoPago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/$paymentId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $payment = json_decode($response, true);
    
    if ($payment && $payment['status'] === 'approved') {
        // Extract user_id and pack from external_reference (format: userId_pack_timestamp)
        $ref = explode('_', $payment['external_reference']);
        $userId = $ref[0];
        $pack = $ref[1];
        
        // Check if already processed
        $stmt = $pdo->prepare("SELECT status FROM transactions WHERE mp_reference = ?");
        $stmt->execute([$payment['preference_id']]);
        $currentStatus = $stmt->fetchColumn();
        
        if ($currentStatus !== 'approved') {
            // Add questions to user
            $stmt = $pdo->prepare("UPDATE users SET preguntas_restantes = preguntas_restantes + ? WHERE id = ?");
            $stmt->execute([$pack, $userId]);
            
            // Update transaction status
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'approved' WHERE mp_reference = ?");
            $stmt->execute([$payment['preference_id']]);
            
            // Log success
            file_put_contents(__DIR__ . '/../mp_webhook.log', 
                date('Y-m-d H:i:s') . " - APPROVED: Added $pack questions to user $userId\n", 
                FILE_APPEND
            );
        }
    }
}

http_response_code(200);
?>
