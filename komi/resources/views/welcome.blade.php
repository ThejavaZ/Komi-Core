<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komi - Plataforma Social</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 text-white min-h-screen antialiased">
    <!-- Hero -->
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 via-transparent to-purple-600/20"></div>
        <div class="relative max-w-5xl mx-auto px-6 py-24 text-center">
            <!-- Logo -->
            <div class="w-20 h-20 rounded-2xl bg-indigo-600 flex items-center justify-center font-bold text-4xl mx-auto mb-8 shadow-lg shadow-indigo-500/30">
                K
            </div>
            <h1 class="text-5xl font-bold tracking-tight mb-4">
                Komi
            </h1>
            <p class="text-xl text-gray-400 max-w-2xl mx-auto mb-12">
                Plataforma social moderna con comunidades, encuestas, votos y sistema de republicaciones.
            </p>

            <!-- CTA buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a
                    href="/admin/login"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-8 py-3 rounded-xl transition-colors shadow-lg shadow-indigo-500/25"
                >
                    Panel de Admin
                </a>
                <a
                    href="/api/tags/trending"
                    class="border border-gray-700 hover:border-gray-500 text-gray-300 hover:text-white font-semibold px-8 py-3 rounded-xl transition-colors"
                >
                    API Explorer
                </a>
            </div>
        </div>
    </div>

    <!-- Features -->
    <div class="max-w-5xl mx-auto px-6 pb-24">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gray-900/50 border border-gray-800 rounded-2xl p-6">
                <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Comunidades</h3>
                <p class="text-sm text-gray-400">Crea y únete a comunidades temáticas con usuarios de todo el mundo.</p>
            </div>

            <div class="bg-gray-900/50 border border-gray-800 rounded-2xl p-6">
                <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Encuestas y Votos</h3>
                <p class="text-sm text-gray-400">Crea encuestas interactivas y vota publicaciones al estilo Reddit.</p>
            </div>

            <div class="bg-gray-900/50 border border-gray-800 rounded-2xl p-6">
                <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Reposts</h3>
                <p class="text-sm text-gray-400">Republica contenido con o tu propio comentario integrado.</p>
            </div>
        </div>

        <!-- Tech stack -->
        <div class="mt-16 text-center">
            <p class="text-sm text-gray-500 mb-4">Desarrollado con</p>
            <div class="flex items-center justify-center gap-6 text-gray-600">
                <span class="text-sm font-medium">Laravel 13</span>
                <span class="text-gray-700">&middot;</span>
                <span class="text-sm font-medium">Flutter</span>
                <span class="text-gray-700">&middot;</span>
                <span class="text-sm font-medium">Vue 3</span>
                <span class="text-gray-700">&middot;</span>
                <span class="text-sm font-medium">Tailwind CSS</span>
            </div>
        </div>
    </div>
</body>
</html>
