<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

            'cuit' => [
                'nullable',
                'string',
                'regex:/^\d{11}$/',
            ],

            'arca_iva_condition' => [
                'nullable',
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
     * Muestra el detalle de un cliente y sus facturas.
     * Primero las pendientes (por vencimiento más próximo)
     * y después las pagadas (más recientes primero).
     */
    public function show(Request $request, Client $client)
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $todas = Invoice::where('client_document', $client->document)->get();

        $pendientes = $todas
            ->where('payment_status', '!=', 'paid')
            ->sortBy(fn ($invoice) => $invoice->due_date->timestamp);

        $pagadas = $todas
            ->where('payment_status', 'paid')
            ->sortByDesc(fn ($invoice) => $invoice->issued_at->timestamp);

        $invoices = $pendientes->concat($pagadas)->values();

        /*
         * Saldos a fecha (estado de cuenta estilo asiento contable).
         * "hasta" vacío se interpreta como "hasta hoy".
         */
        $desde = $request->filled('desde')
            ? Carbon::parse($request->input('desde'))->startOfDay()
            : null;

        $hasta = $request->filled('hasta')
            ? Carbon::parse($request->input('hasta'))->startOfDay()
            : null;

        $cuenta = $this->estadoDeCuenta($todas, $desde, $hasta);

        return view(
            'admin.clients.show',
            array_merge(
                compact('client', 'invoices', 'desde', 'hasta'),
                $cuenta
            )
        );
    }

    /**
     * Arma el estado de cuenta del cliente.
     *
     * Debe:  cada factura en su fecha de emisión (precio + impuestos)
     *        y, si se pagó con precio vencido, el recargo por mora
     *        en la fecha de pago.
     * Haber: cada pago (amount_paid) en su fecha de pago.
     * Saldo: debe - haber acumulado (positivo = el cliente debe).
     */
    private function estadoDeCuenta(
        Collection $facturas,
        ?Carbon $desde,
        ?Carbon $hasta
    ): array {
        $movimientos = collect();

        foreach ($facturas as $invoice) {
            $tax = (float) ($invoice->tax_percentage ?? 0);

            $totalNormal = round(
                (float) $invoice->price * (1 + ($tax / 100)),
                2
            );

            $fechaEmision = $invoice->issued_at->copy()->startOfDay();

            $movimientos->push([
                'fecha'   => $fechaEmision,
                'orden'   => 0,
                'id'      => $invoice->id,
                'detalle' => "Factura #{$invoice->id} — {$invoice->service_name}",
                'debe'    => $totalNormal,
                'haber'   => 0.0,
            ]);

            if ($invoice->payment_status !== 'paid') {
                continue;
            }

            $fechaPago = ($invoice->paid_at ?? $invoice->issued_at)
                ->copy()
                ->startOfDay();

            $pagado  = round((float) $invoice->amount_paid, 2);
            $recargo = round($pagado - $totalNormal, 2);

            if ($recargo > 0) {
                $movimientos->push([
                    'fecha'   => $fechaPago,
                    'orden'   => 0,
                    'id'      => $invoice->id,
                    'detalle' => "Recargo por mora — Factura #{$invoice->id}",
                    'debe'    => $recargo,
                    'haber'   => 0.0,
                ]);
            }

            $movimientos->push([
                'fecha'   => $fechaPago,
                'orden'   => 1,
                'id'      => $invoice->id,
                'detalle' => "Pago factura #{$invoice->id}",
                'debe'    => 0.0,
                'haber'   => $pagado,
            ]);
        }

        // Orden cronológico; en la misma fecha primero el debe y luego el haber.
        $movimientos = $movimientos
            ->sortBy([
                fn ($a, $b) => $a['fecha']->timestamp <=> $b['fecha']->timestamp,
                fn ($a, $b) => $a['orden'] <=> $b['orden'],
                fn ($a, $b) => $a['id'] <=> $b['id'],
            ])
            ->values();

        $hoy           = now()->startOfDay();
        $hastaEfectivo = $hasta ?? $hoy;

        // Saldo anterior al "desde".
        $saldoAnterior = $desde
            ? round(
                $movimientos
                    ->filter(fn ($m) => $m['fecha']->lt($desde))
                    ->sum(fn ($m) => $m['debe'] - $m['haber']),
                2
            )
            : 0.0;

        // Movimientos dentro del rango, con saldo acumulado.
        $saldo = $saldoAnterior;

        $asientos = $movimientos
            ->filter(fn ($m) =>
                (! $desde || $m['fecha']->gte($desde))
                && $m['fecha']->lte($hastaEfectivo)
            )
            ->map(function ($m) use (&$saldo) {
                $saldo = round($saldo + $m['debe'] - $m['haber'], 2);
                $m['saldo'] = $saldo;

                return $m;
            })
            ->values();

        // Saldo al día de hoy (todos los movimientos hasta hoy).
        $saldoHoy = round(
            $movimientos
                ->filter(fn ($m) => $m['fecha']->lte($hoy))
                ->sum(fn ($m) => $m['debe'] - $m['haber']),
            2
        );

        return [
            'hastaEfectivo' => $hastaEfectivo,
            'saldoAnterior' => $saldoAnterior,
            'asientos'      => $asientos,
            'totalDebe'     => round($asientos->sum('debe'), 2),
            'totalHaber'    => round($asientos->sum('haber'), 2),
            'saldoFinal'    => $saldo,
            'saldoHoy'      => $saldoHoy,
            'hoy'           => $hoy,
        ];
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

            'cuit' => [
                'nullable',
                'string',
                'regex:/^\d{11}$/',
            ],

            'arca_iva_condition' => [
                'nullable',
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