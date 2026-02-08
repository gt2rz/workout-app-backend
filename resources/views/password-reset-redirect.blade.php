<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restableciendo contraseña...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 14px 32px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px 5px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .countdown {
            color: #667eea;
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔐</div>
        <h1>Restableciendo tu contraseña</h1>
        <p>Intentando abrir la aplicación Workout App...</p>
        <div class="loader"></div>
        <p class="countdown">Redirigiendo en <span id="countdown">5</span> segundos</p>
        <div style="margin-top: 30px;">
            <a href="{{ $deepLink }}" class="btn" id="openApp">Abrir App Manualmente</a>
            @if($expoDevelopmentUrl)
                <a href="{{ $expoDevelopmentUrl }}" class="btn" style="background: #000;">Abrir en Expo Go</a>
            @endif
            <a href="{{ $apiEndpoint }}" class="btn btn-secondary">Ver instrucciones API</a>
        </div>
        <p style="margin-top: 30px; font-size: 14px; color: #999;">
            Si no tienes la app instalada, descárgala desde tu tienda de aplicaciones.
        </p>
        @if($expoDevelopmentUrl)
            <p style="margin-top: 10px; font-size: 12px; color: #667eea;">
                ⚡ Modo desarrollo: Usando Expo Go
            </p>
        @endif
    </div>

    <script>
        const deepLink = "{{ $deepLink }}";
        const expoDevelopmentUrl = "{{ $expoDevelopmentUrl ?? '' }}";
        const apiEndpoint = "{{ $apiEndpoint }}";
        let countdown = 5;

        // Intentar abrir la app inmediatamente
        // En desarrollo, preferir Expo Go si está disponible
        const urlToOpen = expoDevelopmentUrl || deepLink;
        window.location.href = urlToOpen;

        // Countdown y fallback
        const countdownElement = document.getElementById('countdown');
        const interval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;

            if (countdown <= 0) {
                clearInterval(interval);
                // Si después de 5 segundos no abrió la app, mostrar instrucciones
                document.querySelector('.loader').style.display = 'none';
                document.querySelector('.countdown').innerHTML =
                    '<span style="color: #e74c3c;">La app no se abrió automáticamente.</span>';
            }
        }, 1000);

        // Intentar abrir la app cada 2 segundos
        const openAppInterval = setInterval(() => {
            // Alternar entre deep link y Expo URL si está disponible
            if (expoDevelopmentUrl && countdown % 2 === 0) {
                window.location.href = expoDevelopmentUrl;
            } else {
                window.location.href = deepLink;
            }
        }, 2000);

        // Detener intentos después de 10 segundos
        setTimeout(() => {
            clearInterval(openAppInterval);
        }, 10000);

        // Detectar si el usuario regresa a la página (la app no se abrió)
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                console.log('Usuario regresó - la app no se abrió');
            }
        });
    </script>
</body>
</html>
