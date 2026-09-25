<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-white leading-tight text-[32px]">
                Configuración ARCA
            </h2>

            
                href="{{ route('admin.dashboard') }}"
                class="btn"
            >
                Volver
            </a>
        </div>
    </x-slot>

    <style>
        .btn {
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 22px;
            background-color: #111827; /* gray-900 */
            border: 1px solid #111827;
            border-radius: 6px;
            font-weight: 600;
            color: #ffffff;
            font-size: 18px;
            line-height: normal;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            margin: 0;
            transition: background-color 0.15s ease-in-out;
        }

        .btn:hover {
            background-color: #374151; /* gray-700 */
        }

        .info-box {
            background-color: #111827; /* gray-900 */
            border-radius: 8px;
            padding: 20px;
        }

        .info-box h4 {
            color: #ffffff;
            font-weight: 600;
            font-size: 16px;
            margin: 0;
        }

        .info-box p {
            color: #d1d5db; /* gray-300 */
            font-size: 14px;
            margin: 8px 0 0;
        }

        .help-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .help-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb; /* gray-200 */
        }

        .help-list li:last-child {
            border-bottom: none;
        }

        .help-list .help-name {
            font-weight: 600;
            color: #111827; /* gray-900 */
            font-size: 18px;
        }

        .help-list .help-desc {
            color: #4b5563; /* gray-600 */
            font-size: 16px;
            margin-top: 4px;
        }
    </style>

    <div class="py-12">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">

            {{-- Mensaje de éxito --}}
            @if (session('success'))
                <div
                    class="mb-6 p-4 bg-green-700 text-white rounded-lg shadow-sm text-[24px]"
                >
                    {{ session('success') }}
                </div>
            @endif

            {{-- Errores de validación --}}
            @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-red-700 text-white rounded-lg shadow-sm"
                >
                    <ul class="list-disc list-inside text-[24px]">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900">

                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 text-[32px]">
                            Datos de ARCA
                        </h3>

                        <p class="mt-2 text-gray-700 text-[24px]">
                            Configuración necesaria para la comunicación
                            del sistema con ARCA.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.arca.update') }}"
                        class="space-y-8"
                    >
                        @csrf
                        @method('PUT')

                        {{-- CUIT --}}
                        <div>
                            <label
                                for="cuit"
                                class="block font-medium text-gray-900 text-[24px]"
                            >
                                CUIT
                            </label>

                            <input
                                id="cuit"
                                name="cuit"
                                type="text"
                                value="{{ old('cuit', $config?->cuit) }}"
                                required
                                class="mt-2 block w-full rounded-md border-gray-400 shadow-sm text-[24px] text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <p class="mt-2 text-gray-700 text-[24px]">
                                CUIT de la empresa utilizada para operar
                                con ARCA.
                            </p>
                        </div>

                        {{-- Condición frente al IVA (emisor) --}}
                        <div>
                            <label
                                for="condicion_iva"
                                class="block font-medium text-gray-900 text-[24px]"
                            >
                                Condición frente al IVA (código AFIP)
                            </label>

                            <input
                                id="condicion_iva"
                                name="condicion_iva"
                                type="number"
                                min="1"
                                list="condicionIvaReferencia"
                                value="{{ old('condicion_iva', $config?->condicion_iva) }}"
                                required
                                class="mt-2 block w-full rounded-md border-gray-400 shadow-sm text-[24px] text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <datalist id="condicionIvaReferencia">
                                <option value="1">IVA Responsable Inscripto</option>
                                <option value="4">IVA Sujeto Exento</option>
                                <option value="5">Consumidor Final</option>
                                <option value="6">Responsable Monotributo</option>
                                <option value="7">Sujeto No Categorizado</option>
                                <option value="8">Proveedor del Exterior</option>
                                <option value="9">Cliente del Exterior</option>
                                <option value="10">IVA Liberado – Ley N° 19.640</option>
                                <option value="13">Monotributista Social</option>
                                <option value="15">IVA No Alcanzado</option>
                                <option value="16">Monotributo Trabajador Independiente Promovido</option>
                            </datalist>

                            <p class="mt-2 text-gray-700 text-[24px]">
                                Se puede escribir cualquier código numérico. La lista es
                                solo de referencia (tabla de condiciones de ARCA/AFIP
                                vigente al momento de escribir esto) — si ARCA agrega o
                                modifica códigos, se carga el nuevo valor directamente
                                acá sin necesitar ningún cambio de código. Ver la
                                sección "Explicaciones" más abajo para el detalle de
                                qué implica cada una.
                            </p>
                        </div>

                        {{-- Punto de venta --}}
                        <div>
                            <label
                                for="punto_venta"
                                class="block font-medium text-gray-900 text-[24px]"
                            >
                                Punto de venta
                            </label>

                            <input
                                id="punto_venta"
                                name="punto_venta"
                                type="number"
                                min="1"
                                value="{{ old('punto_venta', $config?->punto_venta) }}"
                                required
                                class="mt-2 block w-full rounded-md border-gray-400 shadow-sm text-[24px] text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <p class="mt-2 text-gray-700 text-[24px]">
                                Punto de venta habilitado en ARCA que se usa
                                para pedir el CAE. En homologación normalmente
                                alcanza con el 1. Ver "Explicaciones" más abajo.
                            </p>
                        </div>

                        {{-- Información del certificado --}}
                        <div class="info-box">

                            <h4>
                                Certificado y clave privada
                            </h4>

                            <p>
                                No se cargan desde acá: salen de las variables
                                de entorno ARCA_CERTIFICATE_CRT y
                                ARCA_PRIVATE_KEY (Base64) configuradas en el
                                servidor.
                            </p>

                        </div>

                        {{-- Información del token --}}
                        <div class="info-box">

                            <h4>
                                Estado de autenticación
                            </h4>

                            @if ($config?->token && $config?->sign)
                                <p>
                                    Token y Sign configurados.
                                </p>

                                @if ($config->token_expires_at)
                                    <p>
                                        Vencimiento:
                                        {{ $config->token_expires_at->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            @else
                                <p>
                                    Todavía no se generó un Token/Sign de autenticación
                                    contra ARCA.
                                </p>
                            @endif

                            <p>
                                El Token y el Sign son gestionados automáticamente por
                                la integración con ARCA — no se cargan manualmente acá.
                            </p>

                        </div>

                        {{-- Botón --}}
                        <div class="flex items-center justify-end" style="margin-top: 8px;">

                            <button
                                type="submit"
                                class="btn"
                            >
                                Guardar configuración
                            </button>

                        </div>

                    </form>

                    {{-- ================================================== --}}
                    {{-- Explicaciones --}}
                    {{-- ================================================== --}}

                    <hr class="my-10 border-gray-300">

                    <h3 class="font-semibold text-gray-900 text-[32px] mb-6">
                        Explicaciones
                    </h3>

                    <div class="mb-8">
                        <h4 class="font-semibold text-gray-900 text-[20px] mb-2">
                            Condición frente al IVA
                        </h4>

                        <p class="text-gray-700 text-[16px] mb-4">
                            Es la categoría del emisor (la empresa/persona que factura)
                            ante ARCA/AFIP. El sistema la usa para decidir si el
                            comprobante se emite como Factura A, B o C. Con condición
                            "Responsable Inscripto" el sistema mira también la condición
                            del cliente (Responsable Inscripto → Factura A, cualquier otra
                            → Factura B). Con cualquier otra condición del emisor
                            (Monotributo, Exento, etc.), siempre se emite Factura C.
                        </p>

                        <ul class="help-list">
                            <li>
                                <div class="help-name">1 — IVA Responsable Inscripto</div>
                                <div class="help-desc">
                                    Inscripto en IVA. Es la única condición que puede
                                    generar Factura A (si el cliente también es
                                    Responsable Inscripto) o Factura B (para el resto de
                                    los clientes). Para Factura A el cliente necesita
                                    CUIT cargado.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">4 — IVA Sujeto Exento</div>
                                <div class="help-desc">
                                    Exento de IVA por la actividad que realiza. El
                                    sistema lo trata igual que cualquier condición que
                                    no sea Responsable Inscripto: siempre emite Factura C.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">5 — Consumidor Final</div>
                                <div class="help-desc">
                                    No está inscripto ante AFIP como contribuyente.
                                    Es también el valor por defecto que usa el sistema
                                    para un cliente sin condición cargada. Como emisor,
                                    resulta en Factura C.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">6 — Responsable Monotributo</div>
                                <div class="help-desc">
                                    Monotributista. Siempre emite Factura C y nunca
                                    discrimina IVA por separado.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">7 — Sujeto No Categorizado</div>
                                <div class="help-desc">
                                    Contribuyente sin categoría de IVA definida todavía
                                    ante AFIP. Como emisor, resulta en Factura C.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">8 — Proveedor del Exterior</div>
                                <div class="help-desc">
                                    Proveedor radicado fuera del país. Como emisor,
                                    resulta en Factura C.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">9 — Cliente del Exterior</div>
                                <div class="help-desc">
                                    Cliente radicado fuera del país. Como emisor,
                                    resulta en Factura C.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">10 — IVA Liberado – Ley N° 19.640</div>
                                <div class="help-desc">
                                    Liberado de IVA por un régimen especial (Tierra del
                                    Fuego). Como emisor, resulta en Factura C.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">13 — Monotributista Social</div>
                                <div class="help-desc">
                                    Variante del monotributo para emprendimientos
                                    sociales. Como emisor, resulta en Factura C.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">15 — IVA No Alcanzado</div>
                                <div class="help-desc">
                                    La actividad no está alcanzada por el impuesto al
                                    IVA. Como emisor, resulta en Factura C.
                                </div>
                            </li>
                            <li>
                                <div class="help-name">16 — Monotributo Trabajador Independiente Promovido</div>
                                <div class="help-desc">
                                    Variante del monotributo para trabajadores
                                    independientes promovidos. Como emisor, resulta en
                                    Factura C.
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 text-[20px] mb-2">
                            Punto de venta
                        </h4>

                        <p class="text-gray-700 text-[16px]">
                            Es el número que identifica, dentro del CUIT configurado,
                            el canal por el que se factura (por ejemplo, "sucursal web").
                            Cada punto de venta tiene su propia numeración correlativa
                            de comprobantes en ARCA: al pedir un CAE, el sistema informa
                            este número para que ARCA sepa qué numeración de factura
                            corresponde. En el ambiente de producción, el punto de venta
                            tiene que estar dado de alta en ARCA (Administrador de puntos
                            de venta y domicilios) como habilitado para "Factura
                            Electrónica – Web Services" antes de poder usarlo acá. En el
                            ambiente de homologación (pruebas) no hace falta darlo de
                            alta — alcanza con usar el 1.
                        </p>
                    </div>

                </div>

            </div>

        </div>
    </div>

</x-app-layout>