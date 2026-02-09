<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Pendiente - Oráculo Místico</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, #1a0b2e 0%, #2a1b3d 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            max-width: 500px;
        }
        .icon { font-size: 5rem; margin-bottom: 20px; }
        h1 {
            color: #ffd700;
            font-family: 'Cinzel', serif;
            margin-bottom: 15px;
        }
        p { color: #bca0d9; margin-bottom: 30px; line-height: 1.6; }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #ffd700;
            color: #120d1d;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⏳</div>
        <h1>Pago Pendiente</h1>
        <p>
            Tu pago está siendo procesado. 🌙<br>
            Te notificaremos cuando se acrediten las preguntas.<br><br>
            Esto puede demorar unos minutos.
        </p>
        <a href="/" class="btn">Volver al Oráculo</a>
    </div>
</body>
</html>
