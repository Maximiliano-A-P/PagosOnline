<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-white leading-tight text-[32px]">
            Editar cliente
        </h2>
    </x-slot>

    <style>
        .card-header {
            background-color: #111827;
            padding: 20px 24px;
        }

        .card-header h3 {
            color: #fff;
            font-weight: 600;
            font-size: 24px;
            margin: 0;
        }

        .card-header p {
            color: #d1d5db;
            font-size: 18px;
            margin: 8px 0 0;
        }

        .btn {
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 6px 14px;
            background-color: #111827;
            border: 1px solid #111827;
            border-radius: 6px;
            font-weight: 600;
            color: #fff;
            font-size: 18px;
            line-height: normal;
            font-family: inherit;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            appearance: none;
            margin: 0;
        }

        .btn:hover {
            background-color: #374151;
        }

        .btn-secondary {
            background-color: #fff;
            border: 1px solid #9ca3af;
            color: #111827;
        }

        .btn-secondary:hover {
            background-color: #f3f4f6;
        }

        .field-label {
            display: block;
            font-weight: 600;
            color: #111827;
            font-size: 18px;
        }

        .field-input {
            display: block;
            width: 100%;
            margin-top: 8px;
            border: 1px solid #9ca3af;
            border-radius: 6px;
            background: #fff;
            color: #111827;
            font-size: 18px;
            padding: 10px 12px;
        }

        .field-input:focus {
            border-color: #4f46e5;
            outline: 2px solid transparent;
            box-shadow: 0 0 0 2px #c7d2fe;
        }

        .field-help {
            color: #4b5563;
            font-size: 15px;
            margin-top: 6px;
        }

        .field-error {
            color: #b91c1c;
            font-size: 16px;
            margin-top: 6px;
        }

        .section-title {
            color: #111827;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .section-help {
            color: #4b5563;
            font-size: 16px;
            margin-bottom: 20px;
        }
    </style>

    <div class="py-12">
        <div class="mx-auto" style="width: 90vw;">

            @if ($errors->any())
                <div class="mb-8 rounded-lg bg-red-700 border border-red-800
                            text-white px-6 py-4 shadow-sm">
                    <ul class="list-disc list-inside space-y-1 text-[18px]">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="card-header">
                    <h3>Editar datos del cliente</h3>
                    <p>Actualizá los datos generales, fiscales y de contacto.</p>
                </div>

                <div class="p-6">
                    <form
                        action="{{ route('admin.clients.update', $client) }}"
                        method="POST"
                        class="space-y-7"
                    >
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="name" class="field-label">
                                Nombre o razón social
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $client->name) }}"
                                required
                                maxlength="255"
                                class="field-input"
                            >
                            @error('name')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="document" class="field-label">
                                DNI o documento de referencia
                            </label>
                            <input
                                type="number"
                                id="document"
                                name="document"
                                value="{{ old('document', $client->document) }}"
                                min="1"
                                required
                                class="field-input"
                            >
                            <p class="field-help">
                                Identificador utilizado por la aplicación para
                                localizar al cliente y relacionar sus facturas.
                            </p>
                            @error('document')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="cuit" class="field-label">
                                CUIT argentino (opcional)
                            </label>
                            <input
                                type="text"
                                id="cuit"
                                name="cuit"
                                value="{{ old('cuit', $client->cuit) }}"
                                inputmode="numeric"
                                maxlength="11"
                                pattern="[0-9]{11}"
                                placeholder="Ej. 20123456789"
                                class="field-input"
                            >
                            <p class="field-help">
                                Para clientes nacionales que tengan CUIT.
                                No cargues aquí el identificador fiscal extranjero.
                            </p>
                            @error('cuit')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="arca_iva_condition" class="field-label">
                                Condición frente al IVA (código ARCA)
                            </label>
                            <input
                                type="number"
                                id="arca_iva_condition"
                                name="arca_iva_condition"
                                list="condicionIvaReferencia"
                                value="{{ old('arca_iva_condition', $client->arca_iva_condition) }}"
                                min="1"
                                step="1"
                                class="field-input"
                            >

                            <datalist id="condicionIvaReferencia">
                                <option value="1">IVA Responsable Inscripto</option>
                                <option value="4">IVA Sujeto Exento</option>
                                <option value="5">Consumidor Final</option>
                                <option value="6">Responsable Monotributo</option>
                                <option value="7">Sujeto No Categorizado</option>
                                <option value="8">Proveedor del Exterior</option>
                                <option value="9">Cliente del Exterior</option>
                                <option value="10">IVA Liberado</option>
                                <option value="13">Monotributista Social</option>
                                <option value="15">IVA No Alcanzado</option>
                                <option value="16">Monotributo Trabajador Independiente Promovido</option>
                            </datalist>

                            <p class="field-help">
                                Para clasificar al receptor como cliente del exterior,
                                utilizá el código 9 según la clasificación configurada
                                por la aplicación.
                            </p>

                            @error('arca_iva_condition')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <hr class="border-gray-300">

                        <div>
                            <h3 class="section-title">
                                Datos fiscales del comprador extranjero (opcional)
                            </h3>
                            <p class="section-help">
                                Completá estos datos para las operaciones de exportación.
                                Se solicitan cuando el cliente se identifica como
                                cliente del exterior.
                            </p>
                        </div>

                        <div>
                            <label
                                for="arca_destination_country"
                                class="field-label"
                            >
                                Código de país de destino según ARCA
                            </label>
                            <input
                                type="number"
                                id="arca_destination_country"
                                name="arca_destination_country"
                                value="{{ old('arca_destination_country', $client->arca_destination_country) }}"
                                min="1"
                                max="999"
                                step="1"
                                class="field-input"
                            >
                            <p class="field-help">
                                Utilizá el código numérico del país registrado en el
                                catálogo de países utilizado por ARCA.
                            </p>
                            @error('arca_destination_country')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="arca_cuit_pais_cliente"
                                class="field-label"
                            >
                                Identificador fiscal del comprador en su país
                                (opcional)
                            </label>
                            <input
                                type="text"
                                id="arca_cuit_pais_cliente"
                                name="arca_cuit_pais_cliente"
                                value="{{ old('arca_cuit_pais_cliente', $client->arca_cuit_pais_cliente) }}"
                                inputmode="numeric"
                                maxlength="11"
                                pattern="[0-9]{1,11}"
                                class="field-input"
                            >
                            @error('arca_cuit_pais_cliente')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="arca_id_impositivo"
                                class="field-label"
                            >
                                ID impositivo extranjero (opcional)
                            </label>
                            <input
                                type="text"
                                id="arca_id_impositivo"
                                name="arca_id_impositivo"
                                value="{{ old('arca_id_impositivo', $client->arca_id_impositivo) }}"
                                maxlength="50"
                                class="field-input"
                            >
                            <p class="field-help">
                                Para una Factura E debe informarse al menos uno de
                                los dos identificadores fiscales extranjeros admitidos
                                por la integración.
                            </p>
                            @error('arca_id_impositivo')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="field-label">
                                Teléfono (opcional)
                            </label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="{{ old('phone', $client->phone) }}"
                                maxlength="255"
                                class="field-input"
                            >
                            @error('phone')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="field-label">
                                Email de contacto (opcional)
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $client->email) }}"
                                maxlength="255"
                                class="field-input"
                            >
                            @error('email')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="address" class="field-label">
                                Dirección (opcional para clientes nacionales)
                            </label>
                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="{{ old('address', $client->address) }}"
                                maxlength="255"
                                class="field-input"
                            >
                            <p class="field-help">
                                Para clientes del exterior, este dato es necesario
                                para emitir una Factura E.
                            </p>
                            @error('address')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-4">
                            <a
                                href="{{ route('admin.clients.index') }}"
                                class="btn btn-secondary"
                            >
                                Cancelar
                            </a>

                            <button type="submit" class="btn">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>