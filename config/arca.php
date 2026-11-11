<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ambiente ARCA
    |--------------------------------------------------------------------------
    */

    'ambiente' => env('ARCA_AMBIENTE', 'homologacion'),

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
    |--------------------------------------------------------------------------
    | API intermedia ARCA
    |--------------------------------------------------------------------------
    */

    'api_url' => env('ARCA_API_URL'),
    'api_key' => env('ARCA_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Emisor
    |--------------------------------------------------------------------------
    */

    'cuit' => env('ARCA_CUIT'),

    /*
    |--------------------------------------------------------------------------
    | Certificado y clave privada
    |--------------------------------------------------------------------------
    |
    | Se conservan como Base64 del contenido PEM, tal como los
    | procesa WsaaClient.
    |--------------------------------------------------------------------------
    */

    'certificado_crt' => env('ARCA_CERTIFICATE_CRT'),
    'private_key' => env('ARCA_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Reintentos automáticos
    |--------------------------------------------------------------------------
    */

    'retry' => [
        'delay_minutes' => (int) env(
            'ARCA_RETRY_DELAY_MINUTES',
            10
        ),

        'max_per_run' => (int) env(
            'ARCA_RETRY_MAX_PER_RUN',
            1
        ),

        'max_attempts' => (int) env(
            'ARCA_RETRY_MAX_ATTEMPTS',
            8
        ),

        'max_backoff_minutes' => (int) env(
            'ARCA_RETRY_MAX_BACKOFF_MINUTES',
            120
        ),
    ],
];