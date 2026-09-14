<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ambiente
    |--------------------------------------------------------------------------
    | 'homologacion' para pruebas, 'produccion' para uso real.
    | Se define en el .env — ver más abajo.
    */
    'ambiente' => env('ARCA_AMBIENTE', 'homologacion'),

    'wsaa_wsdl' => [
        'homologacion' => 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms?wsdl',
        'produccion' => 'https://wsaa.afip.gov.ar/ws/services/LoginCms?wsdl',
    ],

    // El "servicio" que se le pide al WSAA — para facturación
    // electrónica siempre es 'wsfe', sin importar el ambiente.
    'servicio' => 'wsfe',

    // URL de la API ARCA (Node) que ya armamos y desplegamos.
    'api_url' => env('ARCA_API_URL'),
    'api_key' => env('ARCA_API_KEY'),
];