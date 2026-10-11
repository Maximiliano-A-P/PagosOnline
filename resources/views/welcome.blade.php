<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                background-color: #111827;
                color: #ffffff;
                font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            nav {
                display: flex;
                justify-content: flex-end;
                gap: 16px;
                padding: 24px 32px;
            }

            nav a {
                color: #ffffff;
                text-decoration: none;
                font-size: 16px;
                padding: 8px 20px;
                border: 1px solid transparent;
                border-radius: 4px;
            }

            nav a:hover {
                border-color: #374151;
            }

            main {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 24px;
            }

            h1 {
                color: #4f46e5;
                font-size: clamp(2.75rem, 9vw, 5.5rem);
                font-weight: 700;
                line-height: 1.1;
            }

            p {
                margin-top: 16px;
                color: #ffffff;
                font-size: clamp(1rem, 2.5vw, 1.375rem);
            }
        </style>
    </head>
    <body>
        @if (Route::has('login'))
            <nav>
                @auth
                    <a href="{{ url('/dashboard') }}">Panel</a>
                @else
                    <a href="{{ route('login') }}">Iniciar sesión</a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">Registrarse</a>
                    @endif
                @endauth
            </nav>
        @endif

        <main>
            <h1>{{ config('app.name') }}</h1>
            <p>Pague de manera online sus facturas</p>
        </main>
    </body>
</html>