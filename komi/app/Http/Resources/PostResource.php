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
        $deleted = $this->trashed();
        $isLiked = ! $deleted && (bool) ($this->is_liked_by_me ?? false);

        return [
            'id' => $this->id,
            // Si el post fue eliminado (soft-delete), no exponemos su contenido.
            'content' => $deleted ? 'Publicación no disponible' : $this->content,
            'media_url' => $deleted ? null : $this->image_url,
            'image_url' => $deleted ? null : $this->image_url,
            'likes_count' => $deleted ? 0 : (int) $this->likes_count,
            'comments_count' => $deleted ? 0 : (int) ($this->comments_count ?? 0),
            'is_liked' => $isLiked,
            'is_liked_by_me' => $isLiked,
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'community' => $this->whenLoaded('community', fn () => $this->community ? [
                'id' => $this->community->id,
                'name' => $this->community->name,
                'slug' => $this->community->slug,
            ] : null),
            'deleted' => $deleted,
            // Anonimizamos al autor cuando el post fue eliminado.
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $deleted ? 'Usuario eliminado' : $this->user->name,
                'username' => $deleted ? 'usuario_eliminado' : $this->user->username,
                'avatar_url' => $deleted ? null : $this->user->avatar,
                'is_verified' => $deleted ? false : (bool) $this->user->is_verified,
            ]),
        ];
    }
}
