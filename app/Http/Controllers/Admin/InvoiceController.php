<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ArcaConfig;
use App\Services\Arca\ArcaApiService;
use App\Services\Arca\WsaaClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * Muestra las facturas.
     *
     * Permite buscar por:
     * - Nombre del cliente
     * - Documento del cliente
     * - Nombre del servicio
     * - Estado de pago
     */
    public function index(Request $request)
    {
        $query = Invoice::query();

        /*
         * Búsqueda general.
         */
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($query) use ($search) {
                $query->where(
                    'client_name',
                    'ilike',
                    "%{$search}%"
                )
                ->orWhere(
                    'client_document',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'service_name',
                    'ilike',
                    "%{$search}%"
                );
            });
        }

        /*
         * Filtro por estado de pago.
         */
        if ($request->filled('payment_status')) {
            $query->where(
                'payment_status',
                $request->input('payment_status')
            );
        }

        /*
         * Filtro por rango de fecha de emisión (ambos límites opcionales).
         */
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        if ($request->filled('date_from')) {
            $query->whereDate('issued_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issued_at', '<=', $request->input('date_to'));
        }

        /*
         * Facturas modificadas más recientemente primero.
         * El ID se utiliza como segundo criterio para mantener un orden determinista cuando dos facturas tienen el mismo updated_at.
         */
        $invoices = $query
        ->orderByDesc('updated_at')
        ->orderByDesc('id')
        ->paginate(15)
        ->withQueryString();

        return view(
            'admin.invoices.index',
            compact('invoices')
        );
    }


    /**
     * Muestra una factura concreta.
     *
     * La vista es solamente informativa.
     */
    public function show(Invoice $invoice)
    {
        return view(
            'admin.invoices.show',
            compact('invoice')
        );
    }


    /**
     * Muestra el formulario para cargar manualmente
     * una factura histórica.
     *
     * Estas son facturas que ya existían antes de utilizar
     * el sistema.
     */
    public function create()
    {
        /*
         * Los servicios se muestran como opciones.
         *
         * El administrador puede:
         *
         * - seleccionar un servicio existente
         * - dejarlo vacío si la factura histórica
         *   no tiene un servicio asociado
         */
        $services = Service::orderBy('service')->get();

        return view(
            'admin.invoices.create',
            compact('services')
        );
    }


    /**
     * Guarda una factura histórica/manual.
     *
     * A diferencia de las facturas automáticas, esta factura
     * no tiene que estar asociada obligatoriamente a un servicio.
     *
     * service_id:
     * - puede ser NULL
     * - si el servicio todavía existe, puede guardarse su ID
     *
     * service_name:
     * - siempre se guarda como dato histórico.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            /*
             * Solo fecha.
             */
            'issued_at' => [
                'required',
                'date',
            ],

            /*
             * Datos históricos del cliente.
             */
            'client_name' => [
                'required',
                'string',
                'max:255',
            ],

            'client_document' => [
                'required',
                'integer',
                'min:1',
            ],

            'client_document_type' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'client_cuit' => [
                'nullable',
                'string',
                'regex:/^\d{11}$/',
            ],

            'client_iva_condition' => [
                'nullable',
                'integer',
                'min:1',
            ],

            /*
             * Opcional para facturas históricas.
             */
            'service_id' => [
                'nullable',
                'integer',
                'exists:services,id',
            ],

            /*
             * Nombre histórico del servicio.
             */
            'service_name' => [
                'required',
                'string',
                'max:255',
            ],

            /*
             * Datos económicos.
             */
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'due_date' => [
                'required',
                'date',
                'after_or_equal:issued_at',
            ],

            'overdue_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'tax_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            /*
             * Estado del pago.
             */
            'payment_status' => [
                'required',
                'in:pending,paid',
            ],

            'amount_paid' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
             * Solo fecha.
             */
            'paid_at' => [
                'nullable',
                'date',
            ],

            'payment_method' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        /*
         * paid_by solo se establece si la factura histórica
         * se carga directamente como pagada y existe una
         * fecha de pago.
         */
        $paidBy = null;

        if (
            $validated['payment_status'] === 'paid'
            && !empty($validated['paid_at'])
        ) {
            $paidBy = auth()->id();
        }

        /*
         * Los datos históricos se guardan directamente
         * en la factura.
         */
        Invoice::create([
            'issued_at' => $validated['issued_at'],

            /*
             * Datos históricos del cliente.
             */
            'client_name' => $validated['client_name'],
            'client_document' => $validated['client_document'],
            'client_cuit' => $validated['client_cuit'] ?? null,
            'client_document_type' => 96,
            'client_iva_condition' => $validated['client_iva_condition'] ?? null,

            /*
             * Servicio.
             *
             * Puede ser NULL para una factura histórica.
             */
            'service_id' => $validated['service_id'] ?? null,
            'service_name' => $validated['service_name'],

            /*
             * Datos económicos.
             */
            'price' => $validated['price'],
            'due_date' => $validated['due_date'],
            'overdue_price' => $validated['overdue_price'],
            'tax_percentage' => $validated['tax_percentage'] ?? null,

            /*
             * Pago.
             */
            'payment_status' => $validated['payment_status'],
            'amount_paid' => $validated['amount_paid'] ?? null,
            'paid_at' => $validated['paid_at'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'paid_by' => $paidBy,

            /*
             * ARCA.
             */
            'arca_status' => null,
            'arca_cae' => null,
            'arca_cae_expires_at' => null,
            'arca_invoice_type' => null,
            'arca_point_of_sale' => null,
            'arca_invoice_number' => null,
            'arca_qr' => null,
        ]);

        return redirect()
            ->route('admin.invoices.index')
            ->with(
                'success',
                'Factura histórica cargada correctamente.'
            );
    }


    /**
     * Condiciones frente al IVA (para mostrar en los buscadores).
     */
    private const CONDICIONES_IVA = [
        1  => 'IVA Responsable Inscripto',
        4  => 'IVA Sujeto Exento',
        5  => 'Consumidor Final',
        6  => 'Responsable Monotributo',
        7  => 'Sujeto No Categorizado',
        8  => 'Proveedor del Exterior',
        9  => 'Cliente del Exterior',
        10 => 'IVA Liberado – Ley N° 19.640',
        13 => 'Monotributista Social',
        15 => 'IVA No Alcanzado',
        16 => 'Monotributo Trabajador Independiente Promovido',
    ];


    /**
     * Calcula qué facturas periódicas corresponde generar hoy.
     *
     * Devuelve una colección de pares [client, service]. La usan
     * tanto la vista previa como la generación real, así lo que se
     * muestra en la tarjeta es exactamente lo que se genera.
     *
     * Los clientes bloqueados no aparecen (global scope de Client).
     */
    private function facturasPorGenerar(Carbon $generationDate)
    {
        $currentMonth = $generationDate
            ->copy()
            ->startOfMonth();

        $pendientes = collect();

        $clients = Client::with('services')->get();

        foreach ($clients as $client) {

            foreach ($client->services as $service) {

                /*
                 * Última factura de este cliente y de ESTE service_id.
                 *
                 * No usamos service_name para identificarlo,
                 * porque el nombre del servicio puede cambiar.
                 */
                $lastInvoice = Invoice::query()
                    ->where('client_document', $client->document)
                    ->where('service_id', $service->id)
                    ->latest('issued_at')
                    ->first();

                if (!$lastInvoice) {
                    // Nunca se facturó: corresponde la primera factura.
                    $shouldGenerate = true;
                } else {
                    $lastInvoiceMonth = Carbon::parse(
                        $lastInvoice->issued_at
                    )->startOfMonth();

                    $monthsElapsed = $lastInvoiceMonth->diffInMonths(
                        $currentMonth
                    );

                    // Corresponde cuando pasó el período completo.
                    $shouldGenerate = (
                        $monthsElapsed >= $service->period
                    );
                }

                if ($shouldGenerate) {
                    $pendientes->push([
                        'client'  => $client,
                        'service' => $service,
                    ]);
                }
            }
        }

        return $pendientes;
    }


    /**
     * Crea una factura pendiente copiando los valores actuales
     * del servicio y los datos del cliente.
     *
     * La usan la generación por lote y la factura manual.
     */
    private function crearFactura(
        Client $client,
        Service $service,
        Carbon $generationDate
    ): Invoice {
        $currentMonth = $generationDate
            ->copy()
            ->startOfMonth();

        /*
         * Fecha de vencimiento: el día configurado del mes actual
         * (ajustado al largo del mes). Si ya pasó, vence hoy.
         */
        $dueDay = min(
            $service->due_day,
            $currentMonth->daysInMonth
        );

        $dueDateObject = $currentMonth
            ->copy()
            ->day($dueDay);

        if ($dueDateObject->lt($generationDate->copy()->startOfDay())) {
            $dueDateObject = $generationDate->copy()->startOfDay();
        }

        return Invoice::create([
            'issued_at' => $generationDate->toDateString(),

            // Datos históricos del cliente.
            'client_name' => $client->name,
            'client_document' => $client->document,
            'client_cuit' => $client->cuit,
            'client_document_type' => 96,
            'client_iva_condition' => $client->arca_iva_condition,

            // Identificación interna del servicio.
            'service_id' => $service->id,
            'service_period_start' => $currentMonth->toDateString(),
            'service_period_end' => $currentMonth
                ->copy()
                ->addMonths($service->period)
                ->subDay()
                ->toDateString(),

            // Nombre histórico del servicio.
            'service_name' => $service->service,

            // Valores actuales del servicio.
            'price' => $service->price,
            'due_date' => $dueDateObject->toDateString(),
            'overdue_price' => $service->overdue_price,
            'tax_percentage' => $service->tax_percentage,

            // Estado inicial.
            'payment_status' => 'pending',
            'amount_paid' => null,
            'paid_at' => null,
            'payment_method' => null,
            'paid_by' => null,

            // ARCA.
            'arca_status' => null,
            'arca_cae' => null,
            'arca_cae_expires_at' => null,
            'arca_invoice_type' => null,
            'arca_point_of_sale' => null,
            'arca_invoice_number' => null,
            'arca_qr' => null,
        ]);
    }


    /**
     * Vista previa de la generación por lote (JSON).
     *
     * Devuelve cuántas facturas se generarían de cada servicio.
     * No crea nada.
     */
    public function generatePreview()
    {
        $porServicio = $this
            ->facturasPorGenerar(now())
            ->groupBy(fn ($par) => $par['service']->id)
            ->map(fn ($pares) => [
                'service' => $pares->first()['service']->service,
                'count'   => $pares->count(),
            ])
            ->sortBy('service', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json([
            'total'    => $porServicio->sum('count'),
            'services' => $porServicio,
        ]);
    }


    /**
     * Genera por lote las facturas periódicas que correspondan.
     *
     * Se ejecuta manualmente desde el panel administrativo.
     * El administrador puede actualizar primero los precios de los
     * servicios y luego ejecutar este proceso.
     */
    public function generate()
    {
        /*
         * Evita que dos generaciones (doble clic, dos pestañas o una
         * factura manual) corran al mismo tiempo y dupliquen facturas.
         */
        $lock = Cache::lock('invoices-generate', 120);

        if (!$lock->get()) {
            return redirect()
                ->route('admin.invoices.index')
                ->with(
                    'error',
                    'Ya hay una generación de facturas en curso. Esperá unos segundos e intentá de nuevo.'
                );
        }

        try {
            $generationDate = now();

            $generatedCount = 0;

            foreach ($this->facturasPorGenerar($generationDate) as $par) {
                $this->crearFactura(
                    $par['client'],
                    $par['service'],
                    $generationDate
                );

                $generatedCount++;
            }
        } finally {
            $lock->release();
        }

        return redirect()
            ->route('admin.invoices.index')
            ->with(
                'success',
                "Se generaron {$generatedCount} factura(s) correctamente."
            );
    }


    /**
     * Pantalla para generar una factura manual a un cliente y un
     * servicio actuales (existentes en el sistema).
     */
    public function generateManual()
    {
        return view('admin.invoices.generate-manual');
    }


    /**
     * Buscador de clientes por nombre o documento (JSON).
     */
    public function searchClients(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $like = '%' . addcslashes($q, '%_\\') . '%';

        $clients = Client::query()
            ->where(function ($query) use ($like) {
                $query->where('name', 'ilike', $like)
                    ->orWhereRaw('CAST(document AS TEXT) LIKE ?', [$like]);
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn ($client) => [
                'id'           => $client->id,
                'name'         => $client->name,
                'document'     => $client->document,
                'cuit'         => $client->cuit,
                'iva_condition' => $client->arca_iva_condition
                    ? $client->arca_iva_condition
                        . ' — '
                        . (self::CONDICIONES_IVA[$client->arca_iva_condition] ?? '')
                    : 'No cargada (se factura como Consumidor Final)',
                'phone'        => $client->phone,
                'email'        => $client->email,
                'address'      => $client->address,
            ]);

        return response()->json($clients);
    }


    /**
     * Buscador de servicios por nombre (JSON).
     */
    public function searchServices(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $like = '%' . addcslashes($q, '%_\\') . '%';

        $services = Service::query()
            ->where('service', 'ilike', $like)
            ->orderBy('service')
            ->limit(8)
            ->get()
            ->map(fn ($service) => [
                'id'             => $service->id,
                'name'           => $service->service,
                'price'          => (float) $service->price,
                'overdue_price'  => (float) $service->overdue_price,
                'tax_percentage' => (float) ($service->tax_percentage ?? 0),
                'due_day'        => $service->due_day,
                'period'         => $service->period,
            ]);

        return response()->json($services);
    }


    /**
     * Genera la factura manual para el cliente y servicio elegidos.
     */
    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        /*
         * findOrFail respeta el global scope: un cliente bloqueado
         * no se puede facturar (404).
         */
        $client = Client::findOrFail($validated['client_id']);
        $service = Service::findOrFail($validated['service_id']);

        $lock = Cache::lock('invoices-generate', 120);

        if (!$lock->get()) {
            return redirect()
                ->route('admin.invoices.generate-manual')
                ->with(
                    'error',
                    'Ya hay una generación de facturas en curso. Esperá unos segundos e intentá de nuevo.'
                );
        }

        try {
            $invoice = $this->crearFactura($client, $service, now());
        } finally {
            $lock->release();
        }

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with(
                'success',
                "Factura generada para {$client->name} — {$service->service}."
            );
    }


    /**
     * Muestra el formulario para registrar un pago manual.
     */
    public function payment(Invoice $invoice)
    {
        return view(
            'admin.invoices.payment',
            compact('invoice')
        );
    }


    /**
     * Registra manualmente el pago de una factura.
     */
    public function registerPayment(
        Request $request,
        Invoice $invoice
    ) {
        /*
         * No permitimos registrar nuevamente una factura
         * que ya está pagada.
         */
        if ($invoice->payment_status === 'paid') {
            return redirect()
                ->route(
                    'admin.invoices.show',
                    $invoice
                )
                ->with(
                    'error',
                    'La factura ya figura como pagada.'
                );
        }

        $validated = $request->validate([
            'amount_paid' => [
                'required',
                'numeric',
                'min:0',
            ],

            /*
             * Solo fecha.
             */
            'paid_at' => [
                'required',
                'date',
            ],

            'payment_method' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        /*
         * Registramos el pago y guardamos qué administrador
         * realizó la operación.
         */
        $invoice->update([
            'payment_status' => 'paid',
            'amount_paid' => $validated['amount_paid'],
            'paid_at' => $validated['paid_at'],
            'payment_method' => $validated['payment_method'],
            'paid_by' => auth()->id(),
        ]);

        return redirect()
            ->route(
                'admin.invoices.show',
                $invoice
            )
            ->with(
                'success',
                'Pago registrado correctamente.'
            );
    }


    /**
     * Elimina una factura.
     */
    public function destroy(Invoice $invoice)
    {
        /*
        * Las facturas que tuvieron un pago o una
        * autorización ARCA nunca se pueden eliminar.
        */
        if (
            $invoice->payment_status === 'paid'
            || $invoice->amount_paid !== null
            || !empty($invoice->mercadopago_payment_id)
            || !empty($invoice->arca_cae)
            || $invoice->arca_status === 'aprobado'
        ) {
            return redirect()
                ->route(
                    'admin.invoices.show',
                    $invoice
                )
                ->with(
                    'error',
                    'Una factura pagada o autorizada por ARCA no puede eliminarse.'
                );
        }

        $invoice->delete();

        return redirect()
            ->route('admin.invoices.index')
            ->with(
                'success',
                'Factura eliminada correctamente.'
            );
    }

    public function retryArca(
        Invoice $invoice
    ) {
        if (
            $invoice->payment_status !== 'paid'
        ) {
            return redirect()
                ->route(
                    'admin.invoices.show',
                    $invoice
                )
                ->with(
                    'error',
                    'Solo se puede reintentar ARCA sobre una factura pagada.'
                );
        }

        if (
            $invoice->arca_status === 'aprobado'
            || !empty($invoice->arca_cae)
        ) {
            return redirect()
                ->route(
                    'admin.invoices.show',
                    $invoice
                )
                ->with(
                    'error',
                    'La factura ya fue autorizada por ARCA.'
                );
        }

        $config =
            ArcaConfig::first();

        if (!$config) {
            return redirect()
                ->route(
                    'admin.invoices.show',
                    $invoice
                )
                ->with(
                    'error',
                    'No existe configuración de ARCA.'
                );
        }

        try {

            $service =
                new ArcaApiService(
                    new WsaaClient($config),
                    $config
                );

            /*
            * FALSE = reintento manual.
            *
            * Ignora el temporizador automático.
            */
            $service->emitir(
                $invoice->fresh(),
                false
            );

        } catch (\Throwable $e) {

            Log::error(
                'Error en reintento manual ARCA.',
                [
                    'invoice_id' =>
                        $invoice->id,

                    'message' =>
                        $e->getMessage(),

                    'exception' =>
                        get_class($e),
                ]
            );

            $invoice->update([
                'arca_status' =>
                    'error: ' . $e->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'admin.invoices.show',
                $invoice
            );
    }
}