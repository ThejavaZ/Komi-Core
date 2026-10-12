<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Enviar un mensaje a una conversacion (solo participantes).
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse|MessageResource
    {
        $me = $request->user();

        if (! $conversation->isParticipant($me)) {
            return response()->json(['message' => 'No formas parte de esta conversación.'], 403);
        }

        // En conversaciones directas, no enviar si hay bloqueo con el otro.
        // withoutGlobalScope: el scope ocultaria al bloqueado y $other seria
        // null, saltandose la comprobacion.
        if ($conversation->type === Conversation::TYPE_DIRECT) {
            $other = $conversation->users()
                ->withoutGlobalScope(\App\Models\Scopes\HideBlockedUsersScope::class)
                ->where('user_id', '!=', $me->id)
                ->first();

            if ($other && $me->hasBlockConflictWith($other)) {
                return response()->json(['message' => 'No puedes enviar mensajes a este usuario.'], 403);
            }
        }

        $message = DB::transaction(function () use ($request, $conversation, $me): Message {
            $message = $conversation->messages()->create([
                'user_id' => $me->id,
                'content' => $request->validated('content'),
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            return $message;
        });

        $message->load('user');

        // Notificar y emitir en tiempo real a cada participante que no sea el emisor.
        $recipients = $conversation->users()
            ->where('user_id', '!=', $me->id)
            ->get();

        foreach ($recipients as $recipient) {
            \App\Models\AppNotification::notifyMessage(
                $recipient,
                $me,
                $conversation,
                $message,
            );

            event(new \App\Events\MessageSent($message, $conversation, $recipient));
        }

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    /**
     * Emite en tiempo real que el usuario esta escribiendo en una conversacion.
     */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $me = $request->user();

        if (! $conversation->isParticipant($me)) {
            return response()->json(['message' => 'No formas parte de esta conversación.'], 403);
        }

        $recipients = $conversation->users()
            ->where('user_id', '!=', $me->id)
            ->get();

        foreach ($recipients as $recipient) {
            event(new \App\Events\UserTyping($conversation->id, $me, $recipient));
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Eliminar un mensaje propio (solo el autor).
     */
    public function destroy(Request $request, Message $message): JsonResponse
    {
        if ($message->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Solo puedes eliminar tus propios mensajes.'], 403);
        }

        DB::transaction(function () use ($message): void {
            $conversation = $message->conversation;
            $message->delete();

            // Actualizar last_message_at si se elimino el ultimo mensaje
            if ($conversation) {
                $last = $conversation->messages()->latest('id')->first();
                $conversation->update(['last_message_at' => $last?->created_at]);
            }
        });

        return response()->json(['status' => 'success']);
    }
}
