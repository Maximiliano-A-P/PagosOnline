<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <title>Desbloqueo de cuenta</title>
</head>

<body>
    <h2>Tu cuenta ha sido bloqueada temporalmente</h2>

    <p>
        Se alcanzó el límite de intentos de inicio de sesión
        permitido para tu cuenta.
    </p>

    <p>
        Si fuiste tú quien realizó estos intentos,
        puedes desbloquear tu cuenta utilizando el siguiente enlace:
    </p>

    <p>
        <a href="{{ $unlockUrl }}">
            Desbloquear mi cuenta
        </a>
    </p>

    <p>
        Este enlace es de un solo uso y tiene una duración limitada.
    </p>

    <p>
        Si no reconoces estos intentos, puedes ignorar este correo.
    </p>
</body>
</html>