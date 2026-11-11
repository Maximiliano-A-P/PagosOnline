<?php

namespace App\Services\Arca;

class ComprobanteResolver
{
    private const RESPONSABLE_INSCRIPTO = 1;
    private const CONSUMIDOR_FINAL = 5;
    private const CLIENTE_EXTERIOR = 9;

    private const DOC_TIPO_CUIT = 80;
    private const DOC_TIPO_DNI = 96;
    private const DOC_TIPO_CONSUMIDOR_FINAL = 99;

    /**
     * Determina el tipo de comprobante según la condición fiscal.
     *
     * $tipoFacturaA debe coincidir con el tipo de Factura A
     * autorizado para el emisor en ARCA: 1 o 51.
     */
    public static function determinarTipoComprobante(
        int $condicionEmisor,
        ?int $condicionCliente,
        int $tipoFacturaA = 1
    ): int {
        $condicionCliente ??= self::CONSUMIDOR_FINAL;

        if ($condicionCliente === self::CLIENTE_EXTERIOR) {
            return 19;
        }

        if ($condicionEmisor === self::RESPONSABLE_INSCRIPTO) {
            if ($condicionCliente === self::RESPONSABLE_INSCRIPTO) {
                return in_array($tipoFacturaA, [1, 51], true)
                    ? $tipoFacturaA
                    : 1;
            }

            return 6;
        }

        return 11;
    }

    /**
     * Resuelve el documento del receptor para comprobantes nacionales.
     */
    public static function resolverDocumento(
        int $tipoComprobante,
        ?string $documentoDni,
        ?string $cuit
    ): array {
        $documentoDni = $documentoDni !== null
            ? preg_replace('/\D/', '', $documentoDni)
            : null;

        $cuit = $cuit !== null
            ? preg_replace('/\D/', '', $cuit)
            : null;

        if (self::esExportacion($tipoComprobante)) {
            return [
                'docTipo' => null,
                'docNro' => null,
            ];
        }

        if (self::requiereCuit($tipoComprobante)) {
            return [
                'docTipo' => self::DOC_TIPO_CUIT,
                'docNro' => $cuit ?? '',
            ];
        }

        if ($documentoDni !== null && $documentoDni !== '') {
            return [
                'docTipo' => self::DOC_TIPO_DNI,
                'docNro' => $documentoDni,
            ];
        }

        return [
            'docTipo' => self::DOC_TIPO_CONSUMIDOR_FINAL,
            'docNro' => '0',
        ];
    }

    public static function requiereCuit(int $tipoComprobante): bool
    {
        return in_array(
            $tipoComprobante,
            [1, 2, 3, 51, 52, 53],
            true
        );
    }

    public static function esAConLeyendaRetencion(
        int $tipoComprobante
    ): bool {
        return in_array($tipoComprobante, [51, 52, 53], true);
    }

    public static function esExportacion(int $tipoComprobante): bool
    {
        return in_array($tipoComprobante, [19, 20, 21], true);
    }

    public static function discriminaIva(int $tipoComprobante): bool
    {
        return in_array(
            $tipoComprobante,
            [1, 2, 3, 6, 7, 8, 51, 52, 53],
            true
        );
    }
}