<?php
session_start();
require_once 'shared/api/db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
$balance = 0;

if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT nombre, preguntas_restantes FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userName = $user['nombre'] ?? '';
    $balance = $user['preguntas_restantes'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oráculo Místico - Elige tu camino espiritual</title>
    <meta name="description" content="Astrología, Tarot y Numerología con IA avanzada">
    <meta name="theme-color" content="#1a0b2e">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, #0a0015 0%, #1a0b2e 50%, #2e1a47 100%);
            min-height: 100vh;
            color: #e6d5f0;
            overflow-x: hidden;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 40px 20px 20px;
        }

        .header h1 {
            font-family: 'Cinzel', serif;
            font-size: 2.5rem;
            color: #d4af37;
            text-shadow: 0 0 20px rgba(212, 175, 55, 0.6);
            margin-bottom: 10px;
        }

        .header p {
            color: #b8a5d4;
            font-size: 1.1rem;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            text-align: right;
            color: #d4af37;
            font-size: 0.9rem;
        }

        .user-info button {
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid #d4af37;
            color: #d4af37;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 5px;
        }

        .user-info button:hover {
            background: rgba(212, 175, 55, 0.4);
            transform: scale(1.05);
        }

        /* Module Selector - Carousel */
        .selector-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .carousel-wrapper {
            position: relative;
            overflow: hidden;
            padding: 20px 60px;
        }

        .carousel {
            display: flex;
            gap: 30px;
            scroll-behavior: smooth;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .carousel::-webkit-scrollbar {
            display: none;
        }

        .module-card {
            flex: 0 0 350px;
            scroll-snap-align: center;
            background: rgba(74, 24, 117, 0.3);
            border: 2px solid rgba(212, 175, 55, 0.5);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
            cursor: pointer;
        }

        .module-card:hover {
            transform: translateY(-10px);
            border-color: #d4af37;
            box-shadow: 0 20px 40px rgba(212, 175, 55, 0.3);
        }

        .module-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 20px rgba(212, 175, 55, 0.4));
        }

        .module-card h2 {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            color: #d4af37;
            margin-bottom: 15px;
        }

        .module-card p {
            color: #b8a5d4;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .module-card button {
            background: linear-gradient(135deg, #d4af37 0%, #b8942c 100%);
            color: #1a0033;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        }

        .module-card button:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.5);
        }

        /* Carousel Navigation */
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(212, 175, 55, 0.2);
            border: 2px solid #d4af37;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10;
        }

        .carousel-nav:hover {
            background: rgba(212, 175, 55, 0.4);
        }

        .carousel-nav.left {
            left: 0;
        }

        .carousel-nav.right {
            right: 0;
        }

        .carousel-nav i {
            color: #d4af37;
            font-size: 1.5rem;
        }

        /* Indicators */
        .carousel-indicators {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.3);
            cursor: pointer;
            transition: all 0.3s;
        }

        .indicator.active {
            background: #d4af37;
            width: 30px;
            border-radius: 6px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 40px 20px;
            color: #b8a5d4;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .module-card {
                flex: 0 0 280px;
            }

            .carousel-wrapper {
                padding: 20px 50px;
            }
        }
    </style>
</head>
<body>
    <!-- User Info -->
    <?php if ($isLoggedIn): ?>
    <div class="user-info">
        <div>✨ <?= htmlspecialchars($userName) ?></div>
        <div style="font-size: 0.85rem;">Preguntas: <strong><?= $balance ?></strong></div>
        <button onclick="logout()">Cerrar Sesión</button>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="header">
        <h1>🌙 Oráculo Místico 🌙</h1>
        <p>Elige tu camino hacia la sabiduría espiritual</p>
    </div>

    <!-- Module Selector -->
    <div class="selector-container">
        <div class="carousel-wrapper">
            <div class="carousel-nav left" onclick="scrollCarousel('left')">
                <i class="fa-solid fa-chevron-left"></i>
            </div>
            
            <div class="carousel" id="carousel">
                <!-- Astrología -->
                <div class="module-card" data-module="astrology">
                    <div class="module-icon">🌟</div>
                    <h2>Astrología</h2>
                    <p>Descubre los secretos escritos en las estrellas. Carta natal, tránsitos planetarios y predicciones astrológicas personalizadas.</p>
                    <button onclick="selectModule('astrology')">Consultar Astros</button>
                </div>

                <!-- Tarot -->
                <div class="module-card" data-module="tarot">
                    <div class="module-icon">🔮</div>
                    <h2>Tarot</h2>
                    <p>Las cartas revelan tu camino. Tiradas personalizadas con interpretaciones profundas guiadas por la sabiduría ancestral.</p>
                    <button onclick="selectModule('tarot')">Consultar Cartas</button>
                </div>

                <!-- Numerología -->
                <div class="module-card" data-module="numerology">
                    <div class="module-icon">🔢</div>
                    <h2>Numerología</h2>
                    <p>Los números guardan tu verdad. Descubre tu número de vida, destino y los ciclos que marcan tu existencia.</p>
                    <button onclick="selectModule('numerology')">Consultar Números</button>
                </div>
            </div>

            <div class="carousel-nav right" onclick="scrollCarousel('right')">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </div>

        <div class="carousel-indicators">
            <div class="indicator active" data-index="0"></div>
            <div class="indicator" data-index="1"></div>
            <div class="indicator" data-index="2"></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>✨ Conecta con tu esencia espiritual ✨</p>
    </div>

    <script>
        const carousel = document.getElementById('carousel');
        const indicators = document.querySelectorAll('.indicator');
        let currentIndex = 1; // Start at Tarot (middle)

        // Initialize carousel at center
        function initCarousel() {
            const cardWidth = 350 + 30; // card width + gap
            carousel.scrollLeft = cardWidth * currentIndex - (carousel.offsetWidth - cardWidth) / 2;
            updateIndicators();
        }

        // Scroll carousel
        function scrollCarousel(direction) {
            const cardWidth = 350 + 30;
            if (direction === 'left') {
                currentIndex = Math.max(0, currentIndex - 1);
            } else {
                currentIndex = Math.min(2, currentIndex + 1);
            }
            carousel.scrollLeft = cardWidth * currentIndex - (carousel.offsetWidth - cardWidth) / 2;
            updateIndicators();
        }

        // Update indicators
        function updateIndicators() {
            indicators.forEach((ind, idx) => {
                ind.classList.toggle('active', idx === currentIndex);
            });
        }

        // Click indicator
        indicators.forEach((ind, idx) => {
            ind.addEventListener('click', () => {
                currentIndex = idx;
                const cardWidth = 350 + 30;
                carousel.scrollLeft = cardWidth * currentIndex - (carousel.offsetWidth - cardWidth) / 2;
                updateIndicators();
            });
        });

        // Select module
        function selectModule(module) {
            <?php if (!$isLoggedIn): ?>
            // Show registration prompt
            if (confirm('Para una experiencia personalizada y guardar tus consultas, ¿te gustaría registrarte?\n\nSi no, puedes continuar como invitado con funcionalidad limitada.')) {
                // Redirect with registration prompt
                window.location.href = `modules/${module}/index.php?prompt_register=1`;
            } else {
                // Continue as guest
                window.location.href = `modules/${module}/index.php`;
            }
            <?php else: ?>
            // Logged in, direct access
            window.location.href = `modules/${module}/index.php`;
            <?php endif; ?>
        }

        // Logout
        function logout() {
            if (confirm('¿Estás seguro de cerrar sesión?')) {
                window.location.href = 'api/logout.php';
            }
        }

        // Touch/Swipe support
        let startX = 0;
        carousel.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        carousel.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const diff = startX - endX;
            if (Math.abs(diff) > 50) {
                scrollCarousel(diff > 0 ? 'right' : 'left');
            }
        });

        // Initialize on load
        window.addEventListener('load', initCarousel);
    </script>

    <!-- Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('SW registered', reg))
            .catch(err => console.log('SW failed', err));
        }
    </script>
</body>
</html>
