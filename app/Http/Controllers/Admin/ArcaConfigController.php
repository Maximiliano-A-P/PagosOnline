<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArcaConfig;
use Illuminate\Http\Request;

class ArcaConfigController extends Controller
{
    public function edit()
    {
        $config = ArcaConfig::first();

        return view('admin.arca.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'condicion_iva' => [
                'required',
                'integer',
                'min:1',
            ],

            /*
             * Tipo de Factura A autorizado para el emisor:
             * 1  = Factura A.
             * 51 = Factura A con leyenda de retención.
             *
             * El usuario final de la aplicación no elige este código.
             * Lo configura el administrador según la autorización
             * correspondiente al CUIT emisor.
             */
            'tipo_factura_a' => [
                'required',
                'integer',
                'in:1,51',
            ],

            'punto_venta' => [
                'required',
                'integer',
                'min:1',
                'max:99998',
            ],

            'punto_venta_exportacion' => [
                'nullable',
                'integer',
                'min:1',
                'max:99998',
            ],
        ]);

        $config = ArcaConfig::first();

        if ($config) {
            $config->update($validated);
        } else {
            ArcaConfig::create($validated);
        }

        return redirect()
            ->route('admin.arca.edit')
            ->with(
                'success',
                'Configuración de ARCA guardada correctamente.'
            );
    }
}