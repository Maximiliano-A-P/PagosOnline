<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (auth()->user()->email_verified_at === null)
                <div class="mb-6 p-6 bg-gray-800 border border-gray-600 rounded-lg">
                    <p class="text-2xl font-semibold text-white">
                        Tu cuenta todavía no está verificada.
                    </p>

                    <p class="mt-3 text-xl text-white">
                        Revisa tu correo electrónico para verificarla.
                    </p>

                    <a
                        href="{{ route('email.verification.show') }}"
                        class="inline-block mt-4 text-xl font-semibold text-white underline hover:text-gray-200"
                    >
                        Verificar mi cuenta
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>