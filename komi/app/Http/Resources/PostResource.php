<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isLiked = (bool) ($this->is_liked_by_me ?? false);

        return [
            'id' => $this->id,
            'content' => $this->content,
            'media_url' => $this->image_url,
            'image_url' => $this->image_url,
            'likes_count' => (int) $this->likes_count,
            'comments_count' => (int) ($this->comments_count ?? 0),
            'is_liked' => $isLiked,
            'is_liked_by_me' => $isLiked,
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'avatar_url' => $this->user->avatar,
                'is_verified' => (bool) $this->user->is_verified,
            ]),
            'community' => $this->whenLoaded('community', fn () => $this->community ? [
                'id' => $this->community->id,
                'name' => $this->community->name,
                'slug' => $this->community->slug,
            ] : null),
        ];
    }
}
