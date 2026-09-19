<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/admin');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        if (session('2fa_pending')) {
            return $this->verify2FA($request);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Las credenciales no son correctas.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if (!$user->is_global_admin) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'No tienes permisos de administrador.',
            ])->onlyInput('email');
        }

        $twoFactor = $user->twoFactor;
        if ($twoFactor && $twoFactor->enabled) {
            session(['2fa_pending' => true, '2fa_user_id' => $user->id]);
            Auth::logout();

            return redirect()->route('admin.2fa.show');
        }

        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }

    public function show2FALogin()
    {
        if (!session('2fa_pending')) {
            return redirect()->route('admin.login');
        }

        return view('admin.2fa');
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('2fa_user_id');

        if (!$userId) {
            return redirect()->route('admin.login');
        }

        $user = \App\Models\User::findOrFail($userId);
        Auth::login($user);

        $twoFactor = $user->twoFactor;
        if (!$twoFactor || !$twoFactor->enabled) {
            $request->session()->regenerate();
            session()->forget(['2fa_pending', '2fa_user_id']);
            return redirect()->intended('/admin');
        }

        $google2fa = new Google2FA();
        $secret = Crypt::decryptString($twoFactor->secret);
        $valid = $google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            Auth::logout();
            return back()->withErrors([
                'code' => 'Código de verificación inválido.',
            ])->onlyInput('code');
        }

        session()->forget(['2fa_pending', '2fa_user_id']);
        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
