<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use App\Models\Community;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CommunityRoomController extends Controller
{
    /**
     * Lista las salas de chat de una comunidad (solo miembros).
     */
    public function index(Request $request, Community $community): AnonymousResourceCollection|JsonResponse
    {
        $me = $request->user();

        if (! $community->members()->where('users.id', $me->id)->exists()) {
            return response()->json(['message' => 'Debes ser miembro de la comunidad.'], 403);
        }

        $rooms = $community->chatRooms()
            ->with('users:id,name,username,avatar')
            ->withCount('users')
            ->with('lastMessage')
            ->orderByDesc('last_message_at')
            ->get()
            ->each(function ($room) use ($me) {
                $room->setAttribute('is_joined', $room->users->contains('id', $me->id));
                $room->setAttribute('members_count', $room->users_count);
            });

        return ConversationResource::collection($rooms);
    }

    /**
     * Crea una sala oficial de la comunidad (solo owner o admin).
     */
    public function store(Request $request, Community $community): ConversationResource|JsonResponse
    {
        $me = $request->user();

        $member = $community->members()->where('users.id', $me->id)->first();
        if (! $member || ! in_array($member->pivot->role, ['owner', 'admin'], true)) {
            return response()->json(['message' => 'Solo owner o admin pueden crear salas.'], 403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
        ]);

        $room = DB::transaction(function () use ($data, $community, $me): Conversation {
            $room = Conversation::create([
                'type' => Conversation::TYPE_ROOM,
                'title' => $data['title'],
                'community_id' => $community->id,
            ]);

            $room->users()->attach($me->id);

            return $room;
        });

        $room->load('users:id,name,username,avatar');

        return new ConversationResource($room);
    }

    /**
     * El usuario se une a una sala de su comunidad (debe ser miembro).
     */
    public function join(Request $request, Conversation $room): JsonResponse
    {
        $me = $request->user();

        if (! $room->isRoom() || ! $room->community_id) {
            return response()->json(['message' => 'Esta conversación no es una sala de comunidad.'], 422);
        }

        if ($room->isParticipant($me)) {
            return response()->json(['message' => 'Ya formas parte de esta sala.'], 400);
        }

        $community = $room->community;
        if (! $community || ! $community->members()->where('users.id', $me->id)->exists()) {
            return response()->json(['message' => 'Debes ser miembro de la comunidad.'], 403);
        }

        $room->users()->attach($me->id);

        return response()->json([
            'status' => 'success',
            'members_count' => $room->users()->count(),
        ]);
    }

    /**
     * El usuario sale de una sala de su comunidad.
     */
    public function leave(Request $request, Conversation $room): JsonResponse
    {
        $me = $request->user();

        if (! $room->isRoom()) {
            return response()->json(['message' => 'Esta conversación no es una sala de comunidad.'], 422);
        }

        if (! $room->isParticipant($me)) {
            return response()->json(['message' => 'No formas parte de esta sala.'], 400);
        }

        $room->users()->detach($me->id);

        return response()->json([
            'status' => 'success',
            'members_count' => $room->users()->count(),
        ]);
    }
}
