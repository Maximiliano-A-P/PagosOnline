<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
         * Facturas más recientes primero.
         */
        $invoices = $query
            ->latest('issued_at')
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
            ],

            'overdue_price' => [
                'required',
                'numeric',
                'min:0',
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
     * Genera las facturas periódicas que correspondan.
     *
     * Este método se ejecuta manualmente desde el panel
     * administrativo.
     *
     * El administrador puede actualizar primero los precios
     * de los servicios y posteriormente ejecutar este proceso.
     */
    public function generate()
    {
        /*
         * Momento exacto en que se ejecuta la generación.
         */
        $generationDate = now();

        /*
         * Usamos el primer día del mes como referencia
         * para calcular el período.
         */
        $currentMonth = $generationDate
            ->copy()
            ->startOfMonth();

        /*
         * Contador de facturas generadas.
         */
        $generatedCount = 0;

        /*
         * Obtenemos clientes y sus servicios contratados.
         */
        $clients = Client::with('services')->get();

        foreach ($clients as $client) {

            foreach ($client->services as $service) {

                /*
                 * Buscamos la última factura de este cliente
                 * y de ESTE service_id.
                 *
                 * No usamos service_name para identificarlo,
                 * porque el nombre del servicio puede cambiar.
                 */
                $lastInvoice = Invoice::query()
                    ->where(
                        'client_document',
                        $client->document
                    )
                    ->where(
                        'service_id',
                        $service->id
                    )
                    ->latest('issued_at')
                    ->first();

                /*
                 * Si nunca fue facturado este servicio al cliente,
                 * generamos la primera factura.
                 */
                if (!$lastInvoice) {
                    $shouldGenerate = true;
                } else {

                    /*
                     * Tomamos solamente el año y mes de la última
                     * factura para comparar períodos.
                     */
                    $lastInvoiceMonth = Carbon::parse(
                        $lastInvoice->issued_at
                    )->startOfMonth();

                    /*
                     * Cantidad de meses transcurridos.
                     */
                    $monthsElapsed = $lastInvoiceMonth->diffInMonths(
                        $currentMonth
                    );

                    /*
                     * Generamos cuando pasó el período completo.
                     */
                    $shouldGenerate = (
                        $monthsElapsed >= $service->period
                    );
                }

                /*
                 * Todavía no corresponde generar.
                 */
                if (!$shouldGenerate) {
                    continue;
                }

                /*
                 * ==================================================
                 * FECHA DE EMISIÓN
                 * ==================================================
                 *
                 * La base de datos ahora guarda solamente la fecha.
                 */
                $issuedAt = $generationDate->toDateString();

                /*
                 * ==================================================
                 * FECHA DE VENCIMIENTO
                 * ==================================================
                 */
                $dueDay = min(
                    $service->due_day,
                    $currentMonth->daysInMonth
                );

                $dueDate = $currentMonth
                    ->copy()
                    ->day($dueDay)
                    ->toDateString();

                /*
                 * ==================================================
                 * CREAR FACTURA
                 * ==================================================
                 *
                 * Se copian los valores actuales del servicio.
                 */
                Invoice::create([
                    'issued_at' => $issuedAt,

                    /*
                     * Datos históricos del cliente.
                     */
                    'client_name' => $client->name,
                    'client_document' => $client->document,

                    /*
                     * Identificación interna del servicio.
                     */
                    'service_id' => $service->id,

                    /*
                     * Nombre histórico del servicio.
                     */
                    'service_name' => $service->service,

                    /*
                     * Valores actuales del servicio.
                     */
                    'price' => $service->price,
                    'due_date' => $dueDate,
                    'overdue_price' => $service->overdue_price,

                    /*
                     * Estado inicial.
                     */
                    'payment_status' => 'pending',
                    'amount_paid' => null,
                    'paid_at' => null,
                    'payment_method' => null,
                    'paid_by' => null,

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

                $generatedCount++;
            }
        }

        return redirect()
            ->route('admin.invoices.index')
            ->with(
                'success',
                "Se generaron {$generatedCount} factura(s) correctamente."
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
        $invoice->delete();

        return redirect()
            ->route('admin.invoices.index')
            ->with(
                'success',
                'Factura eliminada correctamente.'
            );
    }
}