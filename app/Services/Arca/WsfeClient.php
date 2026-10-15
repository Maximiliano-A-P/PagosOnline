<?php

namespace App\Services\Arca;

use RuntimeException;
use SoapClient;
use SoapFault;

/**
 * Cliente directo del WSFE de ARCA (reemplaza a la API Node).
 *
 * Hace dos cosas, igual que la API:
 *   1) consulta el último comprobante autorizado (FECompUltimoAutorizado)
 *   2) solicita el CAE (FECAESolicitar)
 *
 * Devuelve el mismo formato que devolvía la API:
 *   ['exito' => bool, 'cae', 'caeVencimiento', 'numeroComprobante', 'errores' => [...]]
 */
class WsfeClient
{
    private ?SoapClient $client = null;

    /**
     * @param  array  $p  Payload armado por ArcaApiService.
     */
    public function solicitarCae(array $p): array
    {
        $auth = [
            'Token' => $p['token'],
            'Sign' => $p['sign'],
            'Cuit' => $p['cuit'],
        ];

        $numero = $this->proximoNumero(
            $auth,
            (int) $p['puntoVenta'],
            (int) $p['tipoComprobante']
        );

        $detalle = [
            'Concepto' => $p['concepto'],
            'DocTipo' => $p['docTipo'],
            'DocNro' => (int) $p['docNro'],
            'CbteDesde' => $numero,
            'CbteHasta' => $numero,
            'CbteFch' => $p['fecha'],
            'ImpTotal' => $p['importeTotal'],
            'ImpNeto' => $p['importeNeto'],
            'ImpTotConc' => 0,
            'ImpOpEx' => 0,
            'ImpTrib' => 0,
            'ImpIVA' => $p['importeIva'] ?? 0,
            'MonId' => 'PES',
            'MonCotiz' => 1,
            'CondicionIVAReceptorId' => $p['condicionIvaReceptorId'],
        ];

        // Concepto 2 (servicios): fechas de servicio y vencimiento.
        if ((int) $p['concepto'] !== 1) {
            $detalle['FchServDesde'] = $p['fchServDesde'];
            $detalle['FchServHasta'] = $p['fchServHasta'];
            $detalle['FchVtoPago'] = $p['fchVtoPago'];
        }

        // Solo A/M: detalle de IVA por alícuota.
        if (! empty($p['alicuotasIva'])) {
            $detalle['Iva'] = [
                'AlicIva' => array_map(fn ($a) => [
                    'Id' => $a['id'],
                    'BaseImp' => $a['baseImp'],
                    'Importe' => $a['importe'],
                ], $p['alicuotasIva']),
            ];
        }

        $resultado = $this->llamar('FECAESolicitar', [
            'Auth' => $auth,
            'FeCAEReq' => [
                'FeCabReq' => [
                    'CantReg' => 1,
                    'PtoVta' => (int) $p['puntoVenta'],
                    'CbteTipo' => (int) $p['tipoComprobante'],
                ],
                'FeDetReq' => [
                    'FECAEDetRequest' => [$detalle],
                ],
            ],
        ]);

        return $this->normalizar($resultado['FECAESolicitarResult'] ?? [], $numero);
    }

    /**
     * Último comprobante autorizado + 1.
     */
    private function proximoNumero(array $auth, int $puntoVenta, int $tipo): int
    {
        $resultado = $this->llamar('FECompUltimoAutorizado', [
            'Auth' => $auth,
            'PtoVta' => $puntoVenta,
            'CbteTipo' => $tipo,
        ]);

        $respuesta = $resultado['FECompUltimoAutorizadoResult'] ?? [];

        $errores = $this->lista($respuesta['Errors']['Err'] ?? null);

        if ($errores) {
            throw new RuntimeException(
                'Error consultando último comprobante: '
                . implode(', ', array_column($errores, 'Msg'))
            );
        }

        return ((int) ($respuesta['CbteNro'] ?? 0)) + 1;
    }

    /**
     * Llama a un método SOAP y devuelve la respuesta como array.
     */
    private function llamar(string $metodo, array $parametros): array
    {
        try {
            $respuesta = $this->client()->{$metodo}($parametros);

        } catch (SoapFault $e) {

            $mensaje = $e->getMessage();

            // Problemas de red/timeout: se marcan como transitorios.
            if (
                $e->faultcode === 'HTTP'
                || stripos($mensaje, 'Could not connect') !== false
                || stripos($mensaje, 'Error Fetching http') !== false
                || stripos($mensaje, 'timed out') !== false
            ) {
                throw new RuntimeException('Error de conexión con WSFE: ' . $mensaje);
            }

            throw new RuntimeException('Error de WSFE: ' . $mensaje);
        }

        // stdClass -> array
        return json_decode(json_encode($respuesta), true) ?? [];
    }

    private function client(): SoapClient
    {
        if ($this->client) {
            return $this->client;
        }

        $wsdl = config('arca.wsfe_wsdl.' . config('arca.ambiente'));

        if (! $wsdl) {
            throw new RuntimeException(
                'No está configurado el WSDL de WSFE para el ambiente de ARCA.'
            );
        }

        $contexto = stream_context_create([
            'http' => ['timeout' => 30],
            /*
             * El servidor de homologación de ARCA usa claves DH débiles:
             * con OpenSSL 3 sin esto puede fallar con "dh key too small".
             */
            'ssl' => ['ciphers' => 'DEFAULT@SECLEVEL=1'],
        ]);

        try {
            return $this->client = new SoapClient($wsdl, [
                'exceptions' => true,
                'connection_timeout' => 15,
                'stream_context' => $contexto,
                'cache_wsdl' => WSDL_CACHE_MEMORY,
            ]);
        } catch (SoapFault $e) {
            throw new RuntimeException(
                'Error de conexión con WSFE: ' . $e->getMessage()
            );
        }
    }

    /**
     * Mismo criterio que usaba la API Node para decidir si se aprobó.
     */
    private function normalizar(array $respuesta, int $numero): array
    {
        $detalle = $this->lista($respuesta['FeDetResp']['FECAEDetResponse'] ?? null)[0] ?? null;

        $erroresGenerales = array_map(fn ($e) => [
            'codigo' => (string) ($e['Code'] ?? ''),
            'mensaje' => (string) ($e['Msg'] ?? ''),
        ], $this->lista($respuesta['Errors']['Err'] ?? null));

        $observaciones = array_map(fn ($o) => [
            'codigo' => (string) ($o['Code'] ?? ''),
            'mensaje' => (string) ($o['Msg'] ?? ''),
        ], $this->lista($detalle['Observaciones']['Obs'] ?? null));

        $aprobado =
            ($respuesta['FeCabResp']['Resultado'] ?? null) === 'A'
            && ($detalle['Resultado'] ?? null) === 'A'
            && ! $erroresGenerales;

        if (! $aprobado) {
            $errores = $erroresGenerales
                ?: $observaciones
                ?: [['codigo' => 'RECHAZADO', 'mensaje' => 'ARCA rechazó el comprobante']];

            return [
                'exito' => false,
                'numeroComprobante' => $numero,
                'errores' => $errores,
            ];
        }

        return [
            'exito' => true,
            'cae' => (string) $detalle['CAE'],
            'caeVencimiento' => (string) ($detalle['CAEFchVto'] ?? ''),
            'numeroComprobante' => $numero,
        ];
    }

    /**
     * SOAP devuelve un objeto si hay un elemento y una lista si hay varios.
     */
    private function lista(mixed $item): array
    {
        if (empty($item)) {
            return [];
        }

        return array_is_list($item) ? $item : [$item];
    }
}