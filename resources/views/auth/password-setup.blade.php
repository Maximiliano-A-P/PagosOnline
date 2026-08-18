<x-guest-layout>

    <div class="mb-4 text-sm text-gray-600">
        Esta cuenta fue creada mediante Google.
        Para agregar una contraseña, necesitamos verificar tu correo electrónico.
    </div>

    @if (session('success'))
        <div class="mb-4 text-sm text-green-600">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.setup.send') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="'Correo electrónico'" />

            <x-text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                value="{{ session('email') }}"
                required
                autofocus
            />

            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Enviar código
            </x-primary-button>
        </div>
    </form>

</x-guest-layout>