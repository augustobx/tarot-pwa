<?php
session_start();
require_once 'api/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['role'] === 'admin') {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['is_admin'] = true;
                header("Location: admin.php");
                exit;
            } else {
                $error = "Acceso restringido solo a administradores.";
            }
        } else {
            $error = "Usuario o contraseña inválidos.";
        }
    } else {
        $error = "Por favor completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Tarot PWA</title>
    <style>
        body { 
            background: linear-gradient(135deg, #2e1a47 0%, #1a0f2e 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            overflow: hidden;
        }
        .login-card {
            background: rgba(46, 26, 71, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            text-align: center;
        }
        h1 { color: #ffd700; margin-bottom: 30px; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; color: #ccc; margin-bottom: 5px; font-size: 14px; }
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #431259; 
            background: #1a0f2e; 
            color: #fff; 
            border-radius: 5px; 
            box-sizing: border-box;
            font-size: 16px;
            transition: 0.3s;
        }
        input:focus { border-color: #ffd700; outline: none; }
        .btn-login {
            background: #ffd700;
            color: #2e1a47;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-login:hover { background: #e6c200; }
        .error { 
            background: rgba(255, 0, 0, 0.2); 
            color: #ff6b6b; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            border: 1px solid #ff6b6b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Admin Access</h1>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Ingresar</button>
        </form>
    </div>
</body>
</html>
