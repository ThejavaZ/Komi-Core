<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'avatar' => $this->avatar,
            'banner' => $this->banner,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'members_count' => $this->whenCounted('members'),
            'posts_count' => $this->whenCounted('posts'),
            'is_member' => $this->when(
                $request->user(),
                fn () => $this->members->contains('id', $request->user()->id)
            ),
            'user_role' => $this->when(
                $request->user(),
                function () use ($request) {
                    $member = $this->members->firstWhere('id', $request->user()->id);
                    return $member?->pivot?->role;
                }
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
