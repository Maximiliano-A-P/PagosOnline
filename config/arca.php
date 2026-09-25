<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ambiente
    |--------------------------------------------------------------------------
    |
    | 'homologacion' para pruebas.
    | 'produccion' para uso real.
    |
    */

    'ambiente' => env(
        'ARCA_AMBIENTE',
        'homologacion'
    ),

    /*
    |--------------------------------------------------------------------------
    | WSAA
    |--------------------------------------------------------------------------
    */

    'wsaa_wsdl' => [
        'homologacion' =>
            'https://wsaahomo.afip.gov.ar/ws/services/LoginCms?wsdl',

        'produccion' =>
            'https://wsaa.afip.gov.ar/ws/services/LoginCms?wsdl',
    ],

    /*
     * Servicio solicitado al WSAA.
     */
    'servicio' => 'wsfe',

    /*
    |--------------------------------------------------------------------------
    | API intermedia ARCA
    |--------------------------------------------------------------------------
    */

    'api_url' =>
        env('ARCA_API_URL'),

    'api_key' =>
        env('ARCA_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Certificado
    |--------------------------------------------------------------------------
    */

    'certificado_crt' =>
        env('ARCA_CERTIFICATE_CRT'),

    'private_key' =>
        env('ARCA_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Reintentos automáticos
    |--------------------------------------------------------------------------
    */

    'retry' => [

        /*
         * Primer intervalo de espera.
         *
         * 10 minutos.
         */
        'delay_minutes' =>
            (int) env(
                'ARCA_RETRY_DELAY_MINUTES',
                10
            ),

        /*
         * Cantidad máxima de facturas que procesa
         * una ejecución automática.
         *
         * 1 = una solicitud cada ejecución.
         */
        'max_per_run' =>
            (int) env(
                'ARCA_RETRY_MAX_PER_RUN',
                1
            ),

        /*
         * Cantidad máxima de intentos automáticos
         * para una factura.
         */
        'max_attempts' =>
            (int) env(
                'ARCA_RETRY_MAX_ATTEMPTS',
                8
            ),

        /*
         * Límite superior del backoff.
         */
        'max_backoff_minutes' =>
            (int) env(
                'ARCA_RETRY_MAX_BACKOFF_MINUTES',
                120
            ),
    ],
];