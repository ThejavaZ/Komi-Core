<?php

namespace Tests\Feature;

use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint4StoreTest extends TestCase
{
    use RefreshDatabase;

    private function makeItem(User $seller, array $overrides = []): StoreItem
    {
        return $seller->storeItems()->create(array_merge([
            'title' => 'Cyberpunk Neon',
            'description' => 'Tema neon para tu chat',
            'type' => StoreItem::TYPE_TEMPLATE,
            'price' => 100,
            'preview_data' => ['color1' => '#0D0D0D', 'color2' => '#00FF41'],
            'status' => StoreItem::STATUS_ACTIVE,
        ], $overrides));
    }

    public function test_store_requires_authentication(): void
    {
        $this->getJson('/api/store')->assertUnauthorized();
        $this->postJson('/api/store/items', [])->assertUnauthorized();
    }

    public function test_user_can_list_active_catalog(): void
    {
        $seller = User::factory()->create();
        $this->makeItem($seller, ['title' => 'Item Activo']);
        $this->makeItem($seller, ['title' => 'Item Archivado', 'status' => StoreItem::STATUS_ARCHIVED]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/store')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Item Activo')
            ->assertJsonPath('data.0.seller.id', $seller->id);
    }

    public function test_user_can_create_store_item(): void
    {
        $seller = User::factory()->create();
        Sanctum::actingAs($seller);

        $this->postJson('/api/store/items', [
            'title' => 'Dracula Theme',
            'description' => 'Tema oscuro',
            'type' => 'template',
            'price' => 80,
            'preview_data' => ['color1' => '#282A36'],
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Dracula Theme')
            ->assertJsonPath('data.seller.id', $seller->id);

        $this->assertDatabaseHas('store_items', ['seller_id' => $seller->id, 'title' => 'Dracula Theme']);
    }

    public function test_user_can_list_their_own_items(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->makeItem($me, ['title' => 'Mio']);
        $this->makeItem($other, ['title' => 'Ajeno']);

        Sanctum::actingAs($me);

        $this->getJson('/api/store/mine')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mio');
    }

    public function test_user_can_purchase_item_transferring_coins(): void
    {
        $seller = User::factory()->create(['coins' => 0]);
        $buyer = User::factory()->create(['coins' => 100]);
        $item = $this->makeItem($seller, ['price' => 30]);

        Sanctum::actingAs($buyer);

        $this->postJson("/api/store/items/{$item->id}/purchase")
            ->assertCreated()
            ->assertJsonPath('data.price_paid', 30)
            ->assertJsonPath('data.coins_remaining', 70);

        $this->assertSame(70, $buyer->fresh()->coins);
        $this->assertSame(30, $seller->fresh()->coins);
        $this->assertDatabaseHas('store_purchases', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'price_paid' => 30,
        ]);
    }

    public function test_purchase_fails_with_insufficient_coins(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create(['coins' => 10]);
        $item = $this->makeItem($seller, ['price' => 50]);

        Sanctum::actingAs($buyer);

        $this->postJson("/api/store/items/{$item->id}/purchase")
            ->assertStatus(402);

        $this->assertSame(10, $buyer->fresh()->coins);
        $this->assertDatabaseCount('store_purchases', 0);
    }

    public function test_cannot_purchase_own_item(): void
    {
        $seller = User::factory()->create(['coins' => 100]);
        $item = $this->makeItem($seller, ['price' => 10]);

        Sanctum::actingAs($seller);

        $this->postJson("/api/store/items/{$item->id}/purchase")->assertStatus(422);
    }

    public function test_cannot_purchase_archived_item(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create(['coins' => 100]);
        $item = $this->makeItem($seller, ['price' => 10, 'status' => StoreItem::STATUS_ARCHIVED]);

        Sanctum::actingAs($buyer);

        $this->postJson("/api/store/items/{$item->id}/purchase")->assertStatus(422);
    }

    public function test_create_item_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/store/items', ['price' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'price']);
    }
}
