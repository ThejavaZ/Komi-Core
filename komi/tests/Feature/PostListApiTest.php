<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostListApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_list_post_with_ordered_items(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Mis peliculas favoritas',
            'type' => 'list',
            'list_items' => ['Interestelarr', 'Blade Runner 2049', 'El origen'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'list')
            ->assertJsonPath('data.list_items.0.text', 'Interestelarr')
            ->assertJsonPath('data.list_items.0.position', 0)
            ->assertJsonPath('data.list_items.1.text', 'Blade Runner 2049')
            ->assertJsonPath('data.list_items.1.position', 1)
            ->assertJsonPath('data.list_items.2.text', 'El origen')
            ->assertJsonPath('data.list_items.2.position', 2);

        $post = $user->posts()->latest('id')->first();
        $this->assertSame(3, $post->listItems()->count());
    }

    public function test_list_items_auto_detect_the_list_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Top 3 de series',
            'list_items' => ['The Wire', 'Breaking Bad'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'list')
            ->assertJsonPath('data.list_items.0.text', 'The Wire');
    }

    public function test_list_items_are_eager_loaded_in_the_feed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Checklist',
            'type' => 'list',
            'list_items' => ['Uno', 'Dos'],
        ])->assertCreated();

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'list')
            ->assertJsonPath('data.0.list_items.0.text', 'Uno')
            ->assertJsonPath('data.0.list_items.1.text', 'Dos');
    }

    public function test_empty_list_item_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Lista rota',
            'type' => 'list',
            'list_items' => ['Valido', ''],
        ])->assertStatus(422);
    }

    public function test_max_fifty_list_items_enforced(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $items = array_fill(0, 51, 'Item');

        $this->postJson('/api/posts', [
            'content' => 'Lista enorme',
            'type' => 'list',
            'list_items' => $items,
        ])->assertStatus(422);
    }
}
