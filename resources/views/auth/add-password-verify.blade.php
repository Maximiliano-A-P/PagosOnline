<x-guest-layout>

    <div class="mb-4 text-sm text-gray-600">
        Hemos detectado que este correo ya está asociado a una cuenta creada con Google.
        Para añadir una contraseña a tu cuenta, introduce el código que enviamos a tu correo
        y establece una contraseña.
    </div>

    @if (session('success'))
        <div class="mb-4 text-sm text-green-600">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.setup.verify.code') }}">
        @csrf

        <input
            type="hidden"
            name="email"
            value="{{ session('email') }}"
        >

        {{-- Código de verificación --}}
        <div>
            <x-input-label for="code" :value="'Código de verificación'" />

            <x-text-input
                id="code"
                class="block mt-1 w-full"
                type="text"
                name="code"
                maxlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
                autofocus
            />

            <x-input-error
                :messages="$errors->get('code')"
                class="mt-2"
            />
        </div>

        {{-- Nueva contraseña --}}
        <div class="mt-4">
            <x-input-label
                for="password"
                :value="'Nueva contraseña'"
            />

            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                autocomplete="new-password"
                required
            />

            <x-input-error
                :messages="$errors->get('password')"
                class="mt-2"
            />
        </div>

        {{-- Repetir contraseña --}}
        <div class="mt-4">
            <x-input-label
                for="password_confirmation"
                :value="'Repetir contraseña'"
            />

            <x-text-input
                id="password_confirmation"
                class="block mt-1 w-full"
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                required
            />

            <x-input-error
                :messages="$errors->get('password_confirmation')"
                class="mt-2"
            />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                Añadir contraseña
            </x-primary-button>
        </div>
    </form>

</x-guest-layout>