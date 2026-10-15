<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'type' => $this->type ?? 'text',
            'content' => $this->content,
            'media_url' => $this->media_url,
            'media_full_url' => $this->media_url ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->media_url) : null,
            'sticker_meta' => $this->sticker_meta,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'avatar_url' => $this->user->avatar,
                'is_verified' => (bool) $this->user->is_verified,
            ] : null),
        ];
    }
}
