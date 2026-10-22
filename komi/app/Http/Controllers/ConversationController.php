<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Conversaciones del usuario autenticado, ordenadas por ultimo mensaje,
     * con contador de no leidos.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 30), 50);
        $me = $request->user();

        $conversations = Conversation::query()
            ->whereHas('users', fn ($query) => $query->whereKey($me->id))
            ->with(['users', 'lastMessage.user'])
            ->withCount(['messages as unread_count' => function ($query) use ($me) {
                $query->where('user_id', '!=', $me->id)->whereNull('read_at');
            }])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);

        return ConversationResource::collection($conversations);
    }

    /**
     * Crear (o reutilizar) una conversacion directa con otro usuario.
     */
    public function store(StoreConversationRequest $request): JsonResponse
    {
        $me = $request->user();
        $targetId = (int) $request->validated('user_id');

        // Reutilizar la conversacion directa existente entre ambos si ya hay una
        $existing = Conversation::query()
            ->where('type', Conversation::TYPE_DIRECT)
            ->whereHas('users', fn ($query) => $query->whereKey($me->id))
            ->whereHas('users', fn ($query) => $query->whereKey($targetId))
            ->with(['users', 'lastMessage.user'])
            ->get()
            ->first(fn ($conversation) => $conversation->users->count() === 2);

        if ($existing) {
            return (new ConversationResource($existing))->response();
        }

        $conversation = DB::transaction(function () use ($me, $targetId): Conversation {
            $conversation = Conversation::create(['type' => Conversation::TYPE_DIRECT]);
            $conversation->users()->attach([$me->id, $targetId]);

            return $conversation;
        });

        $conversation->load(['users', 'lastMessage.user']);

        return (new ConversationResource($conversation))->response()->setStatusCode(201);
    }

    /**
     * Detalle de una conversacion (solo participantes).
     */
    public function show(Request $request, Conversation $conversation): ConversationResource|JsonResponse
    {
        if (! $conversation->canBeAccessedBy($request->user())) {
            return response()->json(['message' => 'No formas parte de esta conversación.'], 403);
        }

        $conversation->load(['users', 'lastMessage.user']);

        return new ConversationResource($conversation);
    }

    /**
     * Abandonar una conversacion (se elimina al ultimo participante).
     */
    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $conversation->isParticipant($request->user())) {
            return response()->json(['message' => 'No formas parte de esta conversación.'], 403);
        }

        DB::transaction(function () use ($conversation, $request): void {
            $conversation->users()->detach($request->user()->id);

            if ($conversation->users()->count() === 0) {
                $conversation->delete();
            }
        });

        return response()->json(['status' => 'success']);
    }

    /**
     * Mensajes de la conversacion (pagina mas reciente primero).
     * Marca como leidos los mensajes entrantes al consultar.
     */
    public function messages(Request $request, Conversation $conversation): AnonymousResourceCollection|JsonResponse
    {
        if (! $conversation->canBeAccessedBy($request->user())) {
            return response()->json(['message' => 'No formas parte de esta conversación.'], 403);
        }

        $perPage = min((int) $request->input('per_page', 50), 100);

        // Marcar mensajes de otros como leidos
        $conversation->messages()
            ->where('user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('user')
            ->latest()
            ->paginate($perPage);

        return MessageResource::collection($messages);
    }

    /**
     * Crear una conversacion grupal con titulo y participantes.
     */
    public function storeGroup(Request $request): JsonResponse|ConversationResource
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'user_ids' => ['required', 'array', 'min:1', 'max:50'],
            'user_ids.*' => ['integer', 'exists:users,id', 'distinct'],
        ]);

        $me = $request->user();
        $memberIds = array_unique(array_merge([$me->id], array_map('intval', $data['user_ids'])));

        // No incluir usuarios bloqueados
        $memberIds = array_values(array_filter($memberIds, function ($userId) use ($me) {
            if ($userId === $me->id) {
                return true;
            }

            $other = User::find($userId);

            return $other && ! $me->isBlocking($other) && ! $other->isBlocking($me);
        }));

        if (count($memberIds) < 2) {
            return response()->json(['message' => 'No hay participantes válidos para el grupo.'], 422);
        }

        $conversation = DB::transaction(function () use ($data, $memberIds): Conversation {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_GROUP,
                'title' => $data['title'],
            ]);
            $conversation->users()->attach($memberIds);

            return $conversation;
        });

        $conversation->load(['users', 'lastMessage.user']);

        return (new ConversationResource($conversation))->response()->setStatusCode(201);
    }
}
