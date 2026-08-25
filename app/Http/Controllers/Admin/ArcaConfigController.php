<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArcaConfig;
use Illuminate\Http\Request;

class ArcaConfigController extends Controller
{
    /**
     * Muestra la configuración de ARCA.
     */
    public function edit()
    {
        $config = ArcaConfig::first();

        return view(
            'admin.arca.edit',
            compact('config')
        );
    }

    /**
     * Guarda o actualiza la configuración de ARCA.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'cuit' => [
                'required',
                'string',
                'max:20',
            ],

            'certificate_path' => [
                'nullable',
                'string',
                'max:500',
            ],

            'private_key_path' => [
                'nullable',
                'string',
                'max:500',
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