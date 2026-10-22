<?php

namespace App\Http\Resources;

use App\Support\Xp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Miembro de comunidad: usuario básico + datos del pivot
 * (rol y "placa"/título personal otorgado por el staff).
 */
class CommunityMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'is_verified' => $this->is_verified,
            'role' => $this->pivot?->role ?? 'member',
            'custom_title' => $this->pivot?->custom_title,
            'xp' => (int) ($this->xp ?? 0),
            'level' => Xp::levelFor((int) ($this->xp ?? 0)),
            'level_name' => Xp::levelName(
                Xp::levelFor((int) ($this->xp ?? 0))
            ),
        ];
    }
}
