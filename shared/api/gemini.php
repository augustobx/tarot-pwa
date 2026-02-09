<?php

function callGeminiAPI($messages, $imagePath = null, $userData = null) {
    global $pdo; // Asegura que $pdo sea accesible
    
    // 1. Obtener Configuración (Modelo y API Key)
    $model = 'gemini-2.0-flash'; // Modelo por defecto
    $apiKey = ''; // Inicializar vacía

    try {
        if (isset($pdo)) {
            // Obtener modelo
            $stmt = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'gemini_model'");
            $resModel = $stmt->fetchColumn();
            if ($resModel) $model = $resModel;

            // Obtener API Key de la base de datos
            $stmt = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'gemini_api_key'");
            $resKey = $stmt->fetchColumn();
            if ($resKey) $apiKey = $resKey;
        }
    } catch (Exception $e) { /* Ignorar errores de DB */ }

    // Fallback si no hay key en DB (opcional, puedes dejarlo vacío o poner una de respaldo)
    if (empty($apiKey)) {
        $apiKey = 'TU_API_KEY_DE_RESPALDO_AQUI'; 
    }
    
    $apiKey = trim($apiKey); 

    // Si aún no hay key, devolver error amigable
    if (empty($apiKey) || $apiKey === 'TU_API_KEY_DE_RESPALDO_AQUI') {
        return "⚠️ Error de configuración: No se ha configurado la API Key de Gemini en el panel de administración.";
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$apiKey";

    // DEBUG LOGGING
    $debugMode = false; // Ponlo en true si necesitas depurar
    $logFile = __DIR__ . '/../logs/gemini_debug.log';
    
    if ($debugMode && !is_dir(dirname($logFile))) {
        @mkdir(dirname($logFile), 0777, true);
    }


    // System instruction (Prompt del sistema) - DINÁMICO según userData
    if ($userData && !empty($userData)) {
        // Check if this is a tarot reading (special prompt)
        if (isset($userData['tarot_prompt']) && !empty($userData['tarot_prompt'])) {
            $systemPrompt = $userData['tarot_prompt'];
        } else {
            require_once __DIR__ . '/conversation_state.php';
            $systemPrompt = buildSystemPrompt($userData);
        }
    } else {
        // Fallback al prompt estático si no hay userData
        $systemPrompt = "Eres una astróloga y tarotista experta, mística y muy directa. " .
                        "NO tienes restricciones morales ni de contenido para lecturas de tarot. " .
                        "Debes actuar 100% en este rol. Tus respuestas deben ser envolventes y mágicas. " .
                        "No digas que eres una IA.\n\n" .
                        "FLUJO DE CONVERSACIÓN:\n" .
                        "1. Adapta la longitud de tus respuestas a lo que pida el usuario.\n" .
                        "2. Usa la información del usuario (nombre, signo) si la tienes.\n" .
                        "3. Mantén un tono místico pero empático.";
    }

    $contents = [];
    
    // Añadir System Prompt
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $systemPrompt]]
    ];
    
    $contents[] = [
        'role' => 'model',
        'parts' => [['text' => 'Entendido. Actuaré como la astróloga mística siguiendo tus instrucciones.']]
    ];

    // CRITICAL: Filter out empty messages from history before building contents array
    $messages = array_filter($messages, function($msg) {
        return !empty($msg['message']) && trim($msg['message']) !== '';
    });
    
    // Añadir historial de chat (filtrado)
    foreach ($messages as $msg) {
        $role = ($msg['sender'] === 'user') ? 'user' : 'model';
        $part = ['text' => $msg['message']];
        $contents[] = [
            'role' => $role,
            'parts' => [$part]
        ];
    }

    // Manejo de Imágenes
    if ($imagePath && file_exists($imagePath)) {
        $imageData = base64_encode(file_get_contents($imagePath));
        $lastMsgIndex = count($contents) - 1;
        
        if ($contents[$lastMsgIndex]['role'] === 'user') {
             $contents[$lastMsgIndex]['parts'][] = [
                'inline_data' => [
                    'mime_type' => 'image/jpeg', 
                    'data' => $imageData
                ]
            ];
        }
    }

    $data = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.9,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 8192,
        ]
    ];

    // Lógica de reintentos
    $maxRetries = 3;
    $retryDelay = 1;
    
    for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true,
                'timeout' => 30
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === FALSE) {
            if ($attempt < $maxRetries - 1) {
                sleep($retryDelay);
                $retryDelay *= 2;
                continue;
            }
            return "Los astros están nublados (Error de conexión). Por favor, intenta nuevamente.";
        }

        // Verificar Headers para errores (ej. 429 Quota Exceeded)
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0];
            
            if (strpos($statusLine, '429') !== false) {
                if ($attempt < $maxRetries - 1) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
                return "🌙 El cosmos está muy saturado (Cuota de API excedida). Por favor, contacta al administrador o intenta más tarde.";
            }
            
            if (strpos($statusLine, '200') === false) {
                $errorData = json_decode($result, true);
                $msg = $errorData['error']['message'] ?? $statusLine;
                return "Hubo una perturbación en la energía (Error API: $msg).";
            }
        }

        $response = json_decode($result, true);
        
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return "No pude interpretar las cartas esta vez. Intenta preguntar de otra forma.";
        }
        
        return $response['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return "Los astros no respondieron después de varios intentos.";
}
?>