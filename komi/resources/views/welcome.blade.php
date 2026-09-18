<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komi - Conecta con tu comunidad</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 text-white min-h-screen antialiased">

    <!-- Nav -->
    <nav class="max-w-6xl mx-auto px-6 py-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-lg shadow-lg shadow-indigo-500/20">
                K
            </div>
            <span class="text-xl font-bold tracking-tight">Komi</span>
        </div>
    </nav>

    <!-- Hero -->
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/15 via-transparent to-purple-600/15"></div>
        <div class="relative max-w-4xl mx-auto px-6 py-28 text-center">
            <div class="inline-block text-sm font-medium text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 rounded-full px-4 py-1.5 mb-8">
                Disponible para Android
            </div>
            <h1 class="text-5xl sm:text-6xl font-bold tracking-tight leading-tight mb-6">
                Conecta con<br/>tu comunidad
            </h1>
            <p class="text-lg text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                Komi es una red social donde puedes crear y unirte a comunidades, compartir publicaciones con imágenes, encuestas interactivas, y debatir con personas que comparten tus intereses.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a
                    href="/"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-8 py-3.5 rounded-xl transition-all shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40"
                >
                    Listo para empezar
                </a>
                <a
                    href="#features"
                    class="border border-gray-700 hover:border-gray-500 text-gray-300 hover:text-white font-semibold px-8 py-3.5 rounded-xl transition-colors"
                >
                    Conoce más
                </a>
            </div>
        </div>
    </section>

    <!-- Stats bar -->
    <section class="border-y border-gray-800/60">
        <div class="max-w-5xl mx-auto px-6 py-10 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <p class="text-3xl font-bold text-white">+</p>
                <p class="text-sm text-gray-500 mt-1">Comunidades</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-white">+</p>
                <p class="text-sm text-gray-500 mt-1">Publicaciones</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-white">+</p>
                <p class="text-sm text-gray-500 mt-1">Usuarios activos</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-white">24/7</p>
                <p class="text-sm text-gray-500 mt-1">Disponible siempre</p>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="max-w-6xl mx-auto px-6 py-24">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold mb-4">Todo lo que necesitas en un solo lugar</h2>
            <p class="text-gray-400 max-w-xl mx-auto">Herramientas diseñadas para que la conversación fluya y tu voz sea escuchada.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-900/60 border border-gray-800 rounded-2xl p-7 hover:border-gray-700 transition-colors">
                <div class="w-11 h-11 rounded-xl bg-indigo-500/10 flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-lg mb-2">Comunidades</h3>
                <p class="text-sm text-gray-400 leading-relaxed">Crea o únete a comunidades sobre cualquier tema. Comparte contenido con personas que piensan como tú.</p>
            </div>

            <div class="bg-gray-900/60 border border-gray-800 rounded-2xl p-7 hover:border-gray-700 transition-colors">
                <div class="w-11 h-11 rounded-xl bg-green-500/10 flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-lg mb-2">Encuestas</h3>
                <p class="text-sm text-gray-400 leading-relaxed">Crea encuestas con múltiples opciones y descubre qué piensa la comunidad en tiempo real.</p>
            </div>

            <div class="bg-gray-900/60 border border-gray-800 rounded-2xl p-7 hover:border-gray-700 transition-colors">
                <div class="w-11 h-11 rounded-xl bg-orange-500/10 flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-lg mb-2">Votos Reddit-style</h3>
                <p class="text-sm text-gray-400 leading-relaxed">Upvote o downvote las publicaciones. El contenido con mejor puntuación sube al top.</p>
            </div>

            <div class="bg-gray-900/60 border border-gray-800 rounded-2xl p-7 hover:border-gray-700 transition-colors">
                <div class="w-11 h-11 rounded-xl bg-purple-500/10 flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-lg mb-2">Reposts</h3>
                <p class="text-sm text-gray-400 leading-relaxed">Republica contenido que te guste. Puedes hacer un repost rápido o agregar tu propio comentario.</p>
            </div>

            <div class="bg-gray-900/60 border border-gray-800 rounded-2xl p-7 hover:border-gray-700 transition-colors">
                <div class="w-11 h-11 rounded-xl bg-pink-500/10 flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-lg mb-2">Hashtags</h3>
                <p class="text-sm text-gray-400 leading-relaxed">Etiqueta tus publicaciones con hasta 5 hashtags para que otros usuarios descubran tu contenido.</p>
            </div>

            <div class="bg-gray-900/60 border border-gray-800 rounded-2xl p-7 hover:border-gray-700 transition-colors">
                <div class="w-11 h-11 rounded-xl bg-cyan-500/10 flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-lg mb-2">Seguro y Privado</h3>
                <p class="text-sm text-gray-400 leading-relaxed">Autenticación segura, inicio de sesión con Google, Facebook y Twitter. Tu cuenta está protegida.</p>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="border-t border-gray-800/60">
        <div class="max-w-5xl mx-auto px-6 py-24">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold mb-4">¿Cómo funciona?</h2>
                <p class="text-gray-400">En pocos pasos estás listo para empezar a conectar.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center mx-auto mb-5">
                        <span class="text-indigo-400 font-bold text-lg">1</span>
                    </div>
                    <h3 class="font-semibold mb-2">Descarga la app</h3>
                    <p class="text-sm text-gray-400">Disponible para Android. Descarga Komi desde tu tienda de aplicaciones.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center mx-auto mb-5">
                        <span class="text-indigo-400 font-bold text-lg">2</span>
                    </div>
                    <h3 class="font-semibold mb-2">Crea tu cuenta</h3>
                    <p class="text-sm text-gray-400">Regístrate con tu correo o usa tu cuenta de Google, Facebook o Twitter.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center mx-auto mb-5">
                        <span class="text-indigo-400 font-bold text-lg">3</span>
                    </div>
                    <h3 class="font-semibold mb-2">Comparte y conecta</h3>
                    <p class="text-sm text-gray-400">Únete a comunidades, publica, comenta, vota y construye conversaciones que importan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="border-t border-gray-800/60">
        <div class="max-w-4xl mx-auto px-6 py-24 text-center">
            <h2 class="text-3xl font-bold mb-4">¿Listo para empezar?</h2>
            <p class="text-gray-400 mb-10 max-w-lg mx-auto">Únete a Komi y forma parte de una comunidad que crece cada día.</p>
            <a
                href="/"
                class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-10 py-3.5 rounded-xl transition-all shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40"
            >
                Listo para empezar
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-gray-800/60 py-8">
        <div class="max-w-5xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-xs">K</div>
                <span class="text-sm text-gray-500">Komi &copy; {{ date('Y') }}</span>
            </div>
        </div>
    </footer>

</body>
</html>
