<x-guest-layout>

    <div class="mb-4 text-sm text-gray-600">
        Introduce el código de 6 dígitos que enviamos a tu correo.
    </div>

    @if (session('success'))
        <div class="mb-4 text-sm text-green-600">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.setup.verify') }}">
        @csrf

        <input
            type="hidden"
            name="email"
            value="{{ session('email') }}"
        >

        <div>
            <x-input-label for="code" :value="'Código'" />

            <x-text-input
                id="code"
                class="block mt-1 w-full"
                type="text"
                name="code"
                maxlength="6"
                inputmode="numeric"
                required
                autofocus
            />

            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Verificar código
            </x-primary-button>
        </div>
    </form>

</x-guest-layout>