<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;


class AuthController extends Controller
{
    /**
     * Registro
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'pending',
        ]);

        // 🎲 1. Generamos un código aleatorio de 6 dígitos criptográficamente seguro
        $otpCode = random_int(100000, 999999);

        // ⏱️ 2. Lo guardamos en Cache asociado al correo por 10 minutos (600 segundos)
        // ⚡ CORREGIDO: Ahora usa la variable correcta $otpCode y coincide con los 10 minutos de expiración
        Cache::put('otp_' . $user->email, $otpCode, now()->addMinutes(10));

        // 📧 3. Enviamos el correo real a través de Mailpit
        // ⚡ CORREGIDO: Enviamos la variable correcta $otpCode al constructor de tu Mailable
        Mail::to($user->email)->send(new SendOtpMail($otpCode));

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario registrado con éxito. Código de verificación enviado al correo.',
            'data' => [
                'email' => $user->email,
                'debug_otp' => $otpCode // Lo dejamos aquí por si necesitas verlo en tu consola de Flutter sin abrir Mailpit
            ]
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        // 🔍 1. Recuperamos el código que guardamos en Cache para este correo
        $cachedOtp = Cache::get('otp_' . $request->email);

        // 2. Si no hay código en caché, es porque ya expiró (pasaron más de 10 mins)
        if (!$cachedOtp) {
            return response()->json([
                'status' => 'error',
                'message' => 'El código de verificación ha expirado o no existe. Solicita uno nuevo.'
            ], 400);
        }

        // 3. Comparamos el código que envió el usuario con el de la Cache
        if ($request->code !== (string) $cachedOtp) {
            return response()->json([
                'status' => 'error',
                'message' => 'El código de verificación es incorrecto.'
            ], 400);
        }

        // 4. Si es correcto, activamos al usuario
        $user->update([
            'status' => 'active',
            'email_verified_at' => now(),
            'is_verified' => true,
        ]);

        // 🧹 Limpiamos la caché del código ya usado para que no se pueda reutilizar
        Cache::forget('otp_' . $request->email);

        // 5. Emitimos token de acceso
        $token = $user->createToken('komi_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => '¡Cuenta verificada y activada con éxito!',
            'token' => $token,
            'user' => new UserResource($user)
        ], 200);
    }

    /**
     * Login
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Las credenciales no coinciden con nuestros registros.'
            ], 401);
        }

        // 🚫 VALIDACIÓN CLAVE: Bloquear accesos si está suspendido o baneado
        if ($user->status === 'banned') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu cuenta ha sido permanentemente baneada de Komi.'
            ], 403);
        }

        if ($user->status === 'suspended') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu cuenta se encuentra temporalmente suspendida.'
            ], 403);
        }

        // Si tu login requiere que esté 'active' (ya verificado por OTP)
        if ($user->status === 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Por favor, verifica tu cuenta primero.'
            ], 403);
        }

        // Generar token normal si todo está en orden
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Cerrar Sesión (Revocar Token)
     */
    public function logout(Request $request)
    {
        // $request->user() nos da el usuario autenticado gracias al middleware de Sanctum
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada con éxito. Token revocado.'
        ], 200);
    }
}
