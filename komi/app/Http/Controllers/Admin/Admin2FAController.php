<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

class Admin2FAController extends Controller
{
    public function status(): JsonResponse
    {
        $user = auth()->user();
        $twoFactor = $user->twoFactor;

        return response()->json([
            'enabled' => $twoFactor && $twoFactor->enabled,
        ]);
    }

    public function setup(): JsonResponse
    {
        $user = auth()->user();
        $twoFactor = $user->twoFactor;

        if ($twoFactor && $twoFactor->enabled) {
            return response()->json(['message' => '2FA ya está habilitado.'], 400);
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        if ($twoFactor) {
            $twoFactor->update(['secret' => Crypt::encryptString($secret)]);
        } else {
            AdminTwoFactor::create([
                'user_id' => $user->id,
                'secret' => Crypt::encryptString($secret),
                'enabled' => false,
            ]);
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name') . ' Admin',
            $user->email,
            $secret
        );

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = auth()->user();
        $twoFactor = $user->twoFactor;

        if (!$twoFactor) {
            return response()->json(['message' => 'Primero ejecuta setup.'], 400);
        }

        $google2fa = new Google2FA();
        $secret = Crypt::decryptString($twoFactor->secret);

        $valid = $google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return response()->json(['message' => 'Código inválido.'], 422);
        }

        $twoFactor->update(['enabled' => true]);

        return response()->json(['message' => '2FA habilitado correctamente.']);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = auth()->user();
        $twoFactor = $user->twoFactor;

        if (!$twoFactor || !$twoFactor->enabled) {
            return response()->json(['message' => '2FA no está habilitado.'], 400);
        }

        $google2fa = new Google2FA();
        $secret = Crypt::decryptString($twoFactor->secret);

        $valid = $google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return response()->json(['message' => 'Código inválido.'], 422);
        }

        $twoFactor->update(['enabled' => false]);

        return response()->json(['message' => '2FA deshabilitado correctamente.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = auth()->user();
        $twoFactor = $user->twoFactor;

        if (!$twoFactor || !$twoFactor->enabled) {
            return response()->json(['verified' => true]);
        }

        $google2fa = new Google2FA();
        $secret = Crypt::decryptString($twoFactor->secret);

        $valid = $google2fa->verifyKey($secret, $request->input('code'));

        return response()->json(['verified' => $valid]);
    }
}
