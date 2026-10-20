<?php

namespace App\Services\Arca;

use App\Models\ArcaConfig;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;
use SoapClient;
use SoapFault;

class WsaaClient
{
    private string $servicio;

    public function __construct(
        private readonly ArcaConfig $config,
        string $servicio = 'wsfe'
    ) {
        if (! in_array($servicio, ['wsfe', 'wsfex'], true)) {
            throw new InvalidArgumentException(
                'Servicio ARCA no admitido: ' . $servicio
            );
        }

        $this->servicio = $servicio;
    }

    /**
     * Devuelve Token/Sign válidos para el servicio seleccionado.
     *
     * @return array{token: string, sign: string}
     */
    public function obtenerCredenciales(): array
    {
        $campos = $this->camposCredenciales();

        $token = $this->config->{$campos['token']};
        $sign = $this->config->{$campos['sign']};
        $expiracion = $this->config->{$campos['expiracion']};

        if (
            $token
            && $sign
            && $expiracion
            && $expiracion->isFuture()
        ) {
            return [
                'token' => $token,
                'sign' => $sign,
            ];
        }

        return $this->autenticar();
    }

    /**
     * Nombres de columnas según el servicio.
     */
    private function camposCredenciales(): array
    {
        if ($this->servicio === 'wsfex') {
            return [
                'token' => 'token_wsfex',
                'sign' => 'sign_wsfex',
                'expiracion' => 'token_wsfex_expires_at',
            ];
        }

        return [
            'token' => 'token',
            'sign' => 'sign',
            'expiracion' => 'token_expires_at',
        ];
    }

    /**
     * Genera el TRA, lo firma como CMS y obtiene Token/Sign
     * desde WSAA para el servicio seleccionado.
     */
    private function autenticar(): array
    {
        $ticketXml = $this->generarLoginTicketRequest();
        $cms = $this->firmarTicket($ticketXml);
        $respuesta = $this->llamarWsaa($cms);

        $campos = $this->camposCredenciales();

        $this->config->update([
            $campos['token'] => $respuesta['token'],
            $campos['sign'] => $respuesta['sign'],
            $campos['expiracion'] => $respuesta['expiracion'],
        ]);

        return [
            'token' => $respuesta['token'],
            'sign' => $respuesta['sign'],
        ];
    }

    /**
     * Genera el Login Ticket Request de WSAA.
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

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<loginTicketRequest version="1.0">
    <header>
        <uniqueId>{$uniqueId}</uniqueId>
        <generationTime>{$generacion}</generationTime>
        <expirationTime>{$expiracion}</expirationTime>
    </header>
    <service>{$this->servicio}</service>
</loginTicketRequest>
XML;
    }

    /**
     * Firma el XML usando PKCS#7/CMS.
     */
    private function firmarTicket(string $xml): string
    {
        $certificadoBase64 = config('arca.certificado_crt');
        $claveBase64 = config('arca.private_key');

        if (! $certificadoBase64 || ! $claveBase64) {
            throw new RuntimeException(
                'Falta configurar ARCA_CERTIFICATE_CRT o ARCA_PRIVATE_KEY en el .env.'
            );
        }

        $certificadoPem = base64_decode(
            $certificadoBase64,
            true
        );

        $clavePem = base64_decode(
            $claveBase64,
            true
        );

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
        $archivoCms = tempnam(sys_get_temp_dir(), 'arca_cms_');

        if (
            $archivoCert === false
            || $archivoKey === false
            || $archivoXml === false
            || $archivoCms === false
        ) {
            foreach ([
                $archivoCert,
                $archivoKey,
                $archivoXml,
                $archivoCms,
            ] as $archivo) {
                if (is_string($archivo)) {
                    @unlink($archivo);
                }
            }

            throw new RuntimeException(
                'No se pudieron crear los archivos temporales para la firma ARCA.'
            );
        }

        try {
            if (
                file_put_contents($archivoCert, $certificadoPem) === false
                || file_put_contents($archivoKey, $clavePem) === false
                || file_put_contents($archivoXml, $xml) === false
            ) {
                throw new RuntimeException(
                    'No se pudieron escribir los archivos temporales para la firma ARCA.'
                );
            }

            /*
             * Se genera CMS SignedData con el XML incluido.
             * No se utiliza PKCS7_DETACHED.
             */
            $firmado = openssl_pkcs7_sign(
                $archivoXml,
                $archivoCms,
                'file://' . $archivoCert,
                'file://' . $archivoKey,
                [],
                PKCS7_BINARY
            );

            if (! $firmado) {
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

            if ($contenido === false || trim($contenido) === '') {
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
     * Extrae el CMS Base64 de la salida S/MIME de OpenSSL.
     */
    private function extraerCmsBase64(string $contenido): string
    {
        $posicion = stripos(
            $contenido,
            'Content-Transfer-Encoding: base64'
        );

        if ($posicion === false) {
            throw new RuntimeException(
                'OpenSSL no generó una salida S/MIME con contenido Base64.'
            );
        }

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
                'No se pudo localizar el cuerpo Base64 del CMS.'
            );
        }

        $cms = preg_replace(
            '/\s+/',
            '',
            substr($contenido, $inicioCuerpo)
        );

        if (! is_string($cms) || $cms === '') {
            throw new RuntimeException(
                'El CMS generado por OpenSSL está vacío.'
            );
        }

        if (base64_decode($cms, true) === false) {
            throw new RuntimeException(
                'El CMS generado por OpenSSL no contiene un Base64 válido.'
            );
        }

        return $cms;
    }

    /**
     * Envía el CMS a WSAA y procesa Token/Sign.
     */
    private function llamarWsaa(string $cms): array
    {
        $wsdl = config(
            'arca.wsaa_wsdl.' . config('arca.ambiente')
        );

        if (! $wsdl) {
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
                . $e->getMessage(),
                0,
                $e
            );
        }

        if (
            ! isset($resultado->loginCmsReturn)
            || ! $resultado->loginCmsReturn
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

        if ($token === '' || $sign === '' || $expiracion === '') {
            throw new RuntimeException(
                'El WSAA respondió sin Token, Sign o fecha de expiración.'
            );
        }

        return [
            'token' => $token,
            'sign' => $sign,
            'expiracion' => Carbon::parse($expiracion),
        ];
    }
}