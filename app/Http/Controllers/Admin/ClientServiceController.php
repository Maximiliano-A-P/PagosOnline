<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Http\Request;

class ClientServiceController extends Controller
{
    /**
     * Muestra los servicios asignados a los clientes.
     *
     * Permite buscar clientes por nombre o documento.
     */
    public function index(Request $request)
    {
        $query = Client::with('services');

        /*
         * Permite buscar clientes por nombre o documento.
         */
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($query) use ($search) {
                $query->where(
                    'name',
                    'ilike',
                    "%{$search}%"
                )
                ->orWhere(
                    'document',
                    'like',
                    "%{$search}%"
                );
            });
        }

        $clients = $query
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.client-services.index',
            compact('clients')
        );
    }

    /**
     * Muestra el formulario para asignar un servicio
     * a un cliente específico.
     *
     * El cliente ya viene seleccionado desde el index.
     */
    public function create(Client $client)
    {
        /*
        * Servicios disponibles para agregar.
        */
        $services = Service::orderBy('service')->get();

        /*
        * Servicios que el cliente ya tiene asignados.
        */
        $client->load('services');

        return view(
            'admin.client-services.create',
            compact('client', 'services')
        );
    }

    /**
     * Asigna un servicio al cliente seleccionado.
     */
    public function store(
        Request $request,
        Client $client
    ) {
        $validated = $request->validate([
            'service_id' => [
                'required',
                'integer',
                'exists:services,id',
            ],
        ]);

        /*
         * No elimina otros servicios que el cliente
         * ya tenga asignados.
         */
        $client->services()->syncWithoutDetaching([
            $validated['service_id'],
        ]);

        return redirect()
            ->route('admin.client-services.index')
            ->with(
                'success',
                'Servicio asignado correctamente.'
            );
    }

    /**
     * Elimina la asignación entre un cliente y un servicio.
     */
    public function destroy(
        Request $request,
        Client $client
    ) {
        $request->validate([
            'service_id' => [
                'required',
                'integer',
                'exists:services,id',
            ],
        ]);

        /*
         * Quitamos solamente ese servicio del cliente.
         *
         * Esto NO elimina:
         * - al cliente
         * - el servicio
         * - ninguna factura existente
         */
        $client->services()->detach(
            $request->input('service_id')
        );

        return redirect()
            ->route('admin.client-services.index')
            ->with(
                'success',
                'Servicio quitado correctamente.'
            );
    }
}