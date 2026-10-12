<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'price' => $this->price,
            'preview_data' => $this->preview_data,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
                'username' => $this->seller->username,
                'avatar_url' => $this->seller->avatar,
            ]),
        ];
    }
}
