<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Código de verificación</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 40px 20px;">

    <div style="max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px;">

        <h1 style="margin-top: 0;">
            Configurar contraseña
        </h1>

        <p>
            Hemos recibido una solicitud para agregar una contraseña a tu cuenta.
        </p>

        <p>
            Utiliza el siguiente código:
        </p>

        <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; text-align: center; padding: 20px 0;">
            {{ $code }}
        </div>

        <p>
            Este código es válido durante <strong>5 minutos</strong>.
        </p>

        <p>
            Si no solicitaste agregar una contraseña a tu cuenta, simplemente ignora este correo.
        </p>

    </div>

</body>
</html>