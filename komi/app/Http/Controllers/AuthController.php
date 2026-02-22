<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(){
        return view('auth.login');
    }

    public function store(Request $request){
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $data['email'])->where('is_active',1)->first();

        $remember = $request->has('remember');

        if($user && Hash::check($data['password'], $user->password)){
            Auth::login(($user), $remember);
            return redirect()->route('home');
        }
        return redirect()->back()->withErrors(['email' => 'Invalid credentials']);
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('login');
    }
}
