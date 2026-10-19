<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        /*
         * Si la cuenta todavía no verificó el correo, se la manda
         * a la pantalla donde se introduce el código de 6 dígitos.
         */
        if ($user->email_verified_at === null) {
            return redirect()
                ->route('email.verification.show')
                ->with(
                    'success',
                    'Verificá tu correo con el código de 6 dígitos que te enviamos.'
                );
        }

        /*
         * Documentos que el usuario agregó. Se leen directamente de
         * su lista (no de la tabla clients), así las facturas siguen
         * visibles aunque el cliente se bloquee o se elimine.
         */
        $documents = $user->documents()
            ->orderBy('id')
            ->pluck('document');

        // Facturas pendientes de pago de esos documentos.
        $pendingInvoices = $documents->isEmpty()
            ? collect()
            : Invoice::whereIn('client_document', $documents)
                ->where('payment_status', 'pending')
                ->orderBy('issued_at', 'desc')
                ->get();

        return view('dashboard', [
            'documents' => $documents->all(),
            'pendingInvoices' => $pendingInvoices,
        ]);
    }

    public function storeDocument(Request $request)
    {
        /*
         * Se aceptan solo los dígitos ("12.345.678" pasa a 12345678),
         * porque el documento es numérico en la base de datos.
         */
        $request->merge([
            'document' => preg_replace('/\D+/', '', (string) $request->input('document')),
        ]);

        $request->validate([
            'document' => ['required', 'integer', 'min:1'],
        ], [
            'document.required' => 'Ingresá un número de documento.',
            'document.integer' => 'El documento debe ser un número.',
            'document.min' => 'El número de documento no es válido.',
        ]);

        $user = Auth::user();

        /*
         * El documento tiene que estar registrado (y no bloqueado)
         * en el sistema al momento de agregarlo. El global scope de
         * Client ya excluye a los bloqueados.
         */
        $client = Client::where('document', $request->input('document'))->first();

        if (!$client) {
            return back()->withErrors(['document' => 'El número de documento no está registrado en el sistema.']);
        }

        // Verificamos si el usuario ya lo agregó.
        $alreadyAdded = $user->documents()
            ->where('document', $client->document)
            ->exists();

        if ($alreadyAdded) {
            return back()->withErrors(['document' => 'Ya has agregado este documento anteriormente.']);
        }

        /*
         * Se guarda el número de documento. La restricción única
         * (user_id + document) cubre el caso de dos envíos simultáneos.
         */
        try {
            $user->documents()->create([
                'document' => $client->document,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return back()->withErrors(['document' => 'Ya has agregado este documento anteriormente.']);
        }

        return redirect()->route('dashboard');
    }

    public function destroyDocument($document)
    {
        $user = Auth::user();

        /*
         * Se quita el documento de la lista del usuario sin consultar
         * a Client: debe poder quitarse aunque el cliente ya no exista
         * o esté bloqueado.
         */
        if (ctype_digit((string) $document)) {
            $user->documents()
                ->where('document', $document)
                ->delete();
        }

        return redirect()->route('dashboard');
    }
}