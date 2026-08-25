<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Crear cliente</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 20px;
            background-color: #f3f4f6; /* gray-100 */
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .container {
            max-width: 640px;
            margin: 0 auto;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 24px;
        }

        .card {
            background-color: #ffffff;
            border: 1px solid #d1d5db; /* gray-300 */
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            padding: 24px;
        }

        .error-box {
            background-color: #b91c1c; /* red-700 */
            border: 1px solid #991b1b; /* red-800 */
            color: #ffffff;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .error-box ul {
            margin: 0;
            padding-left: 20px;
            list-style: disc;
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #111827;
            font-size: 14px;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            color: #111827;
            background-color: #ffffff;
            border: 1px solid #9ca3af; /* gray-400 */
            border-radius: 6px;
            line-height: normal;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: #4f46e5; /* indigo-600 */
            box-shadow: 0 0 0 1px #4f46e5;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 14px;
            background-color: #111827; /* gray-900 */
            border: 1px solid #111827;
            border-radius: 6px;
            font-weight: 600;
            color: #ffffff;
            font-size: 14px;
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

        .btn-secondary {
            background-color: #ffffff;
            border: 1px solid #9ca3af; /* gray-400 */
            color: #111827;
        }

        .btn-secondary:hover {
            background-color: #f3f4f6; /* gray-100 */
        }
    </style>
</head>

<body>

    <div class="container">

        <h1>Crear cliente</h1>

        <div class="card">

            @if ($errors->any())
                <div class="error-box">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('admin.clients.store') }}"
            >

                @csrf

                <div class="field">
                    <label for="name">
                        Nombre
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        maxlength="255"
                    >
                </div>

                <div class="field">
                    <label for="document">
                        Documento
                    </label>

                    <input
                        type="number"
                        id="document"
                        name="document"
                        value="{{ old('document') }}"
                        min="1"
                        required
                    >
                </div>

                <div class="actions">
                    <button type="submit" class="btn">
                        Crear cliente
                    </button>

                    <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>

            </form>

        </div>

    </div>

</body>
</html>