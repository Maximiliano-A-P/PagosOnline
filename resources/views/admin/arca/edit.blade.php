<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-white leading-tight text-[32px]">
                Configuración ARCA
            </h2>

            <a href="{{ route('admin.dashboard') }}" class="btn">
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
            background-color: #111827;
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
            background-color: #374151;
        }

        .info-box {
            background-color: #111827;
            border-radius: 8px;
            padding: 20px;
        }

        .info-box h4 {
            color: #ffffff;
            font-weight: 600;
            font-size: 22px;
            margin: 0;
        }

        .info-box p {
            color: #d1d5db;
            font-size: 18px;
            margin: 8px 0 0;
        }

        .help-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .help-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .help-list li:last-child {
            border-bottom: none;
        }

        .help-list .help-name {
            font-weight: 600;
            color: #111827;
            font-size: 18px;
        }

        .help-list .help-desc {
            color: #4b5563;
            font-size: 16px;
            margin-top: 4px;
        }
    </style>

    <div class="py-12">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 p-4 bg-green-700 text-white rounded-lg shadow-sm text-[24px]">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-700 text-white rounded-lg shadow-sm">
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
                            Configuración necesaria para emitir comprobantes
                            fiscales nacionales y de exportación.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.arca.update') }}"
                        class="space-y-8"
                    >
                        @csrf
                        @method('PUT')

                        {{-- Condición fiscal del emisor --}}
                        <div>
                            <label
                                for="condicion_iva"
                                class="block font-medium text-gray-900 text-[24px]"
                            >
                                Condición frente al IVA del emisor
                            </label>

                            <input
                                id="condicion_iva"
                                name="condicion_iva"
                                type="number"
                                min="1"
                                step="1"
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
                                <option value="10">IVA Liberado – Ley N.º 19.640</option>
                                <option value="13">Monotributista Social</option>
                                <option value="15">IVA No Alcanzado</option>
                                <option value="16">Monotributo Trabajador Independiente Promovido</option>
                            </datalist>

                            <p class="mt-2 text-gray-700 text-[18px]">
                                Indicá la condición fiscal registrada para el emisor.
                                La condición fiscal del cliente también se utiliza
                                para determinar el tipo de comprobante.
                            </p>
                        </div>

                        {{-- Punto de venta nacional --}}
                        <div>
                            <label
                                for="punto_venta"
                                class="block font-medium text-gray-900 text-[24px]"
                            >
                                Punto de venta nacional (WSFEv1)
                            </label>

                            <input
                                id="punto_venta"
                                name="punto_venta"
                                type="number"
                                min="1"
                                max="99998"
                                step="1"
                                value="{{ old('punto_venta', $config?->punto_venta) }}"
                                required
                                class="mt-2 block w-full rounded-md border-gray-400 shadow-sm text-[24px] text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <p class="mt-2 text-gray-700 text-[18px]">
                                Se utiliza para los comprobantes nacionales emitidos
                                mediante WSFEv1. Debe coincidir con un punto de venta
                                habilitado para el servicio correspondiente en ARCA.
                            </p>
                        </div>

                        {{-- Punto de venta para exportación --}}
                        <div>
                            <label
                                for="punto_venta_exportacion"
                                class="block font-medium text-gray-900 text-[24px]"
                            >
                                Punto de venta de exportación (WSFEXv1)
                            </label>

                            <input
                                id="punto_venta_exportacion"
                                name="punto_venta_exportacion"
                                type="number"
                                min="1"
                                max="99998"
                                step="1"
                                value="{{ old('punto_venta_exportacion', $config?->punto_venta_exportacion) }}"
                                class="mt-2 block w-full rounded-md border-gray-400 shadow-sm text-[24px] text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <p class="mt-2 text-gray-700 text-[18px]">
                                Es independiente del punto de venta nacional. Debe
                                estar habilitado para emitir comprobantes de exportación
                                mediante WSFEXv1. Es necesario configurarlo antes de
                                emitir una Factura E.
                            </p>

                            @error('punto_venta_exportacion')
                                <p class="mt-2 text-red-700 text-[18px]">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Certificado --}}
                        <div class="info-box">
                            <h4>Certificado y clave privada</h4>

                            <p>
                                Se configuran en las variables de entorno
                                ARCA_CERTIFICATE_CRT y ARCA_PRIVATE_KEY del servidor.
                                No se cargan desde esta pantalla.
                            </p>
                        </div>

                        {{-- Estado de WSFE --}}
                        <div class="info-box">
                            <h4>Autenticación nacional (WSFEv1)</h4>

                            @if ($config?->token && $config?->sign)
                                <p>Token y Sign configurados.</p>

                                @if ($config->token_expires_at)
                                    <p>
                                        Vencimiento:
                                        {{ $config->token_expires_at->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            @else
                                <p>
                                    Todavía no se generaron credenciales para WSFEv1.
                                    Se obtendrán automáticamente cuando sean necesarias.
                                </p>
                            @endif
                        </div>

                        {{-- Estado de WSFEX --}}
                        <div class="info-box">
                            <h4>Autenticación de exportación (WSFEXv1)</h4>

                            @if ($config?->token_wsfex && $config?->sign_wsfex)
                                <p>Token y Sign de WSFEXv1 configurados.</p>

                                @if ($config->token_wsfex_expires_at)
                                    <p>
                                        Vencimiento:
                                        {{ $config->token_wsfex_expires_at->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            @else
                                <p>
                                    Todavía no se generaron credenciales para WSFEXv1.
                                    Se obtendrán automáticamente cuando se emita una
                                    factura de exportación.
                                </p>
                            @endif

                            <p>
                                Las credenciales de WSFEXv1 son independientes de
                                las de WSFEv1.
                            </p>
                        </div>

                        <div class="flex items-center justify-end" style="margin-top: 8px;">
                            <button type="submit" class="btn">
                                Guardar configuración
                            </button>
                        </div>
                    </form>

                    <hr class="my-10 border-gray-300">

                    <h3 class="font-semibold text-gray-900 text-[32px] mb-6">
                        Explicaciones
                    </h3>

                    {{-- Explicación de la selección del comprobante --}}
                    <div class="mb-8">
                        <h4 class="font-semibold text-gray-900 text-[22px] mb-2">
                            Selección del comprobante
                        </h4>

                        <p class="text-gray-700 text-[16px] mb-4">
                            Para los clientes nacionales, el sistema utiliza la
                            condición fiscal del emisor y la del receptor para decidir
                            entre los comprobantes A, B o C según las reglas existentes.
                            Cuando el receptor está identificado como cliente del
                            exterior mediante el código 9, el sistema deriva la emisión
                            a una Factura E mediante WSFEXv1.
                        </p>

                        <ul class="help-list">
                            <li>
                                <div class="help-name">
                                    1 — IVA Responsable Inscripto
                                </div>
                                <div class="help-desc">
                                    Para operaciones nacionales, el tipo de comprobante
                                    depende también de la condición fiscal del cliente.
                                    Una operación que corresponda a Factura A requiere
                                    que el cliente tenga CUIT.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    6 — Responsable Monotributo
                                </div>
                                <div class="help-desc">
                                    Condición fiscal del emisor. El sistema mantiene
                                    las reglas configuradas para comprobantes nacionales.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    9 — Cliente del Exterior
                                </div>
                                <div class="help-desc">
                                    Es el código que identifica al receptor extranjero
                                    en la clasificación usada por el sistema. Cuando se
                                    selecciona para el cliente, corresponde una Factura E
                                    si la operación cumple los requisitos de exportación.
                                </div>
                            </li>
                        </ul>
                    </div>

                    {{-- Explicación recuperada: condición frente al IVA --}}
                    <div class="mb-8">
                        <h4 class="font-semibold text-gray-900 text-[22px] mb-2">
                            Condición frente al IVA
                        </h4>

                        <p class="text-gray-700 text-[16px] mb-4">
                            Es la categoría del emisor (la empresa o persona que factura)
                            ante ARCA. El sistema utiliza esta condición para determinar
                            cómo emitir los comprobantes nacionales. Cuando el emisor
                            es Responsable Inscripto, también se considera la condición
                            fiscal del cliente para decidir entre Factura A y Factura B.
                            Para las demás condiciones del emisor, se mantienen las
                            reglas configuradas para la emisión nacional.
                        </p>

                        <ul class="help-list">
                            <li>
                                <div class="help-name">
                                    1 — IVA Responsable Inscripto
                                </div>
                                <div class="help-desc">
                                    Inscripto en IVA. Es la condición que permite
                                    generar Factura A cuando el cliente también es
                                    Responsable Inscripto, o Factura B para otros
                                    clientes nacionales. Para una Factura A, el cliente
                                    necesita tener CUIT cargado.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    4 — IVA Sujeto Exento
                                </div>
                                <div class="help-desc">
                                    El emisor está exento de IVA por la actividad que
                                    realiza. Su tratamiento depende de las reglas
                                    configuradas para el tipo de comprobante nacional.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    5 — Consumidor Final
                                </div>
                                <div class="help-desc">
                                    Identifica la condición de consumidor final. También
                                    es uno de los valores de referencia utilizados para
                                    clientes cuya condición fiscal no está cargada.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    6 — Responsable Monotributo
                                </div>
                                <div class="help-desc">
                                    Identifica al emisor adherido al monotributo.
                                    El sistema conserva las reglas de emisión
                                    correspondientes a esa condición fiscal.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    7 — Sujeto No Categorizado
                                </div>
                                <div class="help-desc">
                                    Identifica a un sujeto que no tiene definida una
                                    categoría de IVA en los términos de esta clasificación.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    8 — Proveedor del Exterior
                                </div>
                                <div class="help-desc">
                                    Identifica a un proveedor radicado fuera del país.
                                    No debe confundirse con la condición fiscal del
                                    emisor nacional.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    9 — Cliente del Exterior
                                </div>
                                <div class="help-desc">
                                    Identifica a un cliente radicado fuera del país.
                                    En el flujo de exportación del sistema, esta
                                    condición corresponde al receptor y permite
                                    determinar cuándo debe utilizarse una Factura E,
                                    siempre que se cumplan los requisitos aplicables.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    10 — IVA Liberado – Ley N.º 19.640
                                </div>
                                <div class="help-desc">
                                    Identifica una condición relacionada con un régimen
                                    especial de liberación del IVA, como el aplicable
                                    en Tierra del Fuego.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    13 — Monotributista Social
                                </div>
                                <div class="help-desc">
                                    Variante del monotributo destinada a determinados
                                    emprendimientos y trabajadores de la economía social.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    15 — IVA No Alcanzado
                                </div>
                                <div class="help-desc">
                                    Identifica actividades u operaciones que no están
                                    alcanzadas por el impuesto al valor agregado.
                                </div>
                            </li>

                            <li>
                                <div class="help-name">
                                    16 — Monotributo Trabajador Independiente Promovido
                                </div>
                                <div class="help-desc">
                                    Variante prevista para trabajadores independientes
                                    promovidos dentro del régimen correspondiente.
                                </div>
                            </li>
                        </ul>

                        <p class="text-gray-700 text-[16px] mt-4">
                            Los códigos anteriores describen categorías de la
                            clasificación fiscal. La validez concreta de cada código
                            depende de que corresponda al emisor o al receptor según
                            el dato que se esté completando y el tipo de operación.
                        </p>
                    </div>

                    {{-- Explicación del punto de venta nacional --}}
                    <div class="mb-8">
                        <h4 class="font-semibold text-gray-900 text-[22px] mb-2">
                            Punto de venta nacional
                        </h4>

                        <p class="text-gray-700 text-[16px]">
                            Es el punto de venta habilitado para los comprobantes
                            nacionales de WSFEv1. Se utiliza para solicitar el CAE y
                            mantener la numeración correlativa correspondiente.
                        </p>
                    </div>

                    {{-- Explicación del punto de venta de exportación --}}
                    <div class="mb-8">
                        <h4 class="font-semibold text-gray-900 text-[22px] mb-2">
                            Punto de venta de exportación
                        </h4>

                        <p class="text-gray-700 text-[16px]">
                            WSFEXv1 utiliza el punto de venta habilitado para los
                            comprobantes de exportación. No debe asumirse que el punto
                            de venta nacional sirve también para exportaciones. Si no
                            se configura, el sistema detendrá el intento de emisión de
                            la Factura E y mostrará el motivo en el estado de la factura.
                        </p>
                    </div>

                    {{-- Explicación recuperada: requisitos del punto de venta --}}
                    <div class="mb-8">
                        <h4 class="font-semibold text-gray-900 text-[22px] mb-2">
                            Requisitos del punto de venta en producción y homologación
                        </h4>

                        <p class="text-gray-700 text-[16px]">
                            El punto de venta es el número que identifica, dentro del
                            CUIT del emisor, el canal por el que se factura, por ejemplo,
                            una sucursal web. Cada punto de venta tiene su propia
                            numeración correlativa de comprobantes. Al solicitar un CAE,
                            el sistema informa este número para que ARCA determine qué
                            numeración corresponde.
                        </p>

                        <p class="text-gray-700 text-[16px] mt-3">
                            En producción, el punto de venta debe estar dado de alta
                            en ARCA en la administración de puntos de venta y domicilios,
                            con el sistema habilitado que corresponda al emisor y al
                            servicio de facturación electrónica utilizado. Si el punto
                            de venta 1 ya se utiliza para facturas manuales u otro canal
                            de emisión, es necesario verificar su configuración antes
                            de reutilizarlo.
                        </p>

                        <p class="text-gray-700 text-[16px] mt-3">
                            En homologación, el entorno de pruebas tiene una
                            configuración independiente de producción. Se deben utilizar
                            las credenciales, certificados y puntos de venta que
                            correspondan al entorno de pruebas, sin asumir que la
                            configuración de producción sirve automáticamente para
                            homologación.
                        </p>

                        <p class="text-gray-700 text-[16px] mt-3">
                            El punto de venta nacional configurado arriba se utiliza
                            con WSFEv1. Para las Facturas E, debe configurarse por
                            separado el punto de venta destinado a WSFEXv1. Ambos deben
                            estar habilitados para el servicio que corresponda y tener
                            una configuración válida en el entorno utilizado.
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>