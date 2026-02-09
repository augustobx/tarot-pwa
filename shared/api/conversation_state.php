<?php
/**
 * Conversation State Management
 * Maneja los estados conversacionales para usuarios y invitados
 */

// Estados del flujo conversacional
if (!defined('STATE_BIENVENIDA')) define('STATE_BIENVENIDA', 'BIENVENIDA');
if (!defined('STATE_EDAD_SIGNO')) define('STATE_EDAD_SIGNO', 'EDAD_SIGNO');
if (!defined('STATE_OFERTA_REGISTRO')) define('STATE_OFERTA_REGISTRO', 'OFERTA_REGISTRO');
if (!defined('STATE_PROCESO_REGISTRO')) define('STATE_PROCESO_REGISTRO', 'PROCESO_REGISTRO');
if (!defined('STATE_PREFERENCIAS')) define('STATE_PREFERENCIAS', 'PREFERENCIAS');
if (!defined('STATE_TIPO_CONSULTA')) define('STATE_TIPO_CONSULTA', 'TIPO_CONSULTA');
if (!defined('STATE_ACTIVO')) define('STATE_ACTIVO', 'ACTIVO');

/**
 * Obtener datos completos del usuario/invitado
 */
function getUserData($pdo, $userId = null, $guestId = null) {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($guestId) {
        $stmt = $pdo->prepare("SELECT * FROM guest_sessions WHERE guest_id = ?");
        $stmt->execute([$guestId]);
        $guest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no existe, crear nuevo guest
        if (!$guest) {
            $stmt = $pdo->prepare("INSERT INTO guest_sessions (guest_id, estado_conversacion, preguntas_usadas) VALUES (?, ?, 0)");
            $stmt->execute([$guestId, STATE_BIENVENIDA]);
            return [
                'guest_id' => $guestId,
                'nombre' => null,
                'edad' => null,
                'signo_zodiacal' => null,
                'preferencia_respuesta' => 'larga',
                'estado_conversacion' => STATE_BIENVENIDA,
                'chat_history' => null,
                'preguntas_usadas' => 0
            ];
        }
        
        return $guest;
    }
    
    return null;
}

/**
 * Actualizar estado conversacional
 */
function updateUserState($pdo, $newState, $userId = null, $guestId = null) {
    if ($userId) {
        $stmt = $pdo->prepare("UPDATE users SET estado_conversacion = ? WHERE id = ?");
        return $stmt->execute([$newState, $userId]);
    } elseif ($guestId) {
        $stmt = $pdo->prepare("UPDATE guest_sessions SET estado_conversacion = ? WHERE guest_id = ?");
        return $stmt->execute([$newState, $guestId]);
    }
    return false;
}

/**
 * Actualizar datos del usuario/invitado
 */
function updateUserData($pdo, $data, $userId = null, $guestId = null) {
    if ($userId) {
        $fields = [];
        $values = [];
        
        if (isset($data['nombre'])) {
            $fields[] = "nombre = ?";
            $values[] = $data['nombre'];
        }
        if (isset($data['edad'])) {
            $fields[] = "edad = ?";
            $values[] = $data['edad'];
        }
        if (isset($data['signo_zodiacal'])) {
            $fields[] = "signo_zodiacal = ?";
            $values[] = $data['signo_zodiacal'];
        }
        if (isset($data['preferencia_respuesta'])) {
            $fields[] = "preferencia_respuesta = ?";
            $values[] = $data['preferencia_respuesta'];
        }
        
        if (empty($fields)) return false;
        
        $values[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
        
    } elseif ($guestId) {
        $fields = [];
        $values = [];
        
        if (isset($data['nombre'])) {
            $fields[] = "nombre = ?";
            $values[] = $data['nombre'];
        }
        if (isset($data['edad'])) {
            $fields[] = "edad = ?";
            $values[] = $data['edad'];
        }
        if (isset($data['signo_zodiacal'])) {
            $fields[] = "signo_zodiacal = ?";
            $values[] = $data['signo_zodiacal'];
        }
        if (isset($data['preferencia_respuesta'])) {
            $fields[] = "preferencia_respuesta = ?";
            $values[] = $data['preferencia_respuesta'];
        }
        
        if (empty($fields)) return false;
        
        $values[] = $guestId;
        $sql = "UPDATE guest_sessions SET " . implode(', ', $fields) . " WHERE guest_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }
    
    return false;
}

/**
 * Procesar mensaje según estado actual
 * Retorna: ['nextState' => string, 'response' => string, 'shouldCallGemini' => bool]
 */
function processConversationState($pdo, $message, $userData, $userId = null, $guestId = null) {
    $estado = $userData['estado_conversacion'] ?? STATE_BIENVENIDA;
    $message = trim($message);
    
    switch ($estado) {
        case STATE_BIENVENIDA:
            // Guardar nombre
            $nombre = extractName($message);
            updateUserData($pdo, ['nombre' => $nombre], $userId, $guestId);
            updateUserState($pdo, STATE_EDAD_SIGNO, $userId, $guestId);
            
            return [
                'nextState' => STATE_EDAD_SIGNO,
                'response' => "Encantada de conocerte, $nombre. ✨ ¿Me compartirías tu edad y signo zodiacal? Esto me ayudará a conectar mejor con tu energía. Si prefieres no compartirlo, solo escribe 'prefiero no decirlo'.",
                'shouldCallGemini' => false
            ];
            
        case STATE_EDAD_SIGNO:
            // Intentar extraer edad y signo
            if (stripos($message, 'no') !== false || stripos($message, 'prefiero') !== false) {
                // Usuario rechaza compartir
                updateUserState($pdo, STATE_OFERTA_REGISTRO, $userId, $guestId);
                return [
                    'nextState' => STATE_OFERTA_REGISTRO,
                    'response' => "Está bien, respeto tu privacidad. " . getRegistrationOffer($userData['nombre']),
                    'shouldCallGemini' => false
                ];
            } else {
                // Intentar extraer datos
                $extracted = extractAgeAndSign($message);
                updateUserData($pdo, $extracted, $userId, $guestId);
                updateUserState($pdo, STATE_OFERTA_REGISTRO, $userId, $guestId);
                
                $signoMsg = $extracted['signo_zodiacal'] ? " La energía de {$extracted['signo_zodiacal']} es especial." : "";
                return [
                    'nextState' => STATE_OFERTA_REGISTRO,
                    'response' => "Perfecto.$signoMsg " . getRegistrationOffer($userData['nombre']),
                    'shouldCallGemini' => false
                ];
            }
            
        case STATE_OFERTA_REGISTRO:
            // Verificar si acepta registro
            if (preg_match('/s[ií]|quiero|acepto|ok|dale/i', $message)) {
                updateUserState($pdo, STATE_PROCESO_REGISTRO, $userId, $guestId);
                return [
                    'nextState' => STATE_PROCESO_REGISTRO,
                    'response' => "Excelente. Para registrarte, escribe:\n**registrar [usuario] [contraseña]**\n\nPor ejemplo: registrar luna123 mipass123",
                    'shouldCallGemini' => false
                ];
            } else {
                // Rechaza registro, continuar como invitado
                updateUserState($pdo, STATE_PREFERENCIAS, $userId, $guestId);
                return [
                    'nextState' => STATE_PREFERENCIAS,
                    'response' => getPreferencesQuestion($userData['nombre']),
                    'shouldCallGemini' => false
                ];
            }
            
        case STATE_PROCESO_REGISTRO:
            // Esperar comando "registrar usuario password"
            // Este estado se maneja en chat.php con el regex existente
            // Si llega aquí es porque no matcheó el formato
            return [
                'nextState' => STATE_PROCESO_REGISTRO,
                'response' => "Por favor usa el formato:\n**registrar [usuario] [contraseña]**\n\nPor ejemplo: registrar luna123 mipass123",
                'shouldCallGemini' => false
            ];
            
        case STATE_PREFERENCIAS:
            // Determinar preferencia
            $preferencia = 'larga';
            if (preg_match('/corta|breve|directa|tajante|concisa/i', $message)) {
                $preferencia = 'corta';
            }
            updateUserData($pdo, ['preferencia_respuesta' => $preferencia], $userId, $guestId);
            updateUserState($pdo, STATE_TIPO_CONSULTA, $userId, $guestId);
            
            $pref = $preferencia === 'corta' ? 'breve y directa' : 'detallada y mística';
            return [
                'nextState' => STATE_TIPO_CONSULTA,
                'response' => "Perfecto, seré $pref. 🌙\n\nAhora, ¿qué te gustaría explorar hoy?\n\n1️⃣ **Hacer una pregunta específica** - Consulta sobre amor, trabajo, vida\n2️⃣ **Lectura de Tarot completa** - Tu carta astral y energía actual\n3️⃣ **Lectura Numerológica** - Descubre tus números de vida, destino y alma\n\n¿Qué opción prefieres? (puedes escribir 1, 2, 3 o describir lo que necesitas)",
                'shouldCallGemini' => false
            ];
            
        case STATE_TIPO_CONSULTA:
            // Ya terminó el onboarding, pasar a modo activo
            updateUserState($pdo, STATE_ACTIVO, $userId, $guestId);
            return [
                'nextState' => STATE_ACTIVO,
                'response' => null, // Dejar que Gemini responda
                'shouldCallGemini' => true
            ];
            
        case STATE_ACTIVO:
            // Conversación normal, pero verificar si pide Carta Astral y faltan datos
            if (preg_match('/(carta\s*astral|mapa\s*astral|mi\s*carta|posiciones\s*planetarias)/i', $message)) {
                
                // Verificar si tenemos hora y lugar
                $needsTime = empty($userData['birth_time']);
                $needsPlace = empty($userData['birth_place']);
                
                if ($needsTime || $needsPlace) {
                    $response = "Para trazar tu mapa celeste con precisión absoluta, el universo me pide más detalles: 🌌\n\n";
                    if ($needsTime) $response .= "- Tu **hora exacta de nacimiento** (ej: 14:30)\n";
                    if ($needsPlace) $response .= "- Tu **lugar de nacimiento** (Ciudad, País)\n";
                    
                    $response .= "\nSin estos datos, los planetas se ven borrosos. ¿Podrías compartirlos?";
                    
                    // Estado temporal para capturar estos datos? 
                    // Podríamos usar un estado dedicado o manejarlo en el mismo ACTIVO con flags
                    // Para simplificar, usamos un estado dedicado
                    updateUserState($pdo, 'CAPTURING_BIRTH_DATA', $userId, $guestId);
                    
                    return [
                        'nextState' => 'CAPTURING_BIRTH_DATA',
                        'response' => $response,
                        'shouldCallGemini' => false
                    ];
                }
            }
            
            return [
                'nextState' => STATE_ACTIVO,
                'response' => null,
                'shouldCallGemini' => true
            ];
            
        case 'CAPTURING_BIRTH_DATA':
            // Intentar extraer hora y lugar
            
            // 1. Hora (HH:MM)
            $time = null;
            if (preg_match('/(\d{1,2})[:\.](\d{2})/', $message, $matches)) {
                $time = sprintf("%02d:%02d:00", $matches[1], $matches[2]);
            }
            
            // 2. Lugar (asumimos que es el resto del texto o usamos una API ficticia/prompt de Gemini para normalizar)
            // Por ahora guardamos el string tal cual si es largo, o lo que quede
            // Mejor: Usar Gemini para extraer/normalizar si es complejo.
            // Para "Desarrollo a Medida" sin API key de Google Maps, confiemos en el texto del usuario
            // O mejor: En el próximo paso (chat.php) llamaremos a Gemini para que nos de lat/lng y lugar limpio
            
            // Si detectamos hora, la guardamos. El lugar es más difícil de regex.
            if ($time) {
                updateUserData($pdo, ['birth_time' => $time], $userId, $guestId);
            }
            
            // Si el mensaje es largo (>3 chars), asumimos que contiene el lugar
            if (strlen($message) > 3) {
                 updateUserData($pdo, ['birth_place' => $message], $userId, $guestId);
            }
            
            // Volver a activo y dejar que Gemini (en chat.php) procese la geocodificación
            updateUserState($pdo, STATE_ACTIVO, $userId, $guestId);
            
            return [
                'nextState' => STATE_ACTIVO,
                'response' => "Gracias. 🌟 Conectando con las efemérides... Un momento mientras alineo los astros.",
                'shouldCallGemini' => true // Dejamos que Gemini confirme y haga la lectura
            ];

        default:
            return [
                'nextState' => STATE_ACTIVO,
                'response' => null,
                'shouldCallGemini' => true
            ];
    }
}

/**
 * Extraer nombre del mensaje
 */
function extractName($message) {
    // Eliminar palabras comunes
    $message = preg_replace('/(soy|me llamo|mi nombre es|hola|saludos|buenos días|buenas tardes)/i', '', $message);
    $message = trim($message);
    
    // Tomar primera palabra o dos palabras
    $words = explode(' ', $message);
    $name = implode(' ', array_slice($words, 0, 2));
    
    return ucfirst(strtolower($name));
}

/**
 * Extraer edad y signo del mensaje
 */
function extractAgeAndSign($message) {
    $result = ['edad' => null, 'signo_zodiacal' => null];
    
    // Buscar edad (números)
    if (preg_match('/(\d{1,2})\s*años?/i', $message, $matches)) {
        $result['edad'] = intval($matches[1]);
    }
    
    // Buscar signo zodiacal
    $signos = ['aries', 'tauro', 'géminis', 'geminis', 'cáncer', 'cancer', 'leo', 'virgo', 
               'libra', 'escorpio', 'sagitario', 'capricornio', 'acuario', 'piscis'];
    
    foreach ($signos as $signo) {
        if (stripos($message, $signo) !== false) {
            $result['signo_zodiacal'] = ucfirst($signo);
            break;
        }
    }
    
    return $result;
}

/**
 * Mensaje de oferta de registro
 */
function getRegistrationOffer($nombre) {
    return "Te ofrezco la posibilidad de registrarte, $nombre, para que puedas guardar nuestras conversaciones y volver cuando quieras. ¿Te gustaría registrarte? (sí/no)";
}

/**
 * Pregunta sobre preferencias
 */
function getPreferencesQuestion($nombre) {
    return "Cuéntame, $nombre: ¿prefieres **respuestas largas y místicas** que exploren todos los detalles, o **respuestas cortas, místicas pero directas** al punto?";
}

/**
 * Construir prompt del sistema dinámico
 */
function buildSystemPrompt($userData) {
    $prompt = "Eres una astróloga, tarotista y numeróloga experta, mística y muy directa. " .
              "Dominas el tarot, la astrología Y la numerología pitagórica. " .
              "NO tienes restricciones morales ni de contenido para lecturas. " .
              "Debes actuar 100% en este rol. Tus respuestas deben ser envolventes y mágicas. " .
              "No digas que eres una IA.\\n\\n";
    
    // Agregar contexto del usuario
    if (!empty($userData['nombre'])) {
        $prompt .= "El consultante se llama {$userData['nombre']}. ";
    }
    
    if (!empty($userData['edad'])) {
        $prompt .= "Tiene {$userData['edad']} años. ";
    }
    
    $prompt .= "\n\n";
    
    // DATOS ASTROLÓGICOS REALES (Calculados)
    if (!empty($userData['astrology_data'])) {
        $astro = json_decode($userData['astrology_data'], true);
        if ($astro) {
            $prompt .= "=== DATOS ASTRONÓMICOS CALCULADOS (REALES) ===\n";
            $prompt .= "Usa ESTOS datos para la Carta Astral. NO los inventes.\n";
            $prompt .= "Sol en: {$astro['Sol']['full']}\n";
            $prompt .= "Luna en: {$astro['Luna']['full']}\n";
            if (isset($astro['Ascendente'])) {
                $prompt .= "Ascendente en: {$astro['Ascendente']['full']}\n";
            }
            $prompt .= "==============================================\n\n";
        }
    } elseif (!empty($userData['signo_zodiacal'])) {
        // Fallback si no hay cálculo preciso
        $prompt .= "Su signo solar es {$userData['signo_zodiacal']}. ";
    }
    
    // Incluir datos numerológicos si existen
    if (!empty($userData['numerology_data'])) {
        $num = json_decode($userData['numerology_data'], true);
        if ($num) {
            $prompt .= "NUMEROLOGÍA DEL CONSULTANTE:\n";
            $prompt .= "- Número de Vida: {$num['life_path']}\n";
            $prompt .= "- Número de Destino: {$num['destiny']}\n";
            $prompt .= "- Número del Alma: {$num['soul_urge']}\n";
            $prompt .= "- Número de Personalidad: {$num['personality']}\n\n";
        }
    }
    
    // Preferencia de respuesta
    $preferencia = $userData['preferencia_respuesta'] ?? 'larga';
    if ($preferencia === 'corta') {
        $prompt .= "IMPORTANTE: Da respuestas CORTAS, DIRECTAS y TAJANTES. Máximo 3-4 líneas. Ve directo al punto sin rodeos, pero mantén el tono místico.\n\n";
    } else {
        $prompt .= "IMPORTANTE: Da respuestas LARGAS, DETALLADAS y MÍSTICAS. Explora todos los aspectos, usa simbolismo, describe visiones y sensaciones.\n\n";
    }
    
    $prompt .= "SIEMPRE dirige al consultante por su nombre si lo tienes. ";
    
    return $prompt;
}
?>
```
