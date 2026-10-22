<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use App\Models\Community;
use App\Models\CommunityUser;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Modmail: canal privado entre un miembro y el staff de una comunidad.
 *
 * - El miembro abre/reutiliza UN hilo por comunidad (POST).
 * - El staff lista todos los hilos de su comunidad (GET) y puede leer/escribir
 *   en ellos (los checks de acceso vía Conversation::canBeAccessedBy).
 */
class ModmailController extends Controller
{
    /**
     * Abre (o reutiliza) el hilo de modmail del usuario con la comunidad.
     */
    public function open(Request $request, Community $community): JsonResponse
    {
        $me = $request->user();

        $isMember = $community->members()->where('users.id', $me->id)->exists();
        if (! $isMember) {
            return response()->json(['message' => 'Debes ser miembro de la comunidad para contactar al staff.'], 403);
        }

        $existing = Conversation::query()
            ->where('type', Conversation::TYPE_MODMAIL)
            ->where('community_id', $community->id)
            ->whereHas('users', fn ($query) => $query->whereKey($me->id))
            ->with(['users', 'lastMessage.user'])
            ->first();

        if ($existing) {
            return (new ConversationResource($existing))->response();
        }

        $conversation = DB::transaction(function () use ($community, $me): Conversation {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_MODMAIL,
                'community_id' => $community->id,
            ]);
            $conversation->users()->attach($me->id);

            return $conversation;
        });

        $conversation->load(['users', 'lastMessage.user']);

        return (new ConversationResource($conversation))->response()->setStatusCode(201);
    }

    /**
     * Hilos de modmail de la comunidad (solo staff), ordenados por actividad.
     */
    public function threads(Request $request, Community $community): AnonymousResourceCollection|JsonResponse
    {
        $role = CommunityUser::query()
            ->where('community_id', $community->id)
            ->where('user_id', $request->user()->id)
            ->value('role');

        if (! in_array($role, ['owner', 'admin'], true)) {
            return response()->json(['message' => 'No tienes permiso para ver el modmail.'], 403);
        }

        $conversations = Conversation::query()
            ->where('type', Conversation::TYPE_MODMAIL)
            ->where('community_id', $community->id)
            ->with(['users', 'lastMessage.user'])
            ->orderByDesc('last_message_at')
            ->get();

        return ConversationResource::collection($conversations);
    }
}
