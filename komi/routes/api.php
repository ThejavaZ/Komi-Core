<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
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
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

// Registro: 6/min + tope diario de cuentas por IP (anti-spam).
Route::middleware(['throttle:6,1', 'throttle:register-ip'])
    ->post('/register', [AuthController::class, 'register']);

// Reenvio del codigo OTP (3/min por correo).
Route::middleware('throttle:resend-otp')
    ->post('/resend-otp', [AuthController::class, 'resendOtp']);

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

    // Feed "Siguiendo" (debe ir antes de /posts/{post})
    Route::get('/posts/following', [PostController::class, 'following']);

    // Post individual (deep-linking)
    Route::get('/posts/{post}', [PostController::class, 'show']);

    // Crear una publicacion
    Route::post('/posts', [PostController::class, 'store'])->middleware('throttle:posts-write');

    // Editar una publicacion
    Route::put('/posts/{post}', [PostController::class, 'update']);

    // Dar / quitar like a una publicacion
    Route::post('/posts/{post}/like', [PostController::class, 'toggleLike'])->middleware('throttle:likes');

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

    // Gestion de comentarios (editar/borrar propios, likes)
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('/comments/{comment}/like', [CommentController::class, 'toggleLike']);

    // Busqueda unificada (usuarios, posts, comunidades, hashtags)
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index']);

    // Votar en una encuesta
    Route::post('/posts/{post}/poll/vote', [PollController::class, 'vote']);

    // Posts guardados del usuario
    Route::get('/me/bookmarks', [BookmarkController::class, 'index']);

    // Actualizar el perfil del usuario autenticado
    Route::put('/user/profile', [UserController::class, 'updateProfile']);

    // ─── Perfil de usuario ───────────────────────────────────
    Route::get('/users/search', [UserController::class, 'search']);
    // Debe ir antes de /users/{username}, si no casaria con el patron.
    Route::get('/users/blocked', [BlockController::class, 'blockedUsers']);
    Route::get('/users/{username}', [UserController::class, 'show']);
    Route::get('/users/{user}/posts', [UserController::class, 'userPosts']);

    // ─── Seguidores ───────────────────────────────────────────
    Route::post('/users/{user}/follow', [FollowerController::class, 'toggleFollow']);
    Route::get('/users/{user}/followers', [FollowerController::class, 'followers']);
    Route::get('/users/{user}/following', [FollowerController::class, 'following']);

    // ─── Bloqueo de usuarios ──────────────────────────────────
    Route::post('/users/{user}/block', [BlockController::class, 'toggleBlock']);

    // ─── Notificaciones ───────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // ─── Reportes ─────────────────────────────────────────────
    Route::post('/reports', [ReportController::class, 'store']);

    // ─── Chat / Mensajes ──────────────────────────────────────
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::post('/conversations/group', [ConversationController::class, 'storeGroup']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->middleware('throttle:messages');
    Route::post('/conversations/{conversation}/typing', [MessageController::class, 'typing'])->middleware('throttle:typing');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);

    // ─── Comunidades ─────────────────────────────────────────
    Route::get('/communities', [CommunityController::class, 'index']);
    Route::post('/communities', [CommunityController::class, 'store']);
    Route::get('/communities/{community}', [CommunityController::class, 'show']);
    Route::get('/communities/{community}/posts', [CommunityController::class, 'posts']);
    Route::put('/communities/{community}', [CommunityController::class, 'update']);
    Route::delete('/communities/{community}', [CommunityController::class, 'destroy']);
    Route::post('/communities/{community}/join', [CommunityController::class, 'join']);
    Route::post('/communities/{community}/leave', [CommunityController::class, 'leave']);
    Route::get('/communities/{community}/members', [CommunityController::class, 'members']);
    Route::delete('/communities/{community}/members/{user}', [CommunityController::class, 'removeMember']);

    // ─── Hashtags: posts por tag (slug) ─────────────────────
    Route::get('/tags/{slug}/posts', [TagController::class, 'posts']);

    // ─── Tienda ─────────────────────────────────────────────
    Route::get('/store', [\App\Http\Controllers\StoreController::class, 'index']);
    Route::get('/store/mine', [\App\Http\Controllers\StoreController::class, 'mine']);
    Route::post('/store/items', [\App\Http\Controllers\StoreController::class, 'store']);
    Route::post('/store/items/{item}/purchase', [\App\Http\Controllers\StoreController::class, 'purchase']);

});
