<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'community_id' => $this->community_id,
            'name' => $this->name,
            'color' => $this->color,
            'posts_count' => $this->whenCounted('posts'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
