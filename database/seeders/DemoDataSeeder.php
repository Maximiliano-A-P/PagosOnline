<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    /**
     * Crea los datos generales y los 20 clientes de prueba fiscal.
     *
     * En una base vacía crea 180 clientes generales más 20 clientes
     * fiscales especiales. Si ya hay clientes, solamente garantiza
     * que existan los 20 clientes especiales, sin duplicarlos.
     */
    public function run(): void
    {
        $catalogo = [
            ['Internet 100 Mbps',         18500,  21.00, 10,  1],
            ['Internet 300 Mbps',         24900,  21.00, 10,  1],
            ['Televisión por cable',      14200,  21.00, 15,  1],
            ['Alarma monitoreada',        12800,  21.00,  5,  1],
            ['Mantenimiento de jardín',    9500,  10.50, 20,  1],
            ['Limpieza de oficinas',      32000,  21.00, 25,  1],
            ['Cuota de club social',       7800,   0.00, 12,  3],
            ['Expensas de cochera',       11500,   0.00,  8,  1],
            ['Seguro del hogar',          46000,  10.50, 18,  6],
            ['Abono anual de gimnasio',  120000,  21.00, 28, 12],
        ];

        /*
         * Reutiliza los servicios existentes para evitar duplicarlos
         * cada vez que se ejecuta el seeder.
         */
        $services = collect($catalogo)->map(function ($fila) {
            [
                $nombre,
                $precio,
                $impuesto,
                $diaVencimiento,
                $periodo,
            ] = $fila;

            return Service::firstOrCreate(
                ['service' => $nombre],
                [
                    'price' => $precio,
                    'tax_percentage' => $impuesto,
                    'due_day' => $diaVencimiento,
                    'overdue_price' => round($precio * 1.1, 2),
                    'period' => $periodo,
                ]
            );
        })->values();

        /*
         * Los clientes generales se crean solo cuando la base
         * todavía no contiene clientes.
         */
        if (Client::withoutGlobalScopes()->count() === 0) {
            $this->crearClientesGenerales($services, 180);
        }

        /*
         * Estos 20 clientes se crean o actualizan incluso si ya
         * existen otros clientes en la base.
         */
        $this->crearClientesExportacion($services);
        $this->crearClientesFacturaAM($services);
    }

    /**
     * Crea los clientes generales de demostración.
     */
    private function crearClientesGenerales($services, int $cantidad): void
    {
        $nombres = [
            'Juan', 'Carlos', 'Luis', 'Miguel', 'José', 'Martín',
            'Diego', 'Pablo', 'Sergio', 'Facundo', 'Matías',
            'Nicolás', 'Lucas', 'Gonzalo', 'Federico', 'Ramiro',
            'Tomás', 'Agustín', 'María', 'Laura', 'Ana', 'Lucía',
            'Sofía', 'Valentina', 'Camila', 'Florencia', 'Carolina',
            'Daniela', 'Julieta', 'Natalia', 'Romina', 'Paula',
            'Mariana', 'Micaela', 'Gabriela',
        ];

        $apellidos = [
            'González', 'Rodríguez', 'Fernández', 'López',
            'Martínez', 'Pérez', 'Gómez', 'Sánchez', 'Díaz',
            'Romero', 'Álvarez', 'Torres', 'Ruiz', 'Ramírez',
            'Flores', 'Acosta', 'Benítez', 'Medina', 'Herrera',
            'Suárez', 'Aguirre', 'Giménez', 'Gutiérrez',
            'Pereira', 'Rojas', 'Silva', 'Molina', 'Castro',
            'Ortiz', 'Vega', 'Ríos', 'Cabrera', 'Domínguez',
            'Villalba', 'Sosa',
        ];

        $calles = [
            'San Martín', 'Belgrano', 'Urquiza', 'Rivadavia',
            'Sarmiento', 'Mitre', '25 de Mayo', '9 de Julio',
            'Italia', 'Pellegrini', 'Alberdi', 'Quintana',
            'Perón', 'Jujuy', 'Entre Ríos', 'Corrientes',
            'Chacabuco',
        ];

        $ciudades = [
            ['Gualeguaychú', '3446'],
            ['Concepción del Uruguay', '3442'],
            ['Paraná', '343'],
            ['Concordia', '345'],
            ['Colón', '3447'],
            ['Victoria', '3436'],
        ];

        $dominios = [
            'gmail.com',
            'hotmail.com',
            'outlook.com',
            'yahoo.com.ar',
        ];

        $documentosUsados = [];
        $emailsUsados = [];

        for ($i = 1; $i <= $cantidad; $i++) {
            $nombre = Arr::random($nombres);
            $apellido = Arr::random($apellidos);

            do {
                $documento = random_int(10000000, 45999999);
            } while (isset($documentosUsados[$documento]));

            $documentosUsados[$documento] = true;

            $condicionIva = Arr::random([1, 5, 5, 5, 6, 6]);
            $cuit = null;

            if ($condicionIva !== 5) {
                $prefijo = Arr::random([20, 23, 24, 27]);

                $cuit = $prefijo
                    . str_pad(
                        (string) $documento,
                        8,
                        '0',
                        STR_PAD_LEFT
                    )
                    . random_int(0, 9);
            }

            $baseEmail = Str::of($nombre . '.' . $apellido)
                ->ascii()
                ->lower()
                ->replace(' ', '')
                ->toString();

            do {
                $email = $baseEmail
                    . random_int(1, 999)
                    . '@'
                    . Arr::random($dominios);
            } while (isset($emailsUsados[$email]));

            $emailsUsados[$email] = true;

            [$ciudad, $caracteristica] = Arr::random($ciudades);

            $telefono = '+54 9 '
                . $caracteristica
                . ' '
                . random_int(40, 69)
                . '-'
                . random_int(1000, 9999);

            $direccion = Arr::random($calles)
                . ' '
                . random_int(100, 3500)
                . ', '
                . $ciudad;

            $client = Client::create([
                'name' => $nombre . ' ' . $apellido,
                'document' => $documento,
                'cuit' => $cuit,
                'arca_iva_condition' => $condicionIva,

                'arca_destination_country' => null,
                'arca_cuit_pais_cliente' => null,
                'arca_id_impositivo' => null,

                'phone' => random_int(1, 100) <= 85
                    ? $telefono
                    : null,

                'email' => random_int(1, 100) <= 85
                    ? $email
                    : null,

                'address' => random_int(1, 100) <= 90
                    ? $direccion
                    : null,
            ]);

            $this->asignarServicios(
                $client,
                $services,
                $i - 1
            );
        }
    }

    /**
     * Crea diez clientes preparados para emisión de Factura E.
     */
    private function crearClientesExportacion($services): void
    {
        /*
         * Códigos del catálogo de destinos de WSFEX.
         * No son códigos ISO de dos letras.
         */
        $destinos = [
            [
                'codigo' => 410,
                'pais' => 'España',
                'direccion' => 'Calle Mayor 101, Madrid, España',
                'identificador' => 'ES-TAX-TEST',
            ],
            [
                'codigo' => 212,
                'pais' => 'Estados Unidos',
                'direccion' => '100 Biscayne Blvd, Miami, Estados Unidos',
                'identificador' => 'US-TAX-TEST',
            ],
            [
                'codigo' => 203,
                'pais' => 'Brasil',
                'direccion' => 'Av. Paulista 1000, São Paulo, Brasil',
                'identificador' => 'BR-TAX-TEST',
            ],
            [
                'codigo' => 208,
                'pais' => 'Chile',
                'direccion' => 'Av. Providencia 100, Santiago, Chile',
                'identificador' => 'CL-TAX-TEST',
            ],
            [
                'codigo' => 204,
                'pais' => 'Canadá',
                'direccion' => '100 Queen Street, Toronto, Canadá',
                'identificador' => 'CA-TAX-TEST',
            ],
            [
                'codigo' => 406,
                'pais' => 'Bélgica',
                'direccion' => 'Rue de la Loi 10, Bruselas, Bélgica',
                'identificador' => 'BE-TAX-TEST',
            ],
            [
                'codigo' => 438,
                'pais' => 'Alemania',
                'direccion' => 'Unter den Linden 10, Berlín, Alemania',
                'identificador' => 'DE-TAX-TEST',
            ],
            [
                'codigo' => 412,
                'pais' => 'Francia',
                'direccion' => '10 Rue de Rivoli, París, Francia',
                'identificador' => 'FR-TAX-TEST',
            ],
            [
                'codigo' => 413,
                'pais' => 'Grecia',
                'direccion' => '10 Ermou, Atenas, Grecia',
                'identificador' => 'GR-TAX-TEST',
            ],
            [
                'codigo' => 405,
                'pais' => 'Austria',
                'direccion' => 'Kärntner Straße 10, Viena, Austria',
                'identificador' => 'AT-TAX-TEST',
            ],
        ];

        foreach ($destinos as $index => $destino) {
            $numero = $index + 1;
            $documento = 99000000 + $numero;

            $client = Client::withoutGlobalScopes()->updateOrCreate(
                ['document' => $documento],
                [
                    'name' => sprintf(
                        'Cliente de prueba Factura E %02d',
                        $numero
                    ),

                    'cuit' => null,
                    'arca_iva_condition' => 9,

                    'arca_destination_country' => $destino['codigo'],
                    'arca_cuit_pais_cliente' => null,

                    'arca_id_impositivo' =>
                        $destino['identificador']
                        . '-'
                        . sprintf('%02d', $numero),

                    'phone' => null,

                    'email' => sprintf(
                        'prueba.factura.e.%02d@example.test',
                        $numero
                    ),

                    'address' => $destino['direccion'],

                    'blocked' => false,
                    'blocked_at' => null,
                ]
            );

            $this->asignarServicios(
                $client,
                $services,
                $index
            );
        }
    }

    /**
     * Crea diez clientes responsables inscriptos con CUIT,
     * preparados para comprobantes nacionales de tipo A.
     */
    private function crearClientesFacturaAM($services): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $documento = 99000010 + $i;

            $client = Client::withoutGlobalScopes()->updateOrCreate(
                ['document' => $documento],
                [
                    'name' => sprintf(
                        'Cliente de prueba Factura A/M %02d',
                        $i
                    ),

                    'cuit' => $this->generarCuitDePrueba($documento),
                    'arca_iva_condition' => 1,

                    'arca_destination_country' => null,
                    'arca_cuit_pais_cliente' => null,
                    'arca_id_impositivo' => null,

                    'phone' => null,

                    'email' => sprintf(
                        'prueba.factura.am.%02d@example.test',
                        $i
                    ),

                    'address' => sprintf(
                        'Domicilio de prueba %d, Gualeguaychú, Entre Ríos',
                        $i
                    ),

                    'blocked' => false,
                    'blocked_at' => null,
                ]
            );

            $this->asignarServicios(
                $client,
                $services,
                $i + 9
            );
        }
    }

    /**
     * Genera un CUIT sintético con dígito verificador.
     *
     * Es un dato de prueba, no un CUIT real.
     */
    private function generarCuitDePrueba(int $numero): string
    {
        foreach (['20', '23', '27', '30'] as $prefijo) {
            $base = $prefijo . str_pad(
                (string) $numero,
                8,
                '0',
                STR_PAD_LEFT
            );

            $digito = $this->calcularDigitoVerificador($base);

            if ($digito >= 0 && $digito <= 9) {
                return $base . $digito;
            }
        }

        throw new RuntimeException(
            'No se pudo generar un CUIT sintético para los datos de prueba.'
        );
    }

    /**
     * Calcula el dígito verificador de un CUIT de diez dígitos.
     */
    private function calcularDigitoVerificador(string $base): int
    {
        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        foreach ($pesos as $index => $peso) {
            $suma += ((int) $base[$index]) * $peso;
        }

        $digito = 11 - ($suma % 11);

        if ($digito === 11) {
            return 0;
        }

        return $digito;
    }

    /**
     * Asigna dos servicios a cada cliente.
     */
    private function asignarServicios(
        Client $client,
        $services,
        int $offset
    ): void {
        $cantidad = $services->count();

        if ($cantidad < 2) {
            return;
        }

        $primero = $services[$offset % $cantidad]->id;
        $segundo = $services[($offset + 1) % $cantidad]->id;

        $client->services()->sync([
            $primero,
            $segundo,
        ]);
    }
}