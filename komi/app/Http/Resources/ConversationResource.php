<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user();

        $otherUser = null;
        if ($this->type === 'direct' && $me) {
            $other = $this->users->firstWhere('id', '!=', $me->id);

            if ($other) {
                $otherUser = [
                    'id' => $other->id,
                    'name' => $other->name,
                    'username' => $other->username,
                    'avatar_url' => $other->avatar,
                    'is_verified' => (bool) $other->is_verified,
                ];
            }
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'display_name' => $this->title ?? $otherUser['name'] ?? 'Chat',
            'last_message_at' => $this->last_message_at?->toISOString(),
            'unread_count' => $this->when(isset($this->unread_count), (int) $this->unread_count, 0),
            'other_user' => $otherUser,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'last_message' => new MessageResource($this->whenLoaded('lastMessage')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
