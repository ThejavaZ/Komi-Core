<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialAuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Inicio de sesión unificado con proveedores OAuth2 (Google, Facebook, Twitter).
     *
     * Valida el token del proveedor a través de Socialite, busca el usuario por
     * provider_id o email y lo crea si no existe (con el email verificado por el
     * proveedor). Devuelve el token de Sanctum en el mismo formato que /api/login.
     */
    public function socialLogin(SocialAuthRequest $request)
    {
        $provider = $request->provider;
        $token = $request->token;

        try {
            $socialiteUser = Socialite::driver($provider)->userFromToken($token);
        } catch (\Exception $e) {
            Log::warning('Social login token inválido.', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'El token del proveedor es inválido o ha expirado.',
            ], 401);
        }

        $email = $socialiteUser->getEmail();
        $providerId = (string) $socialiteUser->getId();

        if (! $email) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo obtener el correo del proveedor.',
            ], 422);
        }

        // 🔍 1. Buscamos por provider_id (vínculo social) o por email para
        // permitir que quien ya se registró con su correo pueda entrar social.
        $user = User::where('provider_id', $providerId)->first()
            ?? User::where('email', $email)->first();

        // 🚫 2. Bloqueamos cuentas suspendidas o baneadas (misma regla que /api/login)
        if ($user && in_array($user->status, ['banned', 'suspended'])) {
            return response()->json([
                'status' => 'error',
                'message' => $user->status === 'banned'
                    ? 'Tu cuenta ha sido permanentemente baneada de Komi.'
                    : 'Tu cuenta se encuentra temporalmente suspendida.',
            ], 403);
        }

        if (! $user) {
            // 🆕 3. Registro: creamos la cuenta con datos del proveedor y el
            // email ya verificado (el proveedor lo confirmó al emitir el token).
            $user = User::create([
                'name' => $socialiteUser->getName() ?? $this->fallbackName($email),
                'username' => $this->uniqueUsername($email),
                'email' => $email,
                'password' => Str::random(40), // Sin contraseña: solo acceso social
                'avatar' => $socialiteUser->getAvatar(),
                'provider' => $provider,
                'provider_id' => $providerId,
                'email_verified_at' => now(),
                'status' => 'active',
            ]);
        } else {
            // 🔗 4. Login: vinculamos el proveedor la primera vez que entra así.
            $user->forceFill([
                'provider' => $provider,
                'provider_id' => $providerId,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        // 5. Emitimos el token de acceso (mismo token name que /api/login)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Nombre por defecto si el proveedor no lo facilita.
     */
    private function fallbackName(string $email): string
    {
        $name = ucfirst(Str::before($email, '@'));

        return $name ?: 'Usuario de Komi';
    }

    /**
     * Genera un @username único basado en el correo del usuario.
     */
    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '');
        $base = $base !== '' ? $base : 'usuario';

        $username = $base;
        $counter = 1;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $base.$counter;
            $counter++;
        }

        return $username;
    }
}
