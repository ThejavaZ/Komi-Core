<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReportController;
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

    // Editar una publicacion
    Route::put('/posts/{post}', [PostController::class, 'update']);

    // Dar / quitar like a una publicacion
    Route::post('/posts/{post}/like', [PostController::class, 'toggleLike']);

    // Toggle pin/unpin a publicacion
    Route::post('/posts/{post}/pin', [PostController::class, 'togglePin']);

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

    // ─── Perfil de usuario ───────────────────────────────────
    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/users/{username}', [UserController::class, 'show']);
    Route::get('/users/{user}/posts', [UserController::class, 'userPosts']);

    // ─── Seguidores ───────────────────────────────────────────
    Route::post('/users/{user}/follow', [FollowerController::class, 'toggleFollow']);
    Route::get('/users/{user}/followers', [FollowerController::class, 'followers']);
    Route::get('/users/{user}/following', [FollowerController::class, 'following']);

    // ─── Bloqueo de usuarios ──────────────────────────────────
    Route::post('/users/{user}/block', [BlockController::class, 'toggleBlock']);
    Route::get('/users/blocked', [BlockController::class, 'blockedUsers']);

    // ─── Reportes ─────────────────────────────────────────────
    Route::post('/reports', [ReportController::class, 'store']);

    // ─── Comunidades ─────────────────────────────────────────
    Route::get('/communities', [CommunityController::class, 'index']);
    Route::post('/communities', [CommunityController::class, 'store']);
    Route::get('/communities/{community}', [CommunityController::class, 'show']);
    Route::put('/communities/{community}', [CommunityController::class, 'update']);
    Route::delete('/communities/{community}', [CommunityController::class, 'destroy']);
    Route::post('/communities/{community}/join', [CommunityController::class, 'join']);
    Route::post('/communities/{community}/leave', [CommunityController::class, 'leave']);
    Route::get('/communities/{community}/members', [CommunityController::class, 'members']);
    Route::delete('/communities/{community}/members/{user}', [CommunityController::class, 'removeMember']);

});
