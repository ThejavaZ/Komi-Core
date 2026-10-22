<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda - Komi</title>
    <meta name="description" content="Cosméticos, temas y monedas de Komi.">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 text-white min-h-screen antialiased">
    <nav class="max-w-4xl mx-auto px-6 py-6 flex items-center gap-3">
        <a href="/" class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-lg shadow-lg shadow-indigo-500/20">K</a>
        <span class="text-xl font-bold tracking-tight">Komi</span>
    </nav>

    <main class="max-w-3xl mx-auto px-6 py-10">
        <h1 class="text-4xl font-bold mb-2">Tienda de Komi</h1>
        <p class="text-gray-400 mb-10">Personaliza tu perfil con monedas. Todo es opcional y puramente cosmético.</p>

        <div class="grid sm:grid-cols-2 gap-6">
            <div class="rounded-2xl border border-gray-800 bg-gray-900/60 p-6">
                <div class="text-3xl mb-3">🪙</div>
                <h2 class="text-xl font-semibold mb-2">Monedas</h2>
                <p class="text-gray-400 text-sm mb-4">Paquetes de monedas para gastar en la tienda. Se compran desde la app (Google Play / App Store).</p>
                <p class="text-indigo-400 text-sm font-medium">Disponibles en la app</p>
            </div>

            <div class="rounded-2xl border border-gray-800 bg-gray-900/60 p-6">
                <div class="text-3xl mb-3">💬</div>
                <h2 class="text-xl font-semibold mb-2">Burbujas de chat</h2>
                <p class="text-gray-400 text-sm mb-4">Moldes personalizados para tus globos de chat: redondo, pill o cuadrado.</p>
                <p class="text-indigo-400 text-sm font-medium">Desde 50 monedas</p>
            </div>

            <div class="rounded-2xl border border-gray-800 bg-gray-900/60 p-6">
                <div class="text-3xl mb-3">🎨</div>
                <h2 class="text-xl font-semibold mb-2">Temas y colores</h2>
                <p class="text-gray-400 text-sm mb-4">Colores de acento y temas personalizados para tu perfil.</p>
                <p class="text-indigo-400 text-sm font-medium">Próximamente</p>
            </div>

            <div class="rounded-2xl border border-gray-800 bg-gray-900/60 p-6">
                <div class="text-3xl mb-3">⭐</div>
                <h2 class="text-xl font-semibold mb-2">Placas y títulos</h2>
                <p class="text-gray-400 text-sm mb-4">Placas especiales y títulos personalizados en tu perfil.</p>
                <p class="text-indigo-400 text-sm font-medium">Próximamente</p>
            </div>
        </div>

        <div class="mt-12 rounded-2xl border border-amber-500/20 bg-amber-500/5 p-6">
            <h2 class="text-lg font-semibold mb-2 text-amber-400">Aviso</h2>
            <p class="text-gray-400 text-sm">Las monedas de Komi no tienen valor monetario real y no se pueden canjear por dinero. Las compras se gestionan a través de la tienda de tu plataforma (Google Play / App Store) y están sujetas a sus términos.</p>
        </div>
    </main>

    <footer class="border-t border-gray-800 mt-16 py-8 text-center text-gray-500 text-sm">
        © {{ date('Y') }} Komi · <a href="/privacy" class="hover:text-gray-300">Política de Privacidad</a>
    </footer>
</body>
</html>
