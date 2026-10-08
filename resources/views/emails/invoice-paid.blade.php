<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Factura pagada</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 40px 20px;">

    <div style="max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px;">

        <h1 style="margin-top: 0;">
            Recibimos tu pago
        </h1>

        <p>
            Tu pago de <strong>{{ $invoice->service_name }}</strong> fue
            registrado correctamente.
        </p>

        <p>
            Importe pagado:
            <strong>${{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}</strong>
        </p>

        <p>
            Adjuntamos la factura en PDF, con el CAE y el código QR de ARCA.
        </p>

    </div>

</body>
</html>
