<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Rechazado - Oráculo Místico</title>
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
            margin: 5px;
        }
        .btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
        .btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .btn.secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">❌</div>
        <h1>Pago No Completado</h1>
        <p>
            El pago no pudo ser procesado. 🌙<br>
            No se realizó ningún cargo.<br><br>
            Puedes intentar nuevamente con otro método de pago.
        </p>
        <a href="/" class="btn">Volver al Oráculo</a>
        <a href="javascript:history.back()" class="btn secondary">Intentar de Nuevo</a>
    </div>
</body>
</html>
