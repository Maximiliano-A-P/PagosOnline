<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verificación de cuenta</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 40px 20px;">

    <div style="max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px;">

        <h1 style="margin-top: 0;">
            Verificación de cuenta
        </h1>

        <p>
            Hemos recibido una solicitud para verificar tu cuenta.
        </p>

        <p>
            Introduce el siguiente código en la aplicación:
        </p>

        <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; text-align: center; padding: 20px 0;">
            {{ $code }}
        </div>

        <p>
            Este código es válido durante <strong>5 minutos</strong>.
        </p>

        <p>
            Si no solicitaste la verificación de esta cuenta, puedes ignorar este correo.
        </p>

    </div>

</body>
</html>