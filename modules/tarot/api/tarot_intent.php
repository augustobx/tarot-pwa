<?php
/**
 * Detecta si el usuario está solicitando una tirada de tarot usando IA
 * En lugar de regex frágiles, usamos Gemini para interpretar la intención
 */
function detectTarotIntent($message) {
    $apiKey = getenv('GEMINI_API_KEY') ?: 'AIzaSyAHMQPo6FxWWTDyNJNg_L_vBtTiT0_8xkE';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
    
    $prompt = "Eres un clasificador de intenciones. Analiza el siguiente mensaje del usuario y determina:
1. ¿Está pidiendo una lectura/tirada de tarot o que le tires/saques cartas del tarot?
2. Si sí, ¿cuántas cartas quiere EXACTAMENTE?

REGLAS PARA CONTAR CARTAS:
- Si dice \"1 carta\" o \"una carta\" → num_cards: 1
- Si dice \"3 cartas\" o \"tres cartas\" → num_cards: 3
- Si dice \"5 cartas\" o \"cinco cartas\" → num_cards: 5
- Si no especifica número → num_cards: 1 (por defecto UNA carta)

Mensaje del usuario: \"$message\"

Responde SOLO con JSON válido:
{\"is_tarot\": true/false, \"num_cards\": 1}

IMPORTANTE: Si el mensaje dice explícitamente el número, úsalo. Si no dice número, usa 1.";

    $payload = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'maxOutputTokens' => 50
        ]
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($payload),
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result === FALSE) {
        // Si falla IA, fallback a regex simple
        $isTarot = preg_match('/(tarot|carta|tirada|tir[aáeé])/i', $message);
        return [
            'is_tarot' => $isTarot,
            'num_cards' => preg_match('/una\s+carta/i', $message) ? 1 : 3
        ];
    }

    $response = json_decode($result, true);
    $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '{"is_tarot": false, "num_cards": 1}';
    
    // Limpiar markdown code blocks si los incluye
    $text = preg_replace('/```json\s*|\s*```/', '', $text);
    $text = trim($text);
    
    $intent = json_decode($text, true);
    
    // LOG Intent for debugging
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - AI Intent Result: " . print_r($intent, true) . "\n", FILE_APPEND);
    
    // Validar y retornar
    return [
        'is_tarot' => $intent['is_tarot'] ?? false,
        'num_cards' => isset($intent['num_cards']) ? max(1, min(5, (int)$intent['num_cards'])) : 3
    ];
}
