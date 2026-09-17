<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rate limiting para evitar fuerza bruta / abuso de OTP
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Obtener el perfil del usuario autenticado actualmente
    Route::get('/me', function (Request $request) {
        return new UserResource($request->user());
    });

    // Cerrar sesión de manera segura
    Route::post('/logout', [AuthController::class, 'logout']);

    // Feed de publicaciones
    Route::get('/posts', [PostController::class, 'index']);

    // Crear una publicación
    Route::post('/posts', [PostController::class, 'store']);

    // Dar / quitar like a una publicación
    Route::post('/posts/{post}/like', [PostController::class, 'toggleLike']);

    // Listar comentarios de una publicación
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);

    // Crear un comentario (soporta respuestas anidadas con parent_id)
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);

    // Actualizar el perfil del usuario autenticado
    Route::put('/user/profile', [UserController::class, 'updateProfile']);

});
