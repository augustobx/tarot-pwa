<?php
// CRITICAL: Prevent ANY output before JSON
error_reporting(0); // Suppress all errors/warnings in production
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

// Clean any accidental output
ob_clean();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';
$image = $input['image'] ?? null; // Base64 string if provided

if (!$message && !$image) {
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
$estado = $userData['estado_conversacion'] ?? STATE_BIENVENIDA;

// Handle Conversational Commands - "registrar usuario clave"
if (preg_match('/^registrar\s+(\w+)\s+(.+)$/i', $message, $matches)) {
    $res = registerUser($pdo, $matches[1], $matches[2]);
    if ($res['success']) {
        // Transferir datos de guest a usuario registrado si aplica
        if ($guestId && !$userId) {
            $guestData = getUserData($pdo, null, $guestId);
            if ($guestData) {
                updateUserData($pdo, [
                    'nombre' => $guestData['nombre'],
                    'edad' => $guestData['edad'],
                    'signo_zodiacal' => $guestData['signo_zodiacal'],
                    'preferencia_respuesta' => $guestData['preferencia_respuesta']
                ], $res['user_id'], null);
                updateUserState($pdo, STATE_PREFERENCIAS, $res['user_id'], null);
            }
        }
        
        $_SESSION['user_id'] = $res['user_id'];
        unset($_SESSION['guest_id']);
        
        $userData = getUserData($pdo, $res['user_id'], null);
        
        // Mensaje de registro exitoso + Instrucción para comprar preguntas
        $response = "¡Registro exitoso! Bienvenido {$matches[1]}. ✨\n\n";
        $response .= "Ya está todo listo. Para continuar necesitas preguntas. Presiona en **+Preguntas** y elegí tu mejor opción. 🌙\n\n";
        $response .= getPreferencesQuestion($userData['nombre'] ?? $matches[1]);
        
        echo json_encode(['response' => $response]);
    } else {
        echo json_encode(['response' => "Error: " . $res['message']]);
    }
    exit;
}

// "entrar usuario clave"
if (preg_match('/^entrar\s+(\w+)\s+(.+)$/i', $message, $matches)) {
    $res = loginUser($pdo, $matches[1], $matches[2]);
    if ($res['success']) {
        $_SESSION['user_id'] = $res['user_id'];
        unset($_SESSION['guest_id']);
        
        // Verificar si ya completó el onboarding
        $userData = getUserData($pdo, $res['user_id'], null);
        
        $questions = $res['balance']; // preguntas_restantes
        $greeting = "Has entrado a tu espacio místico, {$userData['nombre']}. ";

        if ($questions < 1) {
             $greeting .= "No tienes preguntas disponibles. 🌙\n\n¿Quieres cargar preguntas?\n\n<button class='chat-action-btn' onclick='window.openPaymentModal()'>🌕 Cargar Preguntas</button>";
        } else {
             $greeting .= "Tienes **$questions preguntas** disponibles. 🌙";
             if ($userData['estado_conversacion'] === STATE_ACTIVO) {
                 $greeting .= "\n\n¿Qué te gustaría explorar hoy?";
             }
        }
        
        echo json_encode(['response' => $greeting]);
    } else {
        echo json_encode(['response' => "Credenciales incorrectas. Intenta de nuevo."]);
    }
    exit;
}

// "salir" o "cerrar sesion"
if (preg_match('/^(salir|cerrar\s*sesi[oó]n|logout)$/i', $message)) {
    session_destroy();
    echo json_encode(['response' => "Has cerrado sesión correctamente. ✨\n\nRefresca la página para volver a entrar."]);
    exit;
}

// PROCESAR ESTADO CONVERSACIONAL
$stateResult = processConversationState($pdo, $message, $userData, $userId, $guestId);

// Si el estado devuelve respuesta directa (no necesita Gemini)
if (!$stateResult['shouldCallGemini']) {
    // Guardar mensaje en historial
    if ($userId) {
        $stmt = $pdo->prepare("INSERT INTO chats (user_id, module, sender, message) VALUES (?, 'tarot', 'user', ?)");
        $stmt->execute([$userId, $message]);
        
        $stmt = $pdo->prepare("INSERT INTO chats (user_id, module, sender, message) VALUES (?, 'tarot', 'ai', ?)");
        $stmt->execute([$userId, $stateResult['response']]);
    } else {
        if (!isset($_SESSION['chat_history'])) $_SESSION['chat_history'] = [];
        $_SESSION['chat_history'][] = ['sender' => 'user', 'message' => $message];
        $_SESSION['chat_history'][] = ['sender' => 'ai', 'message' => $stateResult['response']];
    }
    
    echo json_encode(['response' => $stateResult['response']]);
    exit;
}

// SI LLEGAMOS AQUÍ, EL USUARIO ESTÁ EN ESTADO ACTIVO - Llamar a Gemini

// Obtener datos actualizados después del procesamiento del estado
$userData = getUserData($pdo, $userId, $guestId);

// ===== DETECCIÓN Y GESTIÓN DE TIRADA DE TAROT =====
// Usar IA para detectar intención en lugar de regex frágiles
require_once __DIR__ . '/tarot_intent.php'; // Already in correct location

$tarotIntent = detectTarotIntent($message);

if ($tarotIntent['is_tarot']) {
    require_once __DIR__ . '/tarot_spreads.php'; // Already in correct location
    
    $numCards = $tarotIntent['num_cards'];
    
    // FORCE OVERRIDE: Si dice "una carta" o "1 carta", forzar 1 (por si falla IA)
    if (preg_match('/(una|1)\s+carta/i', $message) && !preg_match('/(tres|3|cinco|5)/i', $message)) {
        $numCards = 1;
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - FORCED numCards to 1 based on regex override.\n", FILE_APPEND);
    }
    
    // Draw cards
    $cards = TarotDeck::drawCards($numCards);
    
    // Define positions based on count
    $positions = [];
    if ($numCards === 1) {
        $positions = ['Consejo del Momento'];
    } elseif ($numCards === 3) {
        $positions = ['Pasado', 'Presente', 'Futuro'];
    } elseif ($numCards === 5) {
        $positions = ['Pasado Lejano', 'Pasado Reciente', 'Presente', 'Futuro Inmediato', 'Futuro Lejano'];
    } else {
        for ($i=0; $i<$numCards; $i++) $positions[] = "Carta " . ($i+1);
    }
    
    foreach ($cards as $index => &$card) {
        $card['position'] = $positions[$index] ?? "Carta " . ($index + 1);
    }
    
    // Get chat history properly
    if ($userId) {
        $stmt = $pdo->prepare("SELECT sender, message FROM chats WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId]);
        $historyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $chatHistory = array_reverse($historyRows); // Oldest first
    } else {
        $chatHistory = $_SESSION['chat_history'] ?? [];
    }
    
    $chatHistory[] = ['sender' => 'user', 'message' => $message];
    
    // Build special prompt with the drawn cards - this modifies userData temporarily
    $tarotUserData = $userData;
    $tarotUserData['tarot_prompt'] = TarotDeck::buildTarotPrompt($cards, $message, $userData);
    
    // Call Gemini with tarot context
    // Log start of call
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Initiating Gemini API call for TAROT...\n", FILE_APPEND);
    
    // Increase execution time
    set_time_limit(120); // 2 minutes
    
    try {
        $fullResponse = callGeminiAPI($chatHistory, null, $tarotUserData);
    } catch (Exception $e) {
        $fullResponse = "Error crítico: " . $e->getMessage();
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Exception in callGeminiAPI: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    // Log result
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Gemini Response received. Length: " . strlen($fullResponse) . "\n", FILE_APPEND);
    
    // Check if response is an error message or empty
    $isError = empty($fullResponse) || 
               strpos($fullResponse, 'Error') !== false || 
               strpos($fullResponse, 'error') !== false ||
               strpos($fullResponse, 'perturbación') !== false || // Mensajes de error personalizados en gemini.php
               strpos($fullResponse, 'astros están nublados') !== false;

    // Save to history ONLY if response is not empty
    if ($userId && !empty(trim($fullResponse))) {
        $stmt = $pdo->prepare("INSERT INTO chats (user_id, module, sender, message) VALUES (?, 'tarot', 'user', ?)");
        $stmt->execute([$userId, $message]);
        
        $stmt = $pdo->prepare("INSERT INTO chats (user_id, module, sender, message) VALUES (?, 'tarot', 'ai', ?)");
        $stmt->execute([$userId, $fullResponse]);
        
        // ONLY Deduct 1 Question if NO ERROR
        if (!$isError) {
            $stmt = $pdo->prepare("UPDATE users SET preguntas_restantes = preguntas_restantes - 1, preguntas_realizadas = preguntas_realizadas + 1 WHERE id = ?");
            $stmt->execute([$userId]);
            file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Question deducted for user $userId\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Question NOT deducted due to error. Response start: " . substr($fullResponse, 0, 50) . "\n", FILE_APPEND);
        }
    } else {
        $_SESSION['chat_history'][] = ['sender' => 'user', 'message' => $message];
        $_SESSION['chat_history'][] = ['sender' => 'ai', 'message' => $fullResponse];
        
        // ONLY Deduct/Count if NO ERROR
        if (!$isError) {
            $stmt = $pdo->prepare("UPDATE guest_sessions SET preguntas_usadas = preguntas_usadas + 1 WHERE guest_id = ?");
            $stmt->execute([$guestId]);
        }
    }
    
    
    // Format cards for frontend (map image to image_path)
    $frontendCards = array_map(function($card) use ($positions) {
        static $i = 0;
        $formattedCard = [
            'name' => $card['name'],
            'reversed' => $card['reversed'],
            'position' => $positions[$i] ?? "Carta " . ($i + 1),
            'image_path' => $card['image'] ?? '' // Map 'image' to 'image_path'
        ];
        $i++;
        return $formattedCard;
    }, $cards);
    
    // Clean output buffer and send ONLY JSON with cards
    while (ob_get_level()) ob_end_clean(); // Clean all buffers

    echo json_encode([
        'response' => $fullResponse,
        'cards' => $frontendCards, // Include cards with image paths
        'remaining_questions' => $userData['preguntas_restantes'] ?? 0,
        'error_status' => $isError
    ]);
    exit;
}

// ===== DETECCIÓN Y GESTIÓN DE NUMEROLOGÍA =====
// Solo activar si pide explícitamente numerología Y no tiene datos aún
$requestsNumerology = preg_match('/(numerolog[íi]a|opci[óo]n\s*3|lectura\s*numerol[óo]gica|calculame?\s+mis\s+n[uú]meros)/i', $message);
$hasNumerologyData = !empty($userData['numerology_data']);

if ($requestsNumerology && !$hasNumerologyData) {
    require_once __DIR__ . '/numerology_helper.php';
    
    // Verificar si tenemos los datos necesarios
    $needsName = empty($userData['nombre']);
    $needsBirthDate = empty($userData['edad']) && empty($userData['birth_date']);
    
    if ($needsName || $needsBirthDate) {
        $response = "Para hacer tu lectura numerológica necesito:\n\n";
        if ($needsName) {
            $response .= "✨ Tu **nombre completo EXACTO** tal como aparece en tu certificado de nacimiento (con todos los nombres y apellidos)\n";
        }
        if ($needsBirthDate) {
            $response .= "🎂 Tu **fecha de nacimiento completa** en formato DD/MM/AAAA\n";
        }
        $response .= "\n¿Puedes compartirlos conmigo? 📜";
        
        echo json_encode(['response' => $response]);
        exit;
    }
    
    // Tenemos los datos, generar lectura numerológica
    $birthDate = $userData['birth_date'] ?? null;
    
    // Si no está birth_date pero tiene edad, intentar construir de edad
    if (!$birthDate && !empty($userData['edad'])) {
        // Parsear edad si viene con formato de fecha
    // Parsear edad si viene con formato de fecha
        if (preg_match('/(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})/', $userData['edad'], $matches)) {
            $birthDate = $matches[1] . '/' . $matches[2] . '/' . $matches[3];
        }
    }
}

// ===== CÁLCULO DE CARTA ASTRAL =====
// Verificar si tenemos datos suficientes para calcular
$birthDate = $userData['birth_date'] ?? null; // DD/MM/AAAA
$birthTime = $userData['birth_time'] ?? '12:00:00';
$birthPlace = $userData['birth_place'] ?? null;
$lat = $userData['latitude'] ?? null;
$lng = $userData['longitude'] ?? null;

// Si tenemos lugar pero no coordenadas, intentar obtenerlas de Gemini (truco rápido)
// En producción real usaríamos API de Geocoding
if ($birthPlace && !$lat) {
    // Si estamos en el paso de CAPTURING_BIRTH_DATA o se acaban de dar los datos
    // podemos pedirle a Gemini que extraiga lat/lng aproximados en el PRIMER turno.
    // Esto es complejo sin una herramienta dedicada.
    // Simplificación: Asumiremos coordinadas neutras (0,0) si no hay,
    // O intentaremos extraerlas del texto si el usuario las puso.
    // MEJOR: Pedirle a Gemini en el prompt que nos de JSON con lat/lng si detecta una ciudad.
    
    // Por ahora, para no bloquear, si no hay lat/lng, el Ascendente será aproximado o null.
}

// FORMATO FECHA:
// Asegurar YYYY-MM-DD para AstrologyHelper
if ($birthDate) {
    $dateObj = DateTime::createFromFormat('d/m/Y', $birthDate);
    if ($dateObj) {
        $formattedDate = $dateObj->format('Y-m-d');
        
        require_once __DIR__ . '/astrology_helper.php';
        
        // Calcular posiciones
        // Si no hay lat/lng, pasamos null, AstrologyHelper saltará Ascendente
        $chart = AstrologyHelper::calculateChart($formattedDate, $birthTime, $lat, $lng);
        
        // Guardar resultado serializado (cache simple) en userData para el prompt
        // No necesitamos guardar en DB los planetas, se calculan al vuelo, 
        // pero podemos cachearlo en una columna si quisiéramos.
        // Lo pasamos a $userData temporalmente
        $userData['astrology_data'] = json_encode($chart);
    }
} else {
    // Intentar extraer fecha del campo edad si es formato fecha
    if (!empty($userData['edad']) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $userData['edad'])) {
         $userData['birth_date'] = $userData['edad'];
         // Recursivo... mejor dejamos que el flujo normal lo maneje
    }
}


// Si ya tiene numerology_data y pide info sobre sus números, agregar recordatorio
if ($hasNumerologyData && preg_match('/(mis\s+n[uú]meros?|numerolog[íi]a|que\s+dicen)/i', $message)) {
    $num = json_decode($userData['numerology_data'], true);
    if ($num) {
        $message = "Recuérdame sobre mi numerología. Mis números son: " .
                  "Vida {$num['life_path']}, " .
                  "Destino {$num['destiny']}, " .
                  "Alma {$num['soul_urge']}, " .
                  "Personalidad {$num['personality']}. " .
                  $message;
    }
}


// Check Questions Limit
if ($userId) {
    // USUARIOS REGISTRADOS: Verificar preguntas disponibles
    $questionsData = getUserQuestions($pdo, $userId);
    $preguntasRestantes = $questionsData['preguntas_restantes'];
    
    if ($preguntasRestantes < 1) {
        echo json_encode(['response' => "Te has quedado sin preguntas. 🌙 Compra más para continuar consultando."]);
        exit;
    }
} else {
    // INVITADOS: Solo 1 pregunta gratis
    $stmt = $pdo->prepare("SELECT preguntas_usadas FROM guest_sessions WHERE guest_id = ?");
    $stmt->execute([$guestId]);
    $preguntasUsadas = $stmt->fetchColumn() ?: 0;
    
    if ($preguntasUsadas >= 1) {
        // Ya usó su pregunta gratuita
        echo json_encode(['response' => "Has usado tu pregunta gratuita como invitado. 🌙\n\nPara continuar consultando, necesitas registrarte. Es muy fácil, solo escribe:\n\n**registrar [usuario] [contraseña]**\n\nPor ejemplo: registrar luna123 mipass123"]);
        exit;
    }
}

// Cargar historial
$history = [];
if ($userId) {
    // Save to DB
    $stmt = $pdo->prepare("INSERT INTO chats (user_id, module, sender, message) VALUES (?, 'tarot', 'user', ?)");
    $stmt->execute([$userId, $message]);
    
    // Load recent context (last 10 messages for better context)
    $stmt = $pdo->prepare("SELECT sender, message FROM chats WHERE user_id = ? ORDER BY id DESC LIMIT 10");
    $stmt->execute([$userId]);
    $history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    if (!isset($_SESSION['chat_history'])) $_SESSION['chat_history'] = [];
    $_SESSION['chat_history'][] = ['sender' => 'user', 'message' => $message];
    $history = array_slice($_SESSION['chat_history'], -10);
}

// Handle Image Upload (save temp file)
$imagePath = null;
if ($image) {
    $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
    $imagePath = __DIR__ . '/../assets/img/temp_' . uniqid() . '.jpg';
    
    // Asegurar que el directorio existe
    $imgDir = dirname($imagePath);
    if (!is_dir($imgDir)) {
        @mkdir($imgDir, 0777, true);
    }
    
    file_put_contents($imagePath, $imgData);
}

// LLAMADA A GEMINI (Chat Normal)
// Log start
file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Main Chat: Initiating Gemini API call...\n", FILE_APPEND);

// Increase execution time
set_time_limit(120);

try {
    $fullResponse = callGeminiAPI($history, $imagePath, $userData);
} catch (Exception $e) {
    $fullResponse = "Error crítico: " . $e->getMessage();
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Main Chat: Exception: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Cleanup Image
if ($imagePath && file_exists($imagePath)) {
    unlink($imagePath);
}

// Warning for guests on first question
$warning = "";
if (!$userId) {
    // Check if this is their first (and only) question
    $stmt = $pdo->prepare("SELECT preguntas_usadas FROM guest_sessions WHERE guest_id = ?");
    $stmt->execute([$guestId]);
    $preguntasUsadas = $stmt->fetchColumn() ?: 0;
    
    if ($preguntasUsadas === 0) {
        $warning = "\n\n✨ **Nota:** Como invitado, tienes 1 pregunta gratuita. Úsala bien. Si quieres seguir consultando después, necesitarás registrarte.";
    }
}

// Append warning to response if exists
$fullResponse .= $warning;

// Check if response is an error message
$isError = empty($fullResponse) || 
           strpos($fullResponse, 'Error') !== false || 
           strpos($fullResponse, 'error') !== false ||
           strpos($fullResponse, 'perturbación') !== false;

if ($userId && !empty(trim($fullResponse))) {
    $stmt = $pdo->prepare("INSERT INTO chats (user_id, module, sender, message) VALUES (?, 'tarot', 'ai', ?)");
    $stmt->execute([$userId, $fullResponse]);
    
    // ONLY Deduct 1 Question if NO ERROR
    if (!$isError) {
        $stmt = $pdo->prepare("UPDATE users SET preguntas_restantes = preguntas_restantes - 1, preguntas_realizadas = preguntas_realizadas + 1 WHERE id = ?");
        $stmt->execute([$userId]);
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Main Chat: Question deducted for user $userId\n", FILE_APPEND);
    } else {
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Main Chat: Question NOT deducted due to error.\n", FILE_APPEND);
    }
} else {
    $_SESSION['chat_history'][] = ['sender' => 'ai', 'message' => $fullResponse];
    
    // Increment guest question counter
    // ONLY Deduct/Count if NO ERROR
    if (!$isError) {
        $stmt = $pdo->prepare("UPDATE guest_sessions SET preguntas_usadas = preguntas_usadas + 1 WHERE guest_id = ?");
        $stmt->execute([$guestId]);
    }
}

// CRITICAL: Clean output buffer and send ONLY JSON
while (ob_get_level()) ob_end_clean();

echo json_encode([
    'response' => $fullResponse,
    'error_status' => $isError
]);
exit;
?>
