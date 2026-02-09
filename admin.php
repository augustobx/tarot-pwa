<?php
session_start();
require_once 'api/db.php';

// Secure Admin Auth
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// --- HANDLE POST UPDATES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = "";

    // 1. Actualizar Packs de Preguntas
    if (isset($_POST['update_packs'])) {
        $packs = [
            'pack_1_preguntas' => $_POST['pack_1'],
            'pack_5_preguntas' => $_POST['pack_5'],
            'pack_10_preguntas' => $_POST['pack_10'],
            'pack_25_preguntas' => $_POST['pack_25'],
            'pack_50_preguntas' => $_POST['pack_50']
        ];
        
        foreach ($packs as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO config (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $msg = "Precios de packs actualizados correctamente.";
    }

    // 2. Actualizar Bienvenida
    if (isset($_POST['welcome'])) {
         $stmt = $pdo->prepare("INSERT INTO config (setting_key, setting_value) VALUES ('welcome_message', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$_POST['welcome'], $_POST['welcome']]);
        $msg = "Mensaje actualizado correctamente.";
    }

    // 3. Actualizar Modelo Gemini
    if (isset($_POST['gemini_model'])) {
        $stmt = $pdo->prepare("INSERT INTO config (setting_key, setting_value) VALUES ('gemini_model', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$_POST['gemini_model'], $_POST['gemini_model']]);
        $msg = "Modelo de IA actualizado correctamente.";
    }

    // 4. Actualizar API KEY
    if (isset($_POST['api_key'])) {
        $key = trim($_POST['api_key']);
        $stmt = $pdo->prepare("INSERT INTO config (setting_key, setting_value) VALUES ('gemini_api_key', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $key]);
        $msg = "API Key de Gemini actualizada correctamente.";
    }

    // 5. Agregar Preguntas a Usuario
    if (isset($_POST['add_questions']) && isset($_POST['user_id']) && isset($_POST['cantidad'])) {
        $userId = $_POST['user_id'];
        $cantidad = intval($_POST['cantidad']);
        
        $stmt = $pdo->prepare("UPDATE users SET preguntas_restantes = preguntas_restantes + ? WHERE id = ?");
        $stmt->execute([$cantidad, $userId]);
        
        $msg = "Se agregaron $cantidad preguntas al usuario ID $userId";
    }
    
    // 6. Actualizar MercadoPago
    if (isset($_POST['update_mp'])) {
        $token = trim($_POST['mp_access_token']);
        $publicKey = trim($_POST['mp_public_key']);
        
        $stmt = $pdo->prepare("INSERT INTO config (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute(['mp_access_token', $token, $token]);
        $stmt->execute(['mp_public_key', $publicKey, $publicKey]);
        
        $msg = "Configuración de MercadoPago actualizada correctamente.";
    }
}

// --- FETCH DATA ---
// Fetch packs
$pack1 = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_1_preguntas'")->fetchColumn() ?: 100;
$pack5 = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_5_preguntas'")->fetchColumn() ?: 450;
$pack10 = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_10_preguntas'")->fetchColumn() ?: 800;
$pack25 = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_25_preguntas'")->fetchColumn() ?: 1750;
$pack50 = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'pack_50_preguntas'")->fetchColumn() ?: 3000;

$welcome = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'welcome_message'")->fetchColumn();

// Fetch Modelo
$currentModel = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'gemini_model'")->fetchColumn();
if (!$currentModel) $currentModel = 'gemini-2.0-flash'; 

// Fetch API Key
$currentApiKey = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'gemini_api_key'")->fetchColumn();

// Fetch MercadoPago
$mpToken = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'mp_access_token'")->fetchColumn();
$mpPublicKey = $pdo->query("SELECT setting_value FROM config WHERE setting_key = 'mp_public_key'")->fetchColumn();

// Estadísticas
$userCount = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
$chatCount = $pdo->query("SELECT count(*) FROM chats")->fetchColumn();
$transCount = $pdo->query("SELECT count(*) FROM transactions")->fetchColumn();
$totalPreguntas = $pdo->query("SELECT SUM(preguntas_restantes) FROM users")->fetchColumn() ?: 0;
$users = $pdo->query("SELECT id, username, preguntas_restantes, preguntas_realizadas, created_at, role FROM users ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Tarot PWA</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { 
            background: #120d1d; 
            color: #e0e0e0;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1000px;
            margin: 0 auto 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #332145;
        }
        .admin-header h1 { margin: 0; color: #ffd700; font-weight: 600; }
        .logout-btn {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            border: 1px solid #ff6b6b;
            transition: 0.3s;
        }
        .logout-btn:hover { background: #ff6b6b; color: #fff; }

        .admin-container { 
            max-width: 1000px; 
            margin: 0 auto; 
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }
        .grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-box { 
            background: linear-gradient(135deg, #2e1a47 0%, #1f1234 100%);
            padding: 20px; 
            border-radius: 12px; 
            text-align: center; 
            border: 1px solid #431259;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .stat-box h3 { margin: 0 0 10px; font-weight: 300; font-size: 1rem; color: #bca0d9; }
        .stat-box p { margin: 0; font-size: 2.5rem; font-weight: 600; color: #ffd700; }

        .card {
            background: #1a0f2e;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #332145;
        }
        .card h2 { margin-top: 0; color: #ffd700; border-bottom: 1px solid #332145; padding-bottom: 15px; }

        form label { display: block; margin-top: 15px; color: #bca0d9; }
        input[type="number"], input[type="text"], textarea, select { 
            width: 100%; 
            padding: 12px; 
            background: #120d1d; 
            border: 1px solid #431259; 
            color: #fff; 
            border-radius: 6px;
            margin-top: 5px;
            font-family: inherit;
        }
        button.primary-btn { 
            margin-top: 20px; 
            padding: 12px 25px; 
            background: #ffd700; 
            color: #120d1d; 
            border: none; 
            border-radius: 6px;
            cursor: pointer; 
            font-weight: 600; 
            transition: 0.3s;
            display: inline-block;
            width: 100%;
        }
        button.primary-btn:hover { background: #e6c200; transform: translateY(-2px); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; color: #bca0d9; font-weight: 400; padding: 10px; border-bottom: 1px solid #431259; }
        td { padding: 12px 10px; border-bottom: 1px solid #2a1b3d; color: #e0e0e0; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        
        .role-badge { 
            font-size: 0.75rem; 
            padding: 2px 8px; 
            border-radius: 10px; 
            background: rgba(255, 215, 0, 0.1); 
            color: #ffd700; 
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        .role-badge.admin { background: rgba(255, 107, 107, 0.1); color: #ff6b6b; border-color: rgba(255, 107, 107, 0.3); }

        .fund-form { display: flex; gap: 10px; align-items: center; }
        .fund-form input { width: 100px; padding: 6px; margin: 0; }
        .fund-form button { 
            padding: 6px 12px; 
            background: #431259; 
            color: #fff; 
            border: 1px solid #5a1e78; 
            border-radius: 4px; 
            cursor: pointer;
            transition: 0.2s;
        }
        .fund-form button:hover { background: #5a1e78; }

        .success-msg { 
            background: rgba(46, 204, 113, 0.15); 
            color: #2ecc71; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 30px; 
            border: 1px solid rgba(46, 204, 113, 0.3);
            text-align: center;
        }
        
        .api-section {
            background: rgba(255, 215, 0, 0.05);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>🔮 Tarot PWA <span style="font-weight:300; color:#bca0d9; font-size:0.6em;">| Admin</span></h1>
        <a href="?logout=true" class="logout-btn">Cerrar Sesión</a>
    </header>

    <div class="admin-container">
        <?php if (!empty($msg)): ?>
            <div class="success-msg"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="grid-stats">
            <div class="stat-box">
                <h3>Usuarios</h3>
                <p><?= $userCount ?></p>
            </div>
            <div class="stat-box">
                <h3>Consultas Totales</h3>
                <p><?= $chatCount ?></p>
            </div>
            <div class="stat-box">
                <h3>Transacciones</h3>
                <p><?= $transCount ?></p>
            </div>
            <div class="stat-box">
                <h3>Preguntas Activas</h3>
                <p><?= $totalPreguntas ?></p>
            </div>
        </div>

        <div class="card">
            <h2>⚙️ Configuración Global</h2>
            <form method="POST">
                <div class="api-section">
                    <label style="margin-top:0; color: #ffd700;">🔑 Google Gemini API Key</label>
                    <input type="text" name="api_key" value="<?= htmlspecialchars($currentApiKey) ?>" placeholder="Pega aquí tu API Key (AIzaSy...)" required autocomplete="off">
                    <small style="color: #888;">Si ves errores de "Quota exceeded", cambia esta clave por una con facturación habilitada.</small>
                </div>
                
                <label>Mensaje de Bienvenida</label>
                <textarea name="welcome" rows="3"><?= $welcome ?></textarea>
                
                <label>Modelo de Inteligencia Artificial</label>
                <select name="gemini_model">
                    <option value="gemini-2.5-flash" <?= $currentModel === 'gemini-2.5-flash' ? 'selected' : '' ?>>Gemini 2.5 Flash (Recomendado)</option>
                    <option value="gemini-2.5-flash-lite" <?= $currentModel === 'gemini-2.5-flash-lite' ? 'selected' : '' ?>>Gemini 2.5 Flash-Lite (Económico)</option>
                    <option value="gemini-2.0-flash" <?= $currentModel === 'gemini-2.0-flash' ? 'selected' : '' ?>>Gemini 2.0 Flash (Anterior)</option>
                    <option value="gemini-2.0-pro" <?= $currentModel === 'gemini-2.0-pro' ? 'selected' : '' ?>>Gemini 2.0 Pro (Costoso)</option>
                </select>

                <button type="submit" class="primary-btn">Guardar Cambios</button>
            </form>
        </div>

        <div class="card">
            <h2>💳 MercadoPago - Configuración</h2>
            <form method="POST">
                <label>Access Token (Producción)</label>
                <input type="text" name="mp_access_token" value="<?= htmlspecialchars($mpToken ?? '') ?>" 
                       placeholder="APP_USR-..." style="font-family: monospace; font-size: 0.85rem;">
                <small style="color: #888; display: block; margin-top: 5px;">
                    Obtén tu token en: 
                    <a href="https://www.mercadopago.com.ar/developers/panel/app" 
                       target="_blank" style="color: #ffd700;">
                        Panel de Desarrolladores de MercadoPago
                    </a> → Tus aplicaciones → Credenciales de producción
                </small>
                
                <label style="margin-top: 15px;">Public Key (Producción - Opcional)</label>
                <input type="text" name="mp_public_key" value="<?= htmlspecialchars($mpPublicKey ?? '') ?>" 
                       placeholder="APP_USR-..." style="font-family: monospace; font-size: 0.85rem;">
                <small style="color: #888; display: block; margin-top: 5px;">
                    Solo necesario si usas Checkout Pro u otros productos de MP.
                </small>
                
                <button type="submit" name="update_mp" class="primary-btn" style="margin-top: 15px;">
                    💾 Guardar Configuración MP
                </button>
            </form>
        </div>

        <div class="card">
            <h2>💰 Packs de Preguntas - Configuración de Precios</h2>
            <form method="POST">
                <label>1 Pregunta - Precio ($)</label>
                <input type="number" name="pack_1" value="<?= $pack1 ?>" required step="0.01">
                
                <label>5 Preguntas - Precio ($)</label>
                <input type="number" name="pack_5" value="<?= $pack5 ?>" required step="0.01">
                
                <label>10 Preguntas - Precio ($)</label>
                <input type="number" name="pack_10" value="<?= $pack10 ?>" required step="0.01">
                
                <label>25 Preguntas - Precio ($)</label>
                <input type="number" name="pack_25" value="<?= $pack25 ?>" required step="0.01">
                
                <label>50 Preguntas - Precio ($)</label>
                <input type="number" name="pack_50" value="<?= $pack50 ?>" required step="0.01">

                <button type="submit" name="update_packs" class="primary-btn">Actualizar Packs</button>
            </form>
        </div>

        <div class="card">
            <h2>👥 Gestión de Usuarios</h2>
            <div style="max-height: 500px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Preguntas Rest.</th>
                            <th>Realizadas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <span class="role-badge <?= $u['role'] === 'admin' ? 'admin' : '' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td style="font-family: monospace; font-size: 1.1em; color: #4ade80;"><?= $u['preguntas_restantes'] ?></td>
                            <td style="font-family: monospace; opacity: 0.7;"><?= $u['preguntas_realizadas'] ?></td>
                            <td>
                                <form method="POST" class="fund-form">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="number" name="cantidad" placeholder="Cant." required min="1">
                                    <button type="submit" name="add_questions">+ Preguntas</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>