<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    /**
     * Muestra todos los clientes (los bloqueados no aparecen).
     *
     * Orden: primero los que más deben; entre los que deben lo
     * mismo, por nombre.
     */
    public function index(Request $request)
    {
        /*
         * Cada vez que se entra a este apartado se eliminan los
         * clientes bloqueados hace 5 años o más.
         */
        try {
            Artisan::call('clients:purge-blocked');
        } catch (\Throwable $e) {
            Log::error('Error al purgar clientes bloqueados.', [
                'message' => $e->getMessage(),
            ]);
        }

        $query = Client::query();

        /*
         * Búsqueda por nombre o documento.
         */
        if ($request->filled('search')) {
            $search = addcslashes($request->input('search'), '%_\\');

            $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhereRaw(
                        'CAST(document AS TEXT) LIKE ?',
                        ["%{$search}%"]
                    );
            });
        }

        /*
         * Saldo por cobrar de cada cliente: suma de lo que
         * corresponde cobrar hoy de sus facturas pendientes
         * (misma regla que Invoice::montoACobrar()).
         */
        $saldos = Invoice::query()
            ->where('payment_status', '!=', 'paid')
            ->get()
            ->groupBy('client_document')
            ->map(fn ($facturas) => round(
                $facturas->sum(fn ($f) => $f->montoACobrar()),
                2
            ));

        $ordenados = $query
            ->get()
            ->each(function ($client) use ($saldos) {
                $client->saldo_por_cobrar = (float) (
                    $saldos[$client->document] ?? 0
                );
            })
            ->sort(function ($a, $b) {
                // 1) El que más debe primero.
                $porSaldo = round($b->saldo_por_cobrar * 100)
                    <=> round($a->saldo_por_cobrar * 100);

                if ($porSaldo !== 0) {
                    return $porSaldo;
                }

                // 2) Mismo saldo: por nombre.
                return strcmp(
                    Str::lower(Str::ascii($a->name)),
                    Str::lower(Str::ascii($b->name))
                );
            })
            ->values();

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $clients = new LengthAwarePaginator(
            $ordenados->forPage($page, $perPage)->values(),
            $ordenados->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

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

            'phone' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
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

            'phone' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $client->update($validated);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Bloquea el cliente (NO lo elimina).
     *
     * Por motivos legales los datos se conservan 5 años: el cliente
     * queda oculto en la aplicación y solo se puede ver desde la base
     * de datos. Al cumplirse los 5 años lo borra el comando
     * clients:purge-blocked (cliente + servicios asignados).
     *
     * Las facturas nunca se tocan.
     */
    public function destroy(Client $client)
    {
        $client->update([
            'blocked' => true,
            'blocked_at' => now(),
        ]);

        return redirect()
            ->route('admin.clients.index')
            ->with('blocked_client', [
                'name' => $client->name,
                'delete_on' => now()->addYears(5)->format('d/m/Y'),
            ]);
    }
}