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