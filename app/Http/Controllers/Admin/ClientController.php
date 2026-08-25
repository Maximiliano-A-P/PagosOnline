<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Muestra todos los clientes.
     */
    public function index(Request $request)
    {
        $query = Client::query();

        /*
         * Búsqueda por nombre o documento.
         */
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere(
                        'document',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
         * Ordenamos los clientes más recientes primero.
         */
        $clients = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.clients.index', compact('clients'));
    }

    /**
     * Muestra el formulario para crear un cliente.
     */
    public function create()
    {
        return view('admin.clients.create');
    }

    /**
     * Guarda un nuevo cliente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'document' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        Client::create($validated);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    /**
     * Muestra el formulario para editar un cliente.
     */
    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    /**
     * Actualiza los datos del cliente.
     */
    public function update(
        Request $request,
        Client $client
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'document' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $client->update($validated);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Elimina el cliente.
     *
     * Las facturas NO se eliminan porque contienen
     * sus propios datos históricos del cliente.
     */
    public function destroy(Client $client)
    {
        /*
         * Eliminamos primero las relaciones con servicios.
         */
        $client->services()->detach();

        /*
         * Eliminamos el cliente.
         *
         * Las invoices permanecen intactas.
         */
        $client->delete();

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}