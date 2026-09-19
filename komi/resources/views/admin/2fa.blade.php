<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komi Admin - Verificación 2FA</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center antialiased">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-2xl text-white mx-auto mb-3">
                K
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Verificación en Dos Pasos</h1>
            <p class="text-sm text-gray-500 mt-1">Ingresa el código de tu aplicador de autenticación</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-5">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf

                <div class="mb-6">
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Código de 6 dígitos</label>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        required
                        autofocus
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-center text-2xl tracking-[0.5em] font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        placeholder="000000"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Verificar
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('admin.login') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    Volver al login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
