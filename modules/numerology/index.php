<?php
// Configuración básica
$moduleName = 'Numerología';
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
    <title>Numerología - Oráculo Místico</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../shared/css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../assets/css/moon.css?v=<?= time() ?>"> <!-- Required for widget -->
    
    <style>
        /* Premium Background for Numerology */
        body { 
            background-color: #002b20; 
            color: #F0FFF0;
            overflow: hidden; 
        }
        .bg-image {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: url('../../assets/images/numerology_module.png') no-repeat center center/cover;
            z-index: -2; filter: brightness(0.6);
        }
        .bg-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(0, 43, 32, 0.9), rgba(0, 77, 64, 0.8));
            z-index: -1;
        }
        .floating-numbers {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: -1; opacity: 0.05;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Ctext x='50' y='50' font-family='serif' font-size='20' fill='%23ffffff'%3E123%3C/text%3E%3C/svg%3E");
        }

        .chat-container {
            height: calc(100vh - 140px);
            overflow-y: auto;
            padding: 20px;
        }
        
        .message.ai .bubble {
            background: rgba(46, 139, 87, 0.3);
            border: 1px solid rgba(60, 179, 113, 0.3);
            border-left: 3px solid #7FFFD4;
            backdrop-filter: blur(5px);
            padding: 15px; border-radius: 15px;
            color: #fff;
        }
        
        .input-area {
            background: rgba(0, 43, 32, 0.9);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(127, 255, 212, 0.2);
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
    <div class="floating-numbers"></div>

    <!-- HEADER GRID LAYOUT (Identical to Tarot) -->
    <header class="app-header">
        <div class="header-left">
            <a href="../../home.php" class="brand-logo" title="Volver al Inicio">
                <i class="fa-solid fa-chevron-left" style="font-size: 1rem; margin-right: 5px;"></i>
                <h1 class="brand-text">NUMEROLOGÍA</h1>
            </a>
            <div class="user-status-text">
                <i class="fa-solid fa-hashtag"></i> <?= htmlspecialchars($userStatus) ?>
            </div>
        </div>

        <div class="header-center">
            <div id="moon-container"></div> <!-- Moon Widget will render here -->
        </div>

        <div class="header-right">
            <div class="questions-info" style="opacity: 0.8; font-size: 0.9rem;">
                <i class="fa-solid fa-calculator"></i>
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
                    Bienvenido al orden oculto de los números. 🔢<br>
                    Descubre tu vibración actual. Escribe "Hola".
                </div>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="input-area">
        <textarea id="message-input" placeholder="Consulta tus números..." rows="1" style="flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(127,255,212,0.2); color:white; padding:12px; border-radius:20px; resize:none; font-family:inherit;"></textarea>
        <button id="send-btn" style="background:linear-gradient(135deg, #3CB371 0%, #2E8B57 100%); border:none; border-radius:50%; width:45px; height:45px; cursor:pointer; display:flex; justify-content:center; align-items:center;">
            <i class="fa-solid fa-paper-plane" style="color:white;"></i>
        </button>
    </div>

    <script src="../../shared/js/chat_base.js?v=<?= time() ?>"></script>
    <script src="../../shared/js/moon_widget.js?v=<?= time() ?>"></script> <!-- SHARED WIDGET -->
    <script>
        window.CHAT_API_ENDPOINT = 'api/numerology_chat.php';
        window.MODULE_NAME = 'numerology';
        
        const textarea = document.getElementById('message-input');
        const container = document.getElementById('chat-container');
        
        textarea.addEventListener('keypress', (e) => {
             if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(); }
        });
        document.getElementById('send-btn').addEventListener('click', handleSend);
        
        function handleSend() {
            const val = textarea.value.trim();
            if(!val) return;
            
            const uDiv = document.createElement('div');
            uDiv.className = 'message user'; 
            uDiv.style.cssText = 'display:flex; justify-content:flex-end; margin-top:10px;';
            uDiv.innerHTML = `<div class="bubble" style="background:rgba(127,255,212,0.2); padding:10px 15px; border-radius:15px; color:#fff;">${val}</div>`;
            container.appendChild(uDiv);
            
            textarea.value = '';
            sendMessage(val);
        }
    </script>
</body>
</html>
