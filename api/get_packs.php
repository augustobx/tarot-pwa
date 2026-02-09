<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    // Fetch pack prices from config
    $packs = [
        [
            'cantidad' => 1,
            'precio' => floatval($pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_1_preguntas'")->fetchColumn() ?: 100),
            'emoji' => '1️⃣'
        ],
        [
            'cantidad' => 5,
            'precio' => floatval($pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_5_preguntas'")->fetchColumn() ?: 450),
            'emoji' => '2️⃣'
        ],
        [
            'cantidad' => 10,
            'precio' => floatval($pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_10_preguntas'")->fetchColumn() ?: 800),
            'emoji' => '3️⃣',
            'tag' => true
        ],
        [
            'cantidad' => 25,
            'precio' => floatval($pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_25_preguntas'")->fetchColumn() ?: 1750),
            'emoji' => '4️⃣'
        ],
        [
            'cantidad' => 50,
            'precio' => floatval($pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_50_preguntas'")->fetchColumn() ?: 3000),
            'emoji' => '5️⃣'
        ]
    ];
    
    // Calculate discounts based on unit price
    $basePricePerQuestion = $packs[0]['precio']; // Price of 1 question
    
    foreach ($packs as &$pack) {
        $unitPrice = $pack['precio'] / $pack['cantidad'];
        $pack['discount'] = $pack['cantidad'] > 1 ? round((1 - ($unitPrice / $basePricePerQuestion)) * 100) : 0;
    }
    
    echo json_encode(['packs' => $packs]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar precios']);
}
?>
