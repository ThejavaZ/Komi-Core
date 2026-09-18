<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RepostController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rate limiting para evitar fuerza bruta / abuso de OTP
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);

// Inicio de sesion social unificado (Google, Facebook, Twitter) via token OAuth2
Route::middleware('throttle:5,1')->post('/auth/social-login', [SocialAuthController::class, 'socialLogin']);

// Telemetria: recibe errores del cliente (Flutter), deduplicados por error_hash.
Route::middleware('throttle:30,1')->post('/telemetry/logs', [TelemetryController::class, 'store']);

// Tags trending (publico)
Route::get('/tags/trending', [TagController::class, 'trending']);

Route::middleware('auth:sanctum')->group(function () {

    // Obtener el perfil del usuario autenticado actualmente
    Route::get('/me', function (Request $request) {
        return new UserResource($request->user());
    });

    // Cerrar sesion de manera segura
    Route::post('/logout', [AuthController::class, 'logout']);

    // Feed de publicaciones
    Route::get('/posts', [PostController::class, 'index']);

    // Crear una publicacion
    Route::post('/posts', [PostController::class, 'store']);

    // Dar / quitar like a una publicacion
    Route::post('/posts/{post}/like', [PostController::class, 'toggleLike']);

    // Upvote / Downvote / Remove vote
    Route::post('/posts/{post}/vote', [VoteController::class, 'vote']);

    // Toggle bookmark (guardar publicacion)
    Route::post('/posts/{post}/bookmark', [BookmarkController::class, 'toggle']);

    // Republicar una publicacion
    Route::post('/posts/{post}/repost', [RepostController::class, 'repost']);

    // Eliminar una publicacion (solo el propietario, soft delete)
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);

    // Listar comentarios de una publicacion
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);

    // Crear un comentario (soporta respuestas anidadas con parent_id)
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);

    // Votar en una encuesta
    Route::post('/posts/{post}/poll/vote', [PollController::class, 'vote']);

    // Posts guardados del usuario
    Route::get('/me/bookmarks', [BookmarkController::class, 'index']);

    // Actualizar el perfil del usuario autenticado
    Route::put('/user/profile', [UserController::class, 'updateProfile']);

});
