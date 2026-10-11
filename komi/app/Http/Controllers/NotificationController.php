<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * Listar notificaciones del usuario autenticado.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 20), 50);

        $notifications = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->with('fromUser:id,name,username,avatar,is_verified')
            ->with('comment:id,content')
            ->latest()
            ->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    /**
     * Contador de notificaciones no leidas.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'status' => 'success',
            'unread_count' => $count,
        ]);
    }

    /**
     * Marcar una notificacion como leida.
     */
    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No es tu notificación.'], 403);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Marcar todas como leidas.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'success']);
    }
}
