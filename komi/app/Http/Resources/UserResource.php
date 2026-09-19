<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $followsAuth = false;
        if ($request->user() && $request->user()->id !== $this->id) {
            $followsAuth = $request->user()->isFollowing($this->resource);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when($request->user()?->id === $this->id, fn () => $this->email),
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'banner' => $this->banner,
            'theme_color' => $this->theme_color,
            'gender' => $this->gender,
            'is_verified' => $this->is_verified,
            'is_premium' => $this->is_premium,
            'status' => $this->status,
            'followers_count' => $this->whenCounted('followers'),
            'following_count' => $this->whenCounted('following'),
            'posts_count' => $this->whenCounted('posts'),
            'is_following_by_me' => $followsAuth,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
