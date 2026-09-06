<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Obtenemos los clientes asociados mediante la relación Eloquent
        $clients = $user->clients;
        $clientDocuments = $clients->pluck('document');

        // Buscamos facturas pendientes que coincidan con los documentos del usuario
        $pendingInvoices = $clientDocuments->isEmpty()
            ? collect()
            : Invoice::whereIn('client_document', $clientDocuments)
                ->where('payment_status', 'pending') // Ajusta según tu columna de estado de pago
                ->orderBy('issued_at', 'desc')
                ->get();

        return view('dashboard', [
            'documents' => $clientDocuments,
            'pendingInvoices' => $pendingInvoices,
        ]);
    }

    public function storeDocument(Request $request)
    {
        $request->validate([
            'document' => ['required', 'string'],
        ]);

        $user = Auth::user();

        // Buscamos si existe un cliente registrado con ese documento en el sistema
        $client = Client::where('document', $request->document)->first();

        if (!$client) {
            return back()->withErrors(['document' => 'El número de documento no está registrado en el sistema.']);
        }

        // Verificamos si ya está vinculado al usuario
        if ($user->clients()->where('client_id', $client->id)->exists()) {
            return back()->withErrors(['document' => 'Ya has agregado este documento anteriormente.']);
        }

        // Vinculamos el cliente al usuario mediante la tabla pivot
        $user->clients()->attach($client->id);

        return redirect()->route('dashboard');
    }

    public function destroyDocument($document)
    {
        $user = Auth::user();
        
        // Buscamos el cliente por su documento
        $client = Client::where('document', $document)->first();

        if ($client) {
            // Desvinculamos la relación
            $user->clients()->detach($client->id);
        }

        return redirect()->route('dashboard');
    }
}