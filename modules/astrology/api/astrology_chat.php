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
require_once __DIR__ . '/astrology_helper.php';

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

// Definir estados específicos de Astrología
if (!defined('STATE_ASTRO_PEDIR_FECHA')) define('STATE_ASTRO_PEDIR_FECHA', 'ASTRO_PEDIR_FECHA');
if (!defined('STATE_ASTRO_PEDIR_HORA')) define('STATE_ASTRO_PEDIR_HORA', 'ASTRO_PEDIR_HORA'); // Optional
if (!defined('STATE_ASTRO_PEDIR_LUGAR')) define('STATE_ASTRO_PEDIR_LUGAR', 'ASTRO_PEDIR_LUGAR'); // Optional
if (!defined('STATE_ASTRO_CONFIRMACION')) define('STATE_ASTRO_CONFIRMACION', 'ASTRO_CONFIRMACION');

// Verificar si estamos en un flujo de onboarding específico de astrología
// Si el usuario ya tiene datos básicos en shared state, podemos usarlos
// Pero para Carta Astral necesitamos Fecha, Hora y Lugar precisos

// Detectar si es el primer mensaje en este módulo (o reiniciar)
if (stripos($message, '/start') !== false || stripos($message, 'hola') !== false) {
    // Si ya tenemos datos, saludar y preguntar qué quiere
    $greeting = "Bienvenido al observatorio de las estrellas, " . ($userData['nombre'] ?? 'buscador') . ". ✨\n\n";
    $greeting .= "Aquí podemos trazar tu Carta Astral, analizar tránsitos o consultar la compatibilidad de sinastría.\n";
    
    // Verificar si tenemos datos de nacimiento
    $hasBirthData = !empty($userData['birth_date']) && !empty($userData['birth_time']) && !empty($userData['birth_place']);
    
    if (!$hasBirthData) {
        $greeting .= "\nPara comenzar con precisión, necesito saber tu **fecha de nacimiento** (DD/MM/AAAA).";
        updateUserState($pdo, STATE_ASTRO_PEDIR_FECHA, $userId, $guestId);
    } else {
        $greeting .= "\nYa tengo tus datos astrales. ¿Qué te gustaría consultar hoy?";
        updateUserState($pdo, STATE_ACTIVO, $userId, $guestId);
    }
    
    saveMessage($pdo, $userId ?? $guestId, 'system', $greeting, 'astrology');
    echo json_encode(['response' => $greeting]);
    exit;
}

// Manejo de Estados
$currentState = $userData['estado_conversacion'];

switch ($currentState) {
    case STATE_ASTRO_PEDIR_FECHA:
        // Validar fecha
        if (preg_match('/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/', $message, $matches)) {
            $date = sprintf("%04d-%02d-%02d", $matches[3], $matches[2], $matches[1]);
            updateUserData($pdo, ['birth_date' => $date], $userId, $guestId);
            updateUserState($pdo, STATE_ASTRO_PEDIR_HORA, $userId, $guestId);
            
            $response = "Perfecto. Ahora, para calcular tu Ascendente y Casas, necesito tu **hora de nacimiento** lo más exacta posible (ej: 14:30). Si no la sabes, dime 'no sé' (usaremos mediodía solar).";
        } else {
            $response = "Por favor, ingresa la fecha en formato DD/MM/AAAA (ej: 25/08/1990).";
        }
        break;

    case STATE_ASTRO_PEDIR_HORA:
        $time = '12:00:00'; // Default
        if (preg_match('/no s[eé]/i', $message)) {
            // Usuario no sabe
            updateUserData($pdo, ['birth_time' => $time], $userId, $guestId);
            updateUserState($pdo, STATE_ASTRO_PEDIR_LUGAR, $userId, $guestId);
            $response = "Entendido, usaremos una hora aproximada. Por último, ¿en qué **ciudad y país** naciste?";
        } elseif (preg_match('/(\d{1,2})[:\.](\d{2})/', $message, $matches)) {
            $time = sprintf("%02d:%02d:00", $matches[1], $matches[2]);
            updateUserData($pdo, ['birth_time' => $time], $userId, $guestId);
            updateUserState($pdo, STATE_ASTRO_PEDIR_LUGAR, $userId, $guestId);
            $response = "Gracias. Y finalmente, ¿en qué **ciudad y país** naciste? (Esto define tus coordenadas)";
        } else {
             $response = "Por favor, intenta usar el formato HH:MM (ej: 09:45) o dime 'no sé'.";
        }
        break;

    case STATE_ASTRO_PEDIR_LUGAR:
        if (strlen($message) > 2) {
            updateUserData($pdo, ['birth_place' => $message], $userId, $guestId);
            updateUserState($pdo, STATE_ACTIVO, $userId, $guestId);
            
            // Recargar datos para tener todo
            $userData = getUserData($pdo, $userId, $guestId);
            
            // Calcular Carta Astral Inicial
            // (Aquí usaríamos una API de geocoding real, por ahora mocked lat/lng o 0,0)
            // Asumiremos 0,0 para calcular al menos posiciones planetarias (excepto ascendente)
            // O mejor: Dejar que Gemini alucine la latitud/longitud basado en el nombre de la ciudad O pedirle a Gemini que actúe como geocoder en el siguiente paso.
            // Para simplicidad del helper, pasaremos null lat/lng y el helper saltará el ascendente si no los tiene, o usará 0.
            
            $chart = AstrologyHelper::calculateChart($userData['birth_date'], $userData['birth_time'], null, null);
            $chartJson = json_encode($chart);
            updateUserData($pdo, ['astrology_data' => $chartJson], $userId, $guestId);
            
            $response = "¡Excelente! He alineado los astros para ti. 🌌\n\n";
            $response .= "Tu Sol está en **{$chart['Sol']['sign']}**, tu Luna en **{$chart['Luna']['sign']}**.\n\n";
            $response .= "¿Qué te gustaría saber sobre tu carta o tu destino astral?";
            
            // Guardar contexto en DB
        } else {
            $response = "Por favor, especifica la ciudad y país.";
        }
        break;

    default:
        // Estado Activo o Conversación General
        // Enviar a Gemini con contexto astrológico
        
        // Save user message first
        saveMessage($pdo, $userId ?? $guestId, 'user', $message, 'astrology');
        
        // Preparar System Prompt Especializado
        $systemPrompt = buildSystemPrompt($userData);
        $systemPrompt .= "\n\nERES UNA ASTRÓLOGA EXPERTA. Tu enfoque es 100% astrológico.";
        $systemPrompt .= "\nUsa los tránsitos planetarios, casas y aspectos para explicar la situación del usuario.";
        $systemPrompt .= "\nSi te preguntan por compatibilidad, pide los datos de la otra persona.";
        
        // Incluir datos de la carta si existen
        if (!empty($userData['astrology_data'])) {
            $chart = json_decode($userData['astrology_data'], true);
            $systemPrompt .= "\n\nDATOS DE LA CARTA ASTRAL DEL USUARIO:\n" . json_encode($chart, JSON_PRETTY_PRINT);
        }
        
        // Llamar a Gemini
        // Historial de chat (últimos 5 mensajes de este módulo)
        $history = getChatHistory($pdo, $userId, $guestId, 'astrology', 5);
        
        $aiResponse = callGeminiAPI($message, $history, $systemPrompt);
        
        // Guardar respuesta AI
        saveMessage($pdo, $userId ?? $guestId, 'ai', $aiResponse, 'astrology');
        
        echo json_encode(['response' => $aiResponse]);
        exit;
}

// Para respuestas de cambios de estado (no Gemini)
saveMessage($pdo, $userId ?? $guestId, 'user', $message, 'astrology');
saveMessage($pdo, $userId ?? $guestId, 'system', $response, 'astrology');
echo json_encode(['response' => $response]);

?>
