<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'read_at' => $this->read_at?->toISOString(),
            'is_read' => $this->read_at !== null,
            'post_id' => $this->post_id,
            'comment_id' => $this->comment_id,
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'from_user' => $this->whenLoaded('fromUser', fn () => $this->fromUser ? [
                'id' => $this->fromUser->id,
                'name' => $this->fromUser->name,
                'username' => $this->fromUser->username,
                'avatar_url' => $this->fromUser->avatar,
                'is_verified' => (bool) $this->fromUser->is_verified,
            ] : null),
            'comment_preview' => $this->when(
                $this->type === 'comment' && $this->comment,
                fn () => \Str::limit($this->comment->content, 80),
            ),
        ];
    }
}
