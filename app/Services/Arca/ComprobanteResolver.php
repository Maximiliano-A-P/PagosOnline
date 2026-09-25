<?php

namespace App\Services\Arca;

class ComprobanteResolver
{
    // Códigos AFIP de condición frente al IVA (los más comunes)
    private const RESPONSABLE_INSCRIPTO = 1;
    private const CONSUMIDOR_FINAL = 5;
    private const MONOTRIBUTISTA = 6;

    // Código AFIP de tipo de documento para Consumidor Final
    private const DOC_TIPO_CONSUMIDOR_FINAL = 99;

    /**
     * Determina el tipo de comprobante (código AFIP) a emitir,
     * cruzando la condición del emisor con la del cliente.
     *
     * Si el cliente no tiene condición cargada (null), se lo
     * trata como Consumidor Final — nunca se exige CUIT para
     * poder facturar.
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

        // Emisor Monotributista (u otra condición que no discrimina IVA)
        return 11; // Factura C
    }

    /**
     * Determina qué docTipo/docNro mandarle a la API ARCA,
     * respetando que un cliente sin CUIT cargado se factura
     * igual como Consumidor Final.
     */
    public static function resolverDocumento(
        ?int $tipoDocumentoCliente,
        ?string $documentoCliente
    ): array {
        if ($tipoDocumentoCliente === null || $documentoCliente === null) {
            return [
                'docTipo' => self::DOC_TIPO_CONSUMIDOR_FINAL,
                'docNro' => '0',
            ];
        }

        return [
            'docTipo' => $tipoDocumentoCliente,
            'docNro' => $documentoCliente,
        ];
    }

    /**
     * Valida que, si corresponde Factura A, el cliente
     * efectivamente tenga CUIT cargado. Usar antes de intentar
     * emitir, para frenar con un mensaje claro en vez de que
     * falle recién del lado de ARCA.
     */
    public static function requiereCuit(int $tipoComprobante): bool
    {
        return in_array($tipoComprobante, [1, 2, 3]); // Factura/NC/ND tipo A
    }

    /**
     * Indica si el comprobante debe discriminar IVA en el detalle
     * (alicuotasIva). Solo aplica a comprobantes tipo A y M —
     * la B y la C nunca discriminan.
     */
    public static function discriminaIva(int $tipoComprobante): bool
    {
        return in_array($tipoComprobante, [1, 2, 3, 51, 52, 53]); // A y M
    }
}