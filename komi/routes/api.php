<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\UserResource;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {

    // Obtener el perfil del usuario autenticado actualmente
    Route::get('/me', function (Request $request) {
        return new UserResource($request->user());
    });

    // Cerrar sesión de manera segura
    Route::post('/logout', [AuthController::class, 'logout']);

});
