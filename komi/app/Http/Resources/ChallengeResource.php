<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'community_id' => $this->community_id,
            'title' => $this->title,
            'description' => $this->description,
            'deadline' => $this->deadline?->toISOString(),
            'is_completed' => (bool) $this->is_completed,
            'participants_count' => $this->whenCounted('participants'),
            'joined_by_me' => $this->joined_by_me ?? false,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
