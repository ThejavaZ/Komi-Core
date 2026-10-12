<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Limiters de seguridad para endpoints criticos (spam / bots).
     *
     * Usa la cache configurada (CACHE_STORE). Con CACHE_STORE=redis el
     * almacenamiento pasa a Redis sin tocar codigo.
     */
    private function configureRateLimiting(): void
    {
        // Crear publicaciones: 10/min por usuario.
        RateLimiter::for('posts-write', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
        });

        // Dar/quitar likes: 30/min por usuario.
        RateLimiter::for('likes', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?? $request->ip());
        });

        // Enviar mensajes: 30/min por usuario dentro de cada conversacion.
        RateLimiter::for('messages', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();
            $conversationId = $request->route('conversation')?->id ?? 'none';

            return Limit::perMinute(30)->by($userId.'|'.$conversationId);
        });

        // Registro de cuentas: tope diario por IP (complementa el 6/min).
        RateLimiter::for('register-ip', function (Request $request) {
            return Limit::perDay(5)->by($request->ip());
        });

        // Reenvio de OTP: 3/min por correo.
        RateLimiter::for('resend-otp', function (Request $request) {
            return Limit::perMinute(3)->by(
                'resend-otp|'.($request->input('email') ?? $request->ip())
            );
        });
    }
}
