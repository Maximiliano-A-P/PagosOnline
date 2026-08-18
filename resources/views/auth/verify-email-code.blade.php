<x-guest-layout>

    <div class="mb-4 text-sm text-gray-600">
        Tu cuenta todavía no está verificada.
        Te enviaremos un código de 6 dígitos a tu correo electrónico.
    </div>

    @if (session('success'))
        <div class="mb-4 text-sm text-green-600">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 text-sm text-red-600">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('email.verification.send') }}">
        @csrf

        <button
            type="submit"
            class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
        >
            Enviar código
        </button>
    </form>

    <form method="POST" action="{{ route('email.verification.verify') }}" class="mt-6">
        @csrf

        <div>
            <label for="code" class="block font-medium text-sm text-gray-700">
                Código de verificación
            </label>

            <input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                required
                autofocus
                class="block mt-1 w-full border-gray-300 rounded-md"
            >
        </div>

        <button
            type="submit"
            class="w-full mt-4 inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
        >
            Verificar cuenta
        </button>
    </form>

</x-guest-layout>