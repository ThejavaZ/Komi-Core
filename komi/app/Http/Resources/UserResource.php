<?php

namespace App\Http\Resources;

use App\Support\Xp;
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
            'coins' => $this->when($request->user()?->id === $this->id, fn () => $this->coins),
            'xp' => (int) $this->xp,
            'level' => Xp::levelFor((int) $this->xp),
            'level_name' => Xp::levelName(Xp::levelFor((int) $this->xp)),
            'xp_progress' => Xp::progress((int) $this->xp),
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'banner' => $this->banner,
            'theme_color' => $this->theme_color,
            'gender' => $this->gender,
            'custom_title' => $this->custom_title,
            'badges' => BadgeResource::collection($this->whenLoaded('badges')),
            'active_frame' => $this->when($this->active_frame_id !== null, fn () => [
                'id' => $this->activeFrame->id,
                'cosmetic_type' => $this->activeFrame->cosmetic_type,
                'preview_data' => $this->activeFrame->preview_data,
            ]),
            'active_chat_theme' => $this->when($this->active_chat_theme_id !== null, fn () => [
                'id' => $this->activeChatTheme->id,
                'cosmetic_type' => $this->activeChatTheme->cosmetic_type,
                'preview_data' => $this->activeChatTheme->preview_data,
            ]),
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
