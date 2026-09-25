<?php

namespace App\Services\Arca;

class ComprobanteResolver
{
    // Códigos AFIP de condición frente al IVA.
    private const RESPONSABLE_INSCRIPTO = 1;
    private const CONSUMIDOR_FINAL = 5;
    private const MONOTRIBUTISTA = 6;

    // Códigos AFIP de tipo de documento.
    private const DOC_TIPO_CUIT = 80;
    private const DOC_TIPO_DNI = 96;
    private const DOC_TIPO_CONSUMIDOR_FINAL = 99;

    /**
     * Determina el tipo de comprobante.
     */
    public static function determinarTipoComprobante(
        int $condicionEmisor,
        ?int $condicionCliente
    ): int {
        $condicionCliente ??= self::CONSUMIDOR_FINAL;

        if ($condicionEmisor === self::RESPONSABLE_INSCRIPTO) {
            return $condicionCliente === self::RESPONSABLE_INSCRIPTO
                ? 1   // Factura A
                : 6;  // Factura B
        }

        // Emisor Monotributista.
        return 11; // Factura C
    }

    /**
     * Determina el documento que se envía a ARCA.
     *
     * Factura A:
     *   CUIT -> DocTipo 80
     *
     * Factura B/C:
     *   DNI -> DocTipo 96
     *
     * La CUIT no reemplaza al DNI para una Factura C.
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

    /**
     * Determina qué comprobantes requieren CUIT.
     */
    public static function requiereCuit(int $tipoComprobante): bool
    {
        return in_array(
            $tipoComprobante,
            [1, 2, 3],
            true
        );
    }

    /**
     * Indica si el comprobante discrimina IVA.
     */
    public static function discriminaIva(int $tipoComprobante): bool
    {
        return in_array(
            $tipoComprobante,
            [1, 2, 3, 51, 52, 53],
            true
        );
    }
}