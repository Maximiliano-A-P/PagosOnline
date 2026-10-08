<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Genera datos de prueba en español: 10 servicios, 200 clientes y 2 servicios aleatorios asignados a cada cliente.
     * Solo corre si todavía no hay clientes cargados, para evitar duplicar todo en cada deploy/redeploy.
     */
    public function run(): void
    {
        /*
         * Se consulta sin el global scope para contar también
         * los clientes bloqueados.
         */
        if (Client::withoutGlobalScopes()->count() > 0) {
            return;
        }

        // ==================================================
        // Servicios
        // ==================================================

        /*
         * [nombre, precio neto, % impuesto, día de vencimiento,
         *  período en meses]
         *
         * Períodos: 1 = mensual, 3 = trimestral, 6 = semestral,
         * 12 = anual.
         */
        $catalogo = [
            ['Internet 100 Mbps',          18500,  21.00, 10,  1],
            ['Internet 300 Mbps',          24900,  21.00, 10,  1],
            ['Televisión por cable',       14200,  21.00, 15,  1],
            ['Alarma monitoreada',         12800,  21.00,  5,  1],
            ['Mantenimiento de jardín',     9500,  10.50, 20,  1],
            ['Limpieza de oficinas',       32000,  21.00, 25,  1],
            ['Cuota de club social',        7800,   0.00, 12,  3],
            ['Expensas de cochera',        11500,   0.00,  8,  1],
            ['Seguro del hogar',           46000,  10.50, 18,  6],
            ['Abono anual de gimnasio',   120000,  21.00, 28, 12],
        ];

        $services = collect($catalogo)->map(function ($fila) {

            [$nombre, $precio, $impuesto, $diaVencimiento, $periodo] = $fila;

            return Service::create([
                'service'        => $nombre,
                'price'          => $precio,
                'tax_percentage' => $impuesto,
                'due_day'        => $diaVencimiento,
                'overdue_price'  => round($precio * 1.1, 2),
                'period'         => $periodo,
            ]);
        });


        // ==================================================
        // Datos para armar clientes en español
        // ==================================================

        $nombres = [
            'Juan', 'Carlos', 'Luis', 'Miguel', 'José', 'Martín', 'Diego',
            'Pablo', 'Sergio', 'Facundo', 'Matías', 'Nicolás', 'Lucas',
            'Gonzalo', 'Federico', 'Ramiro', 'Tomás', 'Agustín',
            'María', 'Laura', 'Ana', 'Lucía', 'Sofía', 'Valentina',
            'Camila', 'Florencia', 'Carolina', 'Daniela', 'Julieta',
            'Natalia', 'Romina', 'Paula', 'Mariana', 'Micaela', 'Gabriela',
        ];

        $apellidos = [
            'González', 'Rodríguez', 'Fernández', 'López', 'Martínez',
            'Pérez', 'Gómez', 'Sánchez', 'Díaz', 'Romero', 'Álvarez',
            'Torres', 'Ruiz', 'Ramírez', 'Flores', 'Acosta', 'Benítez',
            'Medina', 'Herrera', 'Suárez', 'Aguirre', 'Giménez', 'Gutiérrez',
            'Peralta', 'Rojas', 'Silva', 'Molina', 'Castro', 'Ortiz',
            'Vega', 'Ríos', 'Cabrera', 'Domínguez', 'Villalba', 'Sosa',
        ];

        $calles = [
            'San Martín', 'Belgrano', 'Urquiza', 'Rivadavia', 'Sarmiento',
            'Mitre', '25 de Mayo', '9 de Julio', 'Italia', 'Pellegrini',
            'Alberdi', 'Quintana', 'Perón', 'Jujuy', 'Entre Ríos',
            'Corrientes', 'Chacabuco',
        ];

        /*
         * Localidades de Entre Ríos con su característica telefónica,
         * así el teléfono coincide con la ciudad de la dirección.
         */
        $ciudades = [
            ['Gualeguaychú',           '3446'],
            ['Concepción del Uruguay', '3442'],
            ['Paraná',                 '343'],
            ['Concordia',              '345'],
            ['Colón',                  '3447'],
            ['Victoria',               '3436'],
        ];

        $dominios = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com.ar'];


        // ==================================================
        // Clientes + asignación de 2 servicios random c/u
        // ==================================================

        $documentosUsados = [];
        $emailsUsados = [];

        for ($i = 1; $i <= 200; $i++) {

            $nombre = Arr::random($nombres);
            $apellido = Arr::random($apellidos);

            // DNI único.
            do {
                $documento = random_int(10000000, 45999999);
            } while (isset($documentosUsados[$documento]));

            $documentosUsados[$documento] = true;

            /*
             * Condición frente al IVA:
             * 1 = Responsable Inscripto, 5 = Consumidor Final,
             * 6 = Monotributista.
             */
            $condicionIva = Arr::random([1, 5, 5, 5, 6, 6]);

            /*
             * Solo Responsable Inscripto y Monotributista tienen
             * CUIT. Se arma con un prefijo, el DNI y un dígito final,
             * para que sea coherente con el documento.
             */
            $cuit = null;

            if ($condicionIva !== 5) {
                $prefijo = Arr::random([20, 23, 24, 27]);
                $cuit = $prefijo
                    . str_pad((string) $documento, 8, '0', STR_PAD_LEFT)
                    . random_int(0, 9);
            }

            // Email derivado del nombre (sin tildes ni espacios).
            $base = Str::of($nombre . '.' . $apellido)
                ->ascii()
                ->lower()
                ->replace(' ', '')
                ->toString();

            do {
                $email = $base . random_int(1, 999) . '@' . Arr::random($dominios);
            } while (isset($emailsUsados[$email]));

            $emailsUsados[$email] = true;

            [$ciudad, $caracteristica] = Arr::random($ciudades);

            $telefono = '+54 9 ' . $caracteristica . ' '
                . random_int(40, 69) . '-' . random_int(1000, 9999);

            $direccion = Arr::random($calles) . ' '
                . random_int(100, 3500) . ', ' . $ciudad;

            /*
             * Los datos de contacto son opcionales: algunos
             * clientes no los tienen cargados.
             */
            $client = Client::create([
                'name'               => $nombre . ' ' . $apellido,
                'document'           => $documento,
                'cuit'               => $cuit,
                'arca_iva_condition' => $condicionIva,
                'phone'              => random_int(1, 100) <= 85 ? $telefono : null,
                'email'              => random_int(1, 100) <= 85 ? $email : null,
                'address'            => random_int(1, 100) <= 90 ? $direccion : null,
            ]);

            // Cada cliente recibe 2 servicios aleatorios.
            $client->services()->attach(
                $services
                    ->random(2)
                    ->pluck('id')
                    ->toArray()
            );
        }
    }
}