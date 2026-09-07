<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Genera datos de prueba: 10 servicios, 200 clientes
     * y 2 servicios aleatorios asignados a cada cliente.
     *
     * Solo corre si todavía no hay clientes cargados, para
     * evitar duplicar todo en cada deploy/redeploy.
     */
    public function run(): void
    {
        if (Client::count() > 0) {
            return;
        }

        // ==================================================
        // Servicios
        // ==================================================

        $periods = [1, 3, 6, 12]; // mensual, trimestral, semestral, anual

        $services = collect(range(1, 10))->map(function ($i) use ($periods) {
            $price = fake()->randomFloat(2, 1000, 20000);

            return Service::create([
                'service' => 'Servicio ' . $i . ' - ' . fake()->words(2, true),
                'price' => $price,
                'due_day' => fake()->numberBetween(1, 28),
                'overdue_price' => round($price * 1.1, 2),
                'period' => fake()->randomElement($periods),
            ]);
        });


        // ==================================================
        // Clientes + asignación de 2 servicios random c/u
        // ==================================================

        for ($i = 1; $i <= 200; $i++) {
            $client = Client::create([
                'name' => fake()->name(),
                'document' => fake()->unique()->numberBetween(10000000, 99999999),
            ]);

            $client->services()->attach(
                $services->random(2)->pluck('id')->toArray()
            );
        }
    }
}