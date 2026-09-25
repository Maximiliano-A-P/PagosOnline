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
     * Devuelve Token/Sign válidos.
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
     * Genera el TRA, lo firma como CMS y obtiene Token/Sign
     * desde el WSAA.
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
     * Firma el XML utilizando PKCS#7/CMS.
     *
     * IMPORTANTE:
     *
     * ARCA requiere un CMS SignedData que contenga el
     * LoginTicketRequest firmado.
     *
     * Por eso NO utilizamos PKCS7_DETACHED.
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
         * Render contiene el certificado y la clave privada
         * como Base64 del contenido completo de los archivos PEM.
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

        $archivoCert = tempnam(
            sys_get_temp_dir(),
            'arca_crt_'
        );

        $archivoKey = tempnam(
            sys_get_temp_dir(),
            'arca_key_'
        );

        $archivoXml = tempnam(
            sys_get_temp_dir(),
            'arca_xml_'
        );

        $archivoCms = tempnam(
            sys_get_temp_dir(),
            'arca_cms_'
        );

        if (
            $archivoCert === false
            || $archivoKey === false
            || $archivoXml === false
            || $archivoCms === false
        ) {
            throw new RuntimeException(
                'No se pudieron crear los archivos temporales para la firma ARCA.'
            );
        }

        try {
            if (
                file_put_contents(
                    $archivoCert,
                    $certificadoPem
                ) === false
            ) {
                throw new RuntimeException(
                    'No se pudo escribir el certificado temporal de ARCA.'
                );
            }

            if (
                file_put_contents(
                    $archivoKey,
                    $clavePem
                ) === false
            ) {
                throw new RuntimeException(
                    'No se pudo escribir la clave privada temporal de ARCA.'
                );
            }

            if (
                file_put_contents(
                    $archivoXml,
                    $xml
                ) === false
            ) {
                throw new RuntimeException(
                    'No se pudo escribir el Login Ticket Request temporal.'
                );
            }

            /*
             * NO usamos PKCS7_DETACHED.
             *
             * Esto genera un CMS SignedData que contiene
             * el XML firmado, equivalente al procedimiento
             * indicado por ARCA con:
             *
             * openssl cms -sign ... -nodetach
             */
            $firmado = openssl_pkcs7_sign(
                $archivoXml,
                $archivoCms,
                'file://' . $archivoCert,
                'file://' . $archivoKey,
                [],
                PKCS7_BINARY
            );

            if (!$firmado) {
                $errores = [];

                while ($error = openssl_error_string()) {
                    $errores[] = $error;
                }

                throw new RuntimeException(
                    'No se pudo generar el CMS para ARCA: '
                    . (
                        $errores
                            ? implode(' | ', $errores)
                            : 'error desconocido de OpenSSL.'
                    )
                );
            }

            $contenido = file_get_contents($archivoCms);

            if (
                $contenido === false
                || trim($contenido) === ''
            ) {
                throw new RuntimeException(
                    'OpenSSL no produjo el CMS de ARCA.'
                );
            }

            return $this->extraerCmsBase64($contenido);

        } finally {
            @unlink($archivoCert);
            @unlink($archivoKey);
            @unlink($archivoXml);
            @unlink($archivoCms);
        }
    }

    /**
     * Extrae el Base64 del CMS generado por openssl_pkcs7_sign().
     *
     * Al utilizar PKCS7_BINARY sin PKCS7_DETACHED,
     * OpenSSL genera una estructura S/MIME cuyo cuerpo
     * contiene directamente el CMS en Base64.
     */
    private function extraerCmsBase64(string $contenido): string
    {
        /*
         * Buscamos el comienzo del cuerpo MIME.
         *
         * Normalmente aparece después de:
         *
         * Content-Transfer-Encoding: base64
         *
         * pero aceptamos tanto CRLF como LF.
         */
        $posicion = stripos(
            $contenido,
            'Content-Transfer-Encoding: base64'
        );

        if ($posicion === false) {
            throw new RuntimeException(
                'OpenSSL no generó una salida S/MIME con contenido Base64.'
            );
        }

        /*
         * Buscamos el final de los headers MIME.
         */
        $inicioCuerpo = strpos(
            $contenido,
            "\n\n",
            $posicion
        );

        if ($inicioCuerpo === false) {
            $inicioCuerpo = strpos(
                $contenido,
                "\r\n\r\n",
                $posicion
            );

            if ($inicioCuerpo !== false) {
                $inicioCuerpo += 4;
            }
        } else {
            $inicioCuerpo += 2;
        }

        if ($inicioCuerpo === false) {
            throw new RuntimeException(
                'No se pudo localizar el cuerpo Base64 del CMS generado por OpenSSL.'
            );
        }

        /*
         * Todo lo que queda después de los headers es el
         * CMS codificado en Base64.
         */
        $cms = substr(
            $contenido,
            $inicioCuerpo
        );

        /*
         * Eliminamos únicamente espacios y saltos de línea.
         */
        $cms = preg_replace(
            '/\s+/',
            '',
            $cms
        );

        if (!is_string($cms) || $cms === '') {
            throw new RuntimeException(
                'El CMS generado por OpenSSL está vacío.'
            );
        }

        /*
         * Validación local:
         * si esto falla, el problema está en la generación
         * del CMS y todavía ni siquiera llegamos a WSAA.
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
            $client = new SoapClient(
                $wsdl,
                [
                    'trace' => true,
                    'exceptions' => true,
                ]
            );

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

        if ($token === '') {
            throw new RuntimeException(
                'El WSAA respondió sin Token.'
            );
        }

        if ($sign === '') {
            throw new RuntimeException(
                'El WSAA respondió sin Sign.'
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