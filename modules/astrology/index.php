<?php
// Configuración básica
$moduleName = 'Astrología';
session_start();
require_once '../../shared/api/db.php';
require_once '../../shared/api/conversation_state.php';

$userStatus = "Invitado";
if (isset($_SESSION['user_id'])) {
    $userData = getUserData($pdo, $_SESSION['user_id'], null);
    $userStatus = "✨ " . ($userData['nombre'] ?? 'Viajero');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Astrología - Oráculo Místico</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../shared/css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../assets/css/moon.css?v=<?= time() ?>"> <!-- Required for widget -->
    
    <style>
        /* Premium Background for Astrology */
        body { 
            background-color: #0f0c29; 
            color: #E6E6FA; 
            overflow: hidden; /* Prevent body scroll, use chat container */
        }
        .bg-image {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: url('../../assets/images/astrology_module.png') no-repeat center center/cover;
            z-index: -2; filter: brightness(0.6);
        }
        .bg-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(15, 12, 41, 0.9), rgba(48, 43, 99, 0.8));
            z-index: -1;
        }

        /* Chat Container adjustment to fit between header and footer */
        .chat-container {
            height: calc(100vh - 140px); /* Approx Header + Footer */
            overflow-y: auto;
            padding: 20px;
            margin-top: 0;
            scroll-behavior: smooth;
        }
        
        .message.ai .bubble {
            background: rgba(75, 0, 130, 0.4);
            border: 1px solid rgba(147, 112, 219, 0.3);
            border-left: 3px solid #ffd700;
            backdrop-filter: blur(5px);
            padding: 15px; border-radius: 15px;
            color: #fff;
        }
        
        .input-area {
            background: rgba(15, 12, 41, 0.9);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 215, 0, 0.15);
            padding: 15px;
            display: flex;
            gap: 10px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <!-- HEADER GRID LAYOUT (Identical to Tarot) -->
    <header class="app-header">
        <div class="header-left">
            <a href="../../home.php" class="brand-logo" title="Volver al Inicio">
                <i class="fa-solid fa-chevron-left" style="font-size: 1rem; margin-right: 5px;"></i>
                <h1 class="brand-text">ASTROLOGÍA</h1>
            </a>
            <div class="user-status-text">
                <i class="fa-solid fa-star"></i> <?= htmlspecialchars($userStatus) ?>
            </div>
        </div>

        <div class="header-center">
            <div id="moon-container"></div> <!-- Moon Widget will render here -->
        </div>

        <div class="header-right">
            <div class="questions-info" style="opacity: 0.8; font-size: 0.9rem;">
                <i class="fa-solid fa-infinity"></i>
            </div>
            <button class="settings-trigger">
                <i class="fa-solid fa-gear"></i>
            </button>
        </div>
    </header>

    <!-- Chat Container -->
    <div class="chat-container" id="chat-container">
        <div class="message ai">
            <div class="message-content">
                <div class="bubble">
                    Bienvenido al templo de las estrellas. ✨<br>
                    Soy tu guía astrológica. Escribe "Hola" para que los astros hablen.
                </div>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="input-area">
        <textarea id="message-input" placeholder="Consulta a los astros..." rows="1" style="flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.2); color:white; padding:12px; border-radius:20px; resize:none; font-family:inherit;"></textarea>
        <button id="send-btn" style="background:var(--primary-gold, #ffd700); border:none; border-radius:50%; width:45px; height:45px; cursor:pointer; display:flex; justify-content:center; align-items:center;">
            <i class="fa-solid fa-paper-plane" style="color:#000;"></i>
        </button>
    </div>

    <script src="../../shared/js/chat_base.js?v=<?= time() ?>"></script>
    <script src="../../shared/js/moon_widget.js?v=<?= time() ?>"></script> <!-- SHARED WIDGET -->
    <script>
        window.CHAT_API_ENDPOINT = 'api/astrology_chat.php';
        window.MODULE_NAME = 'astrology';
        
        // Simple chat logic for this module
        const textarea = document.getElementById('message-input');
        const container = document.getElementById('chat-container');
        
        textarea.addEventListener('keypress', (e) => {
             if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(); }
        });
        document.getElementById('send-btn').addEventListener('click', handleSend);
        
        function handleSend() {
            const val = textarea.value.trim();
            if(!val) return;
            
            // Add user message mock
            const uDiv = document.createElement('div');
            uDiv.className = 'message user'; 
            uDiv.style.cssText = 'display:flex; justify-content:flex-end; margin-top:10px;';
            uDiv.innerHTML = `<div class="bubble" style="background:rgba(255,215,0,0.2); padding:10px 15px; border-radius:15px; color:#fff;">${val}</div>`;
            container.appendChild(uDiv);
            
            textarea.value = '';
            sendMessage(val); // Call shared/chat_base.js logic if connected
        }
    </script>
</body>
</html>
