<?php

namespace App\Services\Arca;

use App\Models\ArcaConfig;
use Carbon\Carbon;
use RuntimeException;
use SoapClient;
use SoapFault;

class WsaaClient
{
    public function __construct(
        private readonly ArcaConfig $config
    ) {}

    /**
     * Devuelve Token/Sign válidos, reutilizando los guardados
     * si todavía no vencieron.
     *
     * @return array{token: string, sign: string}
     */
    public function obtenerCredenciales(): array
    {
        if ($this->tieneCredencialesVigentes()) {
            return [
                'token' => $this->config->token,
                'sign' => $this->config->sign,
            ];
        }

        return $this->autenticar();
    }

    private function tieneCredencialesVigentes(): bool
    {
        return $this->config->token
            && $this->config->sign
            && $this->config->token_expires_at
            && $this->config->token_expires_at->isFuture();
    }

    /**
     * Ejecuta el flujo completo del WSAA:
     *
     * 1. Genera el Login Ticket Request.
     * 2. Firma el XML con certificado + clave privada.
     * 3. Extrae correctamente el CMS Base64.
     * 4. Envía el CMS al WSAA.
     * 5. Guarda Token/Sign.
     */
    private function autenticar(): array
    {
        $ticketXml = $this->generarLoginTicketRequest();
        $cms = $this->firmarTicket($ticketXml);
        $respuesta = $this->llamarWsaa($cms);

        $this->config->update([
            'token' => $respuesta['token'],
            'sign' => $respuesta['sign'],
            'token_expires_at' => $respuesta['expiracion'],
        ]);

        return [
            'token' => $respuesta['token'],
            'sign' => $respuesta['sign'],
        ];
    }

    /**
     * Genera el Login Ticket Request requerido por WSAA.
     */
    private function generarLoginTicketRequest(): string
    {
        $ahora = Carbon::now();

        $generacion = $ahora
            ->copy()
            ->subMinutes(10)
            ->format('Y-m-d\TH:i:sP');

        $expiracion = $ahora
            ->copy()
            ->addMinutes(10)
            ->format('Y-m-d\TH:i:sP');

        $uniqueId = $ahora->timestamp;
        $servicio = config('arca.servicio');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<loginTicketRequest version="1.0">
    <header>
        <uniqueId>{$uniqueId}</uniqueId>
        <generationTime>{$generacion}</generationTime>
        <expirationTime>{$expiracion}</expirationTime>
    </header>
    <service>{$servicio}</service>
</loginTicketRequest>
XML;
    }

    /**
     * Firma el Login Ticket Request utilizando PKCS#7/CMS.
     *
     * Los certificados y la clave privada llegan desde Render
     * codificados en Base64 y se reconstruyen como archivos PEM
     * temporales para OpenSSL.
     */
    private function firmarTicket(string $xml): string
    {
        $certificadoBase64 = config('arca.certificado_crt');
        $claveBase64 = config('arca.private_key');

        if (!$certificadoBase64 || !$claveBase64) {
            throw new RuntimeException(
                'Falta configurar ARCA_CERTIFICATE_CRT o ARCA_PRIVATE_KEY en el .env.'
            );
        }

        /*
         * Los valores almacenados en Render son Base64 del contenido
         * completo de los archivos .crt y .key.
         */
        $certificadoPem = base64_decode($certificadoBase64, true);
        $clavePem = base64_decode($claveBase64, true);

        if ($certificadoPem === false) {
            throw new RuntimeException(
                'ARCA_CERTIFICATE_CRT no contiene un Base64 válido.'
            );
        }

        if ($clavePem === false) {
            throw new RuntimeException(
                'ARCA_PRIVATE_KEY no contiene un Base64 válido.'
            );
        }

        $archivoCert = tempnam(sys_get_temp_dir(), 'arca_crt_');
        $archivoKey = tempnam(sys_get_temp_dir(), 'arca_key_');
        $archivoXml = tempnam(sys_get_temp_dir(), 'arca_xml_');
        $archivoFirmado = tempnam(sys_get_temp_dir(), 'arca_cms_');

        if (
            $archivoCert === false ||
            $archivoKey === false ||
            $archivoXml === false ||
            $archivoFirmado === false
        ) {
            throw new RuntimeException(
                'No se pudieron crear los archivos temporales para la firma ARCA.'
            );
        }

        try {
            if (file_put_contents($archivoCert, $certificadoPem) === false) {
                throw new RuntimeException(
                    'No se pudo escribir el certificado temporal de ARCA.'
                );
            }

            if (file_put_contents($archivoKey, $clavePem) === false) {
                throw new RuntimeException(
                    'No se pudo escribir la clave privada temporal de ARCA.'
                );
            }

            if (file_put_contents($archivoXml, $xml) === false) {
                throw new RuntimeException(
                    'No se pudo escribir el Login Ticket Request temporal.'
                );
            }

            $firmado = openssl_pkcs7_sign(
                $archivoXml,
                $archivoFirmado,
                'file://' . $archivoCert,
                'file://' . $archivoKey,
                [],
                PKCS7_DETACHED | PKCS7_BINARY
            );

            if (!$firmado) {
                $errores = [];

                while ($error = openssl_error_string()) {
                    $errores[] = $error;
                }

                throw new RuntimeException(
                    'No se pudo firmar el ticket de acceso: '
                    . ($errores ? implode(' | ', $errores) : 'error desconocido de OpenSSL.')
                );
            }

            $contenidoMime = file_get_contents($archivoFirmado);

            if ($contenidoMime === false || $contenidoMime === '') {
                throw new RuntimeException(
                    'OpenSSL no produjo contenido para la firma CMS.'
                );
            }

            /*
             * IMPORTANTE:
             *
             * openssl_pkcs7_sign() genera un mensaje MIME.
             *
             * La salida tiene aproximadamente esta estructura:
             *
             * MIME-Version: 1.0
             * Content-Type: multipart/signed...
             *
             * [contenido original]
             *
             * ------boundary
             * Content-Type: application/x-pkcs7-signature...
             * Content-Transfer-Encoding: base64
             *
             * MIIF...
             * ...
             *
             * ------boundary--
             *
             * WSAA necesita únicamente el bloque Base64 del CMS,
             * no todo el mensaje MIME.
             */
            return $this->extraerBase64DelMime($contenidoMime);

        } finally {
            @unlink($archivoCert);
            @unlink($archivoKey);
            @unlink($archivoXml);
            @unlink($archivoFirmado);
        }
    }

    /**
     * Extrae exclusivamente el CMS Base64 de la parte
     * application/x-pkcs7-signature generada por OpenSSL.
     *
     * No se utiliza explode("\n\n") porque la salida de OpenSSL
     * contiene varias secciones MIME antes del CMS.
     */
    private function extraerBase64DelMime(string $contenidoMime): string
    {
        $patron = '/Content-Transfer-Encoding:\s*base64\s*\r?\n\r?\n'
            . '([A-Za-z0-9+\/=\r\n]+)'
            . '/i';

        if (!preg_match($patron, $contenidoMime, $coincidencias)) {
            throw new RuntimeException(
                'No se encontró el bloque Base64 de la firma PKCS#7.'
            );
        }

        $cms = preg_replace('/\s+/', '', $coincidencias[1]);

        if (!is_string($cms) || $cms === '') {
            throw new RuntimeException(
                'El CMS generado por OpenSSL está vacío.'
            );
        }

        /*
         * Validamos que lo que vamos a enviar realmente sea
         * Base64 válido. No mostramos su contenido en los logs.
         */
        if (base64_decode($cms, true) === false) {
            throw new RuntimeException(
                'El CMS generado por OpenSSL no contiene un Base64 válido.'
            );
        }

        return $cms;
    }

    /**
     * Envía el CMS al WSAA mediante SOAP.
     */
    private function llamarWsaa(string $cms): array
    {
        $wsdl = config(
            'arca.wsaa_wsdl.' . config('arca.ambiente')
        );

        if (!$wsdl) {
            throw new RuntimeException(
                'No está configurado el WSDL de WSAA para el ambiente de ARCA.'
            );
        }

        try {
            $client = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
            ]);

            $resultado = $client->loginCms([
                'in0' => $cms,
            ]);

        } catch (SoapFault $e) {
            throw new RuntimeException(
                'Error al autenticar contra el WSAA de ARCA: '
                . $e->getMessage()
            );
        }

        if (
            !isset($resultado->loginCmsReturn)
            || !$resultado->loginCmsReturn
        ) {
            throw new RuntimeException(
                'El WSAA de ARCA no devolvió una respuesta válida.'
            );
        }

        $xmlRespuesta = simplexml_load_string(
            $resultado->loginCmsReturn
        );

        if ($xmlRespuesta === false) {
            throw new RuntimeException(
                'No se pudo interpretar la respuesta XML del WSAA.'
            );
        }

        $token = (string) $xmlRespuesta->credentials->token;
        $sign = (string) $xmlRespuesta->credentials->sign;
        $expiracion = (string) $xmlRespuesta->header->expirationTime;

        if ($token === '' || $sign === '') {
            throw new RuntimeException(
                'El WSAA respondió sin Token o Sign.'
            );
        }

        if ($expiracion === '') {
            throw new RuntimeException(
                'El WSAA respondió sin fecha de expiración.'
            );
        }

        return [
            'token' => $token,
            'sign' => $sign,
            'expiracion' => Carbon::parse($expiracion),
        ];
    }
}