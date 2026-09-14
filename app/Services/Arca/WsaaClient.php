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
     * Devuelve Token/Sign válidos, reusando los guardados si
     * todavía no vencieron, o pidiendo unos nuevos si hace falta.
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
     * Ejecuta el flujo completo del WSAA: arma el XML, lo firma,
     * lo manda a ARCA, y guarda el Token/Sign resultante.
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
     * Arma el XML "Login Ticket Request" que exige el WSAA.
     * uniqueId debe ser único por request (usamos el timestamp).
     */
    private function generarLoginTicketRequest(): string
    {
        $ahora = Carbon::now();
        $generacion = $ahora->copy()->subMinutes(10)->format('Y-m-d\TH:i:sP');
        $expiracion = $ahora->copy()->addMinutes(10)->format('Y-m-d\TH:i:sP');
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
     * Firma el XML con el certificado + clave privada de la
     * empresa (firma CMS/PKCS#7), tal como exige ARCA.
     *
     * openssl_pkcs7_sign trabaja con archivos en disco, por eso
     * se usan archivos temporales que se borran al finalizar.
     */
    private function firmarTicket(string $xml): string
    {
        if (!$this->config->certificate_path || !$this->config->private_key_path) {
            throw new RuntimeException(
                'Falta configurar el certificado o la clave privada de ARCA.'
            );
        }

        $archivoXml = tempnam(sys_get_temp_dir(), 'arca_ttl_');
        $archivoFirmado = tempnam(sys_get_temp_dir(), 'arca_cms_');

        file_put_contents($archivoXml, $xml);

        $firmado = openssl_pkcs7_sign(
            $archivoXml,
            $archivoFirmado,
            'file://' . $this->config->certificate_path,
            'file://' . $this->config->private_key_path,
            [],
            PKCS7_DETACHED | PKCS7_BINARY
        );

        if (!$firmado) {
            @unlink($archivoXml);
            @unlink($archivoFirmado);
            throw new RuntimeException(
                'No se pudo firmar el ticket de acceso: ' . openssl_error_string()
            );
        }

        // openssl_pkcs7_sign devuelve el archivo en formato MIME
        // (con headers y separadores). Necesitamos aislar el
        // bloque en Base64 puro que exige el WSAA.
        $contenido = file_get_contents($archivoFirmado);
        $cms = $this->extraerBase64DelMime($contenido);

        @unlink($archivoXml);
        @unlink($archivoFirmado);

        return $cms;
    }

    /**
     * openssl_pkcs7_sign devuelve algo tipo:
     *   MIME-Version: 1.0
     *   Content-Type: application/x-pkcs7-signature...
     *
     *   <base64 en varias líneas>
     *
     * Esta función se queda solo con el bloque Base64.
     */
    private function extraerBase64DelMime(string $contenidoMime): string
    {
        $partes = explode("\n\n", $contenidoMime, 2);

        if (count($partes) < 2) {
            throw new RuntimeException(
                'Formato inesperado en la salida de la firma PKCS#7.'
            );
        }

        return str_replace(["\r", "\n"], '', $partes[1]);
    }

    /**
     * Llama al método loginCms del WSAA vía SOAP, y parsea el
     * XML de respuesta para sacar Token, Sign y vencimiento.
     */
    private function llamarWsaa(string $cms): array
    {
        $wsdl = config('arca.wsaa_wsdl.' . config('arca.ambiente'));

        try {
            $client = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
            ]);

            $resultado = $client->loginCms(['in0' => $cms]);
        } catch (SoapFault $e) {
            throw new RuntimeException(
                'Error al autenticar contra el WSAA de ARCA: ' . $e->getMessage()
            );
        }

        $xmlRespuesta = simplexml_load_string($resultado->loginCmsReturn);

        if ($xmlRespuesta === false) {
            throw new RuntimeException(
                'No se pudo interpretar la respuesta del WSAA.'
            );
        }

        return [
            'token' => (string) $xmlRespuesta->credentials->token,
            'sign' => (string) $xmlRespuesta->credentials->sign,
            'expiracion' => Carbon::parse(
                (string) $xmlRespuesta->header->expirationTime
            ),
        ];
    }
}