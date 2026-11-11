<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Service;
use App\Support\TextSearch;
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
        TextSearch::apply(
            $query,
            $request->input('search'),
            ['name'],
            ['document']
        );

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
     * Los servicios se buscan por autocompletado
     * (ver searchServices).
     */
    public function create(Client $client)
    {
        /*
         * Servicios que el cliente ya tiene asignados.
         */
        $client->load('services');

        return view(
            'admin.client-services.create',
            compact('client')
        );
    }

    /**
     * Sugerencias del buscador de servicios (JSON).
     *
     * Excluye los servicios que el cliente ya tiene asignados.
     */
    public function searchServices(
        Request $request,
        Client $client
    ) {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $query = Service::query()
            ->whereNotIn(
                'id',
                $client->services()->pluck('services.id')
            );

        TextSearch::apply($query, $q, ['service']);

        $services = $query
            ->orderBy('service')
            ->limit(8)
            ->get()
            ->map(fn ($service) => [
                'id'     => $service->id,
                'name'   => $service->service,
                'price'  => (float) $service->price,
                'period' => $service->period,
            ]);

        return response()->json($services);
    }

    /**
     * Asigna un servicio al cliente seleccionado.
     */
    public function store(
        Request $request,
        Client $client
    ) {
        $validated = $request->validate(
            [
                'service_id' => [
                    'required',
                    'integer',
                    'exists:services,id',
                ],
            ],
            [
                'service_id.required' =>
                    'Buscá y seleccioná un servicio de la lista.',
                'service_id.integer' =>
                    'Buscá y seleccioná un servicio de la lista.',
                'service_id.exists' =>
                    'El servicio seleccionado no existe.',
            ]
        );

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