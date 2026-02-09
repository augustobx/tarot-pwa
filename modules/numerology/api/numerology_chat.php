<?php
// CRITICAL: Prevent ANY output before JSON
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();

// Clean any previous output
while (ob_get_level() > 1) ob_end_clean();

session_start();
require_once __DIR__ . '/../../../shared/api/db.php';
require_once __DIR__ . '/../../../shared/api/auth.php';
require_once __DIR__ . '/../../../shared/api/gemini.php';
require_once __DIR__ . '/../../../shared/api/conversation_state.php';
require_once __DIR__ . '/numerology_helper.php';

// Clean any accidental output
ob_clean();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (!$message) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// User Identification
$userId = $_SESSION['user_id'] ?? null;
$guestId = $_SESSION['guest_id'] ?? null;

if (!$userId && !$guestId) {
    $guestId = uniqid('guest_');
    $_SESSION['guest_id'] = $guestId;
}

// Obtener datos del usuario/guest
$userData = getUserData($pdo, $userId, $guestId);

// Definir estados específicos de Numerología
if (!defined('STATE_NUMERO_PEDIR_NOMBRE')) define('STATE_NUMERO_PEDIR_NOMBRE', 'NUMERO_PEDIR_NOMBRE');
if (!defined('STATE_NUMERO_PEDIR_FECHA')) define('STATE_NUMERO_PEDIR_FECHA', 'NUMERO_PEDIR_FECHA');

// Detectar inicio
if (stripos($message, '/start') !== false || stripos($message, 'hola') !== false) {
    $greeting = "Te doy la bienvenida al reino de los números, " . ($userData['nombre'] ?? 'viajero') . ". 🔢\n\n";
    $greeting .= "Aquí descubriremos tu Número de Vida, Destino y Alma.\n";
    
    // Verificar si tenemos datos
    $hasBirthData = !empty($userData['birth_date']);
    // Usamos el nombre del usuario si está registrado, si no, lo pedimos completo para numerología
    // En numerología se necesita el nombre COMPLETO de nacimiento
    
    $greeting .= "\nPara un análisis preciso, necesito tu **nombre completo** (tal como aparece en tu partida de nacimiento).";
    updateUserState($pdo, STATE_NUMERO_PEDIR_NOMBRE, $userId, $guestId);
    
    saveMessage($pdo, $userId ?? $guestId, 'system', $greeting, 'numerology');
    echo json_encode(['response' => $greeting]);
    exit;
}

// Manejo de Estados
$currentState = $userData['estado_conversacion'];

switch ($currentState) {
    case STATE_NUMERO_PEDIR_NOMBRE:
        if (strlen($message) > 5) {
            // Guardamos el nombre completo en un campo específico o metadato si es posible, 
            // pero por ahora usaremos una columna temporal o lo calculamos al vuelo y guardamos el resultado
            // Vamos a guardar el nombre completo en 'numerology_name' dentro de metadatos (si existiera)
            // O simplemente lo pasamos al siguiente paso en la sesión/estado no persistente complejo
            // Simplificación: Guardamos en 'nombre_completo' en user_data (custom field simulation via updateUserData if schema allowed, but let's assume we store it in a temp variable or update the main name if it's a guest, but better not overwrite 'nombre' which might be a nickname).
            // Lo guardaremos en 'numerology_data' JSON
            
            $numerologyData = ['full_name' => $message];
            updateUserData($pdo, ['numerology_data' => json_encode($numerologyData)], $userId, $guestId);
            
            // Siguiente paso: Fecha
            if (!empty($userData['birth_date'])) {
                // Ya tenemos fecha, calculamos directo
                $finalData = $numerologyData;
                $finalData['birth_date'] = $userData['birth_date'];
                
                // Calcular
                $reading = generateNumerologyReading($finalData['full_name'], $finalData['birth_date']);
                updateUserData($pdo, ['numerology_data' => json_encode($reading)], $userId, $guestId);
                updateUserState($pdo, STATE_ACTIVO, $userId, $guestId);
                
                $response = "¡Excelente! Con tu nombre y fecha de nacimiento he calculado tus números maestros. 🔢\n\n";
                $response .= "Tu **Número de Vida** es **{$reading['life_path']}**.\n";
                $response .= "Tu **Número de Destino** es **{$reading['destiny']}**.\n\n";
                $response .= "¿Qué aspecto de tu vida te gustaría analizar con estos números?";

            } else {
                updateUserState($pdo, STATE_NUMERO_PEDIR_FECHA, $userId, $guestId);
                $response = "Gracias. Ahora, por favor confirma tu **fecha de nacimiento** (DD/MM/AAAA).";
            }
        } else {
            $response = "Por favor, ingresa tu nombre completo real para el cálculo.";
        }
        break;

    case STATE_NUMERO_PEDIR_FECHA:
        if (preg_match('/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/', $message, $matches)) {
            $date = sprintf("%04d-%02d-%02d", $matches[3], $matches[2], $matches[1]);
            updateUserData($pdo, ['birth_date' => $date], $userId, $guestId);
            
            // Recuperar nombre
            $currentData = json_decode($userData['numerology_data'] ?? '{}', true);
            $fullName = $currentData['full_name'] ?? $userData['nombre']; // Fallback
            
            // Calcular
            $reading = generateNumerologyReading($fullName, $date);
            updateUserData($pdo, ['numerology_data' => json_encode($reading)], $userId, $guestId);
            updateUserState($pdo, STATE_ACTIVO, $userId, $guestId);
            
            $response = "¡Cálculos completados! ✨\n\n";
            $response .= "Tu **Número de Vida** es **{$reading['life_path']}**.\n";
            $response .= "Tu **Número de Destino** es **{$reading['destiny']}**.\n\n";
            $response .= "¿Qué deseas descubrir sobre tu vibración numérica hoy?";
        } else {
            $response = "Por favor, ingresa la fecha en formato DD/MM/AAAA (ej: 25/08/1990).";
        }
        break;

    default:
        // Estado Activo
        saveMessage($pdo, $userId ?? $guestId, 'user', $message, 'numerology');
        
        $systemPrompt = buildSystemPrompt($userData);
        $systemPrompt .= "\n\nERES UN NUMERÓLOGO EXPERTO PITAGÓRICO. Tu enfoque se basa en la vibración de los números.";
        
        // Incluir datos numerológicos
        if (!empty($userData['numerology_data'])) {
            $reading = json_decode($userData['numerology_data'], true);
            $systemPrompt .= "\n\nANÁLISIS NUMEROLÓGICO DEL USUARIO:\n" . json_encode($reading, JSON_PRETTY_PRINT);
        }
        
        $history = getChatHistory($pdo, $userId, $guestId, 'numerology', 5);
        
        $aiResponse = callGeminiAPI($message, $history, $systemPrompt);
        
        saveMessage($pdo, $userId ?? $guestId, 'ai', $aiResponse, 'numerology');
        
        echo json_encode(['response' => $aiResponse]);
        exit;
}

saveMessage($pdo, $userId ?? $guestId, 'user', $message, 'numerology');
saveMessage($pdo, $userId ?? $guestId, 'system', $response, 'numerology');
echo json_encode(['response' => $response]);

?>
