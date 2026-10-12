<?php

namespace Tests\Feature;

use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint2CosmeticsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCosmetic(User $seller, string $cosmeticType, array $overrides = []): StoreItem
    {
        return $seller->storeItems()->create(array_merge([
            'title' => 'Marco Dorado',
            'type' => StoreItem::TYPE_ITEM,
            'cosmetic_type' => $cosmeticType,
            'price' => 50,
            'preview_data' => ['border' => ['#FFD700', '#FFA500'], 'text' => 'Leyenda'],
            'status' => StoreItem::STATUS_ACTIVE,
        ], $overrides));
    }

    private function purchase(User $buyer, StoreItem $item): void
    {
        $buyer->increment('coins', $item->price);
        $this->actingAs($buyer)->postJson("/api/store/items/{$item->id}/purchase")->assertCreated();
    }

    public function test_user_can_apply_purchased_frame(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create(['coins' => 0]);
        $frame = $this->makeCosmetic($seller, StoreItem::COSMETIC_FRAME);
        $this->purchase($buyer, $frame);

        Sanctum::actingAs($buyer);

        $this->postJson("/api/store/items/{$frame->id}/apply")
            ->assertOk()
            ->assertJsonPath('data.active_frame.id', $frame->id)
            ->assertJsonPath('data.active_frame.cosmetic_type', 'frame');

        $this->assertSame($frame->id, $buyer->fresh()->active_frame_id);
    }

    public function test_cannot_apply_unpurchased_item(): void
    {
        $seller = User::factory()->create();
        $user = User::factory()->create(['coins' => 0]);
        $frame = $this->makeCosmetic($seller, StoreItem::COSMETIC_FRAME);

        Sanctum::actingAs($user);

        $this->postJson("/api/store/items/{$frame->id}/apply")->assertStatus(403);
        $this->assertNull($user->fresh()->active_frame_id);
    }

    public function test_cannot_apply_non_cosmetic_item(): void
    {
        $seller = User::factory()->create();
        $user = User::factory()->create(['coins' => 0]);
        $plain = $seller->storeItems()->create([
            'title' => 'Item normal',
            'type' => StoreItem::TYPE_ITEM,
            'cosmetic_type' => null,
            'price' => 10,
            'status' => StoreItem::STATUS_ACTIVE,
        ]);
        $this->purchase($user, $plain);

        Sanctum::actingAs($user);

        $this->postJson("/api/store/items/{$plain->id}/apply")->assertStatus(422);
    }

    public function test_apply_title_sets_custom_title(): void
    {
        $seller = User::factory()->create();
        $user = User::factory()->create(['coins' => 0]);
        $title = $this->makeCosmetic($seller, StoreItem::COSMETIC_TITLE, [
            'title' => 'Titulo de tienda',
            'preview_data' => ['text' => 'El Elegido'],
        ]);
        $this->purchase($user, $title);

        Sanctum::actingAs($user);

        $this->postJson("/api/store/items/{$title->id}/apply")
            ->assertOk()
            ->assertJsonPath('data.custom_title', 'El Elegido');

        $this->assertSame('El Elegido', $user->fresh()->custom_title);
    }

    public function test_my_cosmetics_lists_purchased_cosmetics(): void
    {
        $seller = User::factory()->create();
        $user = User::factory()->create(['coins' => 0]);
        $owned = $this->makeCosmetic($seller, StoreItem::COSMETIC_FRAME, ['title' => 'Comprado']);
        $notOwned = $this->makeCosmetic($seller, StoreItem::COSMETIC_FRAME, ['title' => 'No comprado']);
        $this->purchase($user, $owned);

        Sanctum::actingAs($user);

        $this->getJson('/api/me/cosmetics')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $owned->id)
            ->assertJsonPath('data.0.cosmetic_type', 'frame');
    }

    public function test_active_cosmetics_visible_on_other_profile(): void
    {
        $seller = User::factory()->create();
        $owner = User::factory()->create(['coins' => 0, 'custom_title' => 'Pro']);
        $frame = $this->makeCosmetic($seller, StoreItem::COSMETIC_FRAME);
        $owner->forceFill(['active_frame_id' => $frame->id])->save();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/users/{$owner->username}")
            ->assertOk()
            ->assertJsonPath('data.custom_title', 'Pro')
            ->assertJsonPath('data.active_frame.id', $frame->id);
    }
}
