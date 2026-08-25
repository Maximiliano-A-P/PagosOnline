<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Muestra todos los servicios.
     */
    public function index()
    {
        $services = Service::orderBy('id', 'desc')->get();

        return view('admin.services.index', compact('services'));
    }

    /**
     * Muestra el formulario para crear un servicio.
     */
    public function create()
    {
        return view('admin.services.create');
    }

    /**
     * Guarda un nuevo servicio.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service' => [
                'required',
                'string',
                'max:255',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            /*
             * Día del mes en el que vence el servicio.
             *
             * Ejemplos:
             * 1  = día 1
             * 10 = día 10
             * 31 = día 31
             */
            'due_day' => [
                'required',
                'integer',
                'min:1',
                'max:31',
            ],

            'overdue_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            /*
             * Cantidad de meses entre facturaciones.
             *
             * 1  = mensual
             * 3  = trimestral
             * 6  = semestral
             * 12 = anual
             */
            'period' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        Service::create($validated);

        return redirect()
            ->route('admin.services.index')
            ->with(
                'success',
                'Servicio creado correctamente.'
            );
    }

    /**
     * Muestra el formulario para editar un servicio.
     */
    public function edit(Service $service)
    {
        return view(
            'admin.services.edit',
            compact('service')
        );
    }

    /**
     * Actualiza un servicio existente.
     */
    public function update(
        Request $request,
        Service $service
    ) {
        $validated = $request->validate([
            'service' => [
                'required',
                'string',
                'max:255',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            /*
             * Día del mes en el que vence el servicio.
             */
            'due_day' => [
                'required',
                'integer',
                'min:1',
                'max:31',
            ],

            'overdue_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'period' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $service->update($validated);

        return redirect()
            ->route('admin.services.index')
            ->with(
                'success',
                'Servicio actualizado correctamente.'
            );
    }

    /**
     * Elimina un servicio.
     */
    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()
            ->route('admin.services.index')
            ->with(
                'success',
                'Servicio eliminado correctamente.'
            );
    }
}