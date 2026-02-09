<?php
session_start();
require_once '../../shared/api/db.php';
require_once '../../shared/api/conversation_state.php';

// Determine User Status for Header
$userStatus = "Invitado";
$userName = "Invitado";

if (isset($_SESSION['user_id'])) {
    $userData = getUserData($pdo, $_SESSION['user_id'], null);
    $userName = $userData['nombre'] ?? 'Viajero';
    $userStatus = "✨ $userName"; 
} 

// Welcome Message
$welcome = "✨ Bienvenido al Tarot Místico. Las cartas te esperan. ¿Qué deseas consultar hoy?";
if (!isset($_SESSION['user_id'])) {
     $welcome .= "\\n\\n(Eres invitado. Regístrate para guardar tu historial).";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tarot Místico</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../shared/css/global.css?v=<?= time() ?>">
    <!-- Note: assets/css/moon.css is loaded via global import or here? Global.css doesn't import it. 
         We MUST include moon.css for the widget to look like the pill. -->
    <link rel="stylesheet" href="../../assets/css/moon.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../assets/css/modal.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/tarot_cards.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
        <!-- HEADER GRID LAYOUT (Perfect Alignment) -->
        <header class="app-header">
            <!-- Left: Brand -->
            <div class="header-left">
                <a href="../../home.php" class="brand-logo" title="Volver al Inicio">
                    <i class="fa-solid fa-chevron-left" style="font-size: 1rem; margin-right: 5px;"></i>
                    <h1 class="brand-text">ORÁCULO MÍSTICO</h1>
                </a>
                <div class="user-status-text">
                    <i class="fa-solid fa-user-astronaut"></i> <?= htmlspecialchars($userStatus) ?>
                </div>
            </div>

            <!-- Center: Moon Widget -->
            <div class="header-center">
                <div id="moon-container"></div> 
            </div>

            <!-- Right: Functions -->
            <div class="header-right">
                <div class="questions-info" id="add-questions-btn">
                    <i class="fa-solid fa-plus"></i> Preguntas
                </div>
                <button class="settings-trigger" id="settings-btn" title="Configuración">
                    <i class="fa-solid fa-gear"></i>
                </button>
            </div>
        </header>

        <!-- Chat Container -->
        <div id="chat" class="chat"></div>

        <!-- Input Footer -->
        <footer>
            <div class="input-area">
                <button id="image-btn" class="icon-btn"><i class="fa-solid fa-image"></i></button>
                <input type="file" id="image-upload" accept="image/*" style="display: none;">
                <textarea id="user-input" placeholder="Escribe tu consulta..." rows="1"></textarea>
                <button id="send-btn" class="icon-btn"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
            <div id="image-preview-container" class="hidden">
                <img id="image-preview" src="" alt="Preview">
                <button id="remove-image"><i class="fa-solid fa-times"></i></button>
            </div>
        </footer>
    </div>

    <!-- Modals -->
    <div id="settings-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>⚙️ Configuración</h2>
            <div id="settings-dynamic-content"></div>
            <button id="logout-btn" class="btn hidden">Cerrar Sesión</button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../shared/js/chat_base.js?v=<?= time() ?>"></script>
    <script src="../../shared/js/moon_widget.js?v=<?= time() ?>"></script> <!-- SHARED WIDGET -->
    <script>
        window.CHAT_API_ENDPOINT = 'api/tarot_chat.php';
        window.MODULE_NAME = 'tarot';
    </script>
    <script src="js/tarot.js?v=<?= time() ?>"></script>
    <script>
        // Init Welcome
        appendMessage('ai', <?= json_encode($welcome) ?>, document.getElementById('chat'), true);
    </script>
</body>
</html>
