<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\CommunityCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Community}
     */
    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Komi Flairs',
            'slug' => 'komi-flairs-' . $owner->id,
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_anyone_can_list_community_categories(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $community->categories()->create(['name' => 'Fanart']);
        $community->categories()->create(['name' => 'Meme', 'color' => '#ff0000']);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/communities/{$community->id}/categories")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Fanart');
    }

    public function test_owner_and_admin_can_create_categories(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Sanctum::actingAs($owner);
        $this->postJson("/api/communities/{$community->id}/categories", [
            'name' => 'Teorías',
            'color' => '#00aa88',
        ])->assertCreated()
            ->assertJsonPath('category.name', 'Teorías');

        $admin = User::factory()->create();
        $community->members()->attach($admin->id, ['role' => 'admin']);

        Sanctum::actingAs($admin);
        $this->postJson("/api/communities/{$community->id}/categories", [
            'name' => 'Noticias',
        ])->assertCreated();

        $this->assertDatabaseHas('community_categories', [
            'community_id' => $community->id,
            'name' => 'Noticias',
        ]);
    }

    public function test_plain_member_cannot_create_or_delete_categories(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $category = $community->categories()->create(['name' => 'Oficial']);

        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($member);

        $this->postJson("/api/communities/{$community->id}/categories", ['name' => 'Spam'])
            ->assertForbidden();

        $this->deleteJson("/api/communities/{$community->id}/categories/{$category->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('community_categories', ['id' => $category->id]);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $community->categories()->create(['name' => 'Fanart']);

        Sanctum::actingAs($owner);

        $this->postJson("/api/communities/{$community->id}/categories", ['name' => 'Fanart'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ya existe una categoría con ese nombre.');
    }

    public function test_post_can_be_created_with_a_category_of_its_community(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $category = $community->categories()->create(['name' => 'Guías', 'color' => '#3366ff']);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/posts', [
            'content' => 'Guía de personajes',
            'community_id' => $community->id,
            'community_category_id' => $category->id,
        ])->assertCreated()
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.category.name', 'Guías');
    }

    public function test_post_cannot_use_a_category_from_another_community(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        $other = Community::create([
            'name' => 'Otra',
            'slug' => 'otra-' . $owner->id,
            'owner_id' => $owner->id,
        ]);
        $foreignCategory = $other->categories()->create(['name' => 'Ajena']);

        Sanctum::actingAs($owner);

        $this->postJson('/api/posts', [
            'content' => 'Post con categoría ajena',
            'community_id' => $community->id,
            'community_category_id' => $foreignCategory->id,
        ])->assertStatus(422);
    }

    public function test_community_feed_can_be_filtered_by_category(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $fanart = $community->categories()->create(['name' => 'Fanart']);
        $memes = $community->categories()->create(['name' => 'Memes']);

        $a = $owner->posts()->create([
            'content' => 'Dibujo',
            'community_id' => $community->id,
            'community_category_id' => $fanart->id,
        ]);
        $owner->posts()->create([
            'content' => 'Meme',
            'community_id' => $community->id,
            'community_category_id' => $memes->id,
        ]);
        $owner->posts()->create([
            'content' => 'Sin categoría',
            'community_id' => $community->id,
        ]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/communities/{$community->id}/posts?category_id={$fanart->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $a->id)
            ->assertJsonPath('data.0.category.name', 'Fanart');
    }

    public function test_owner_can_update_and_delete_category(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $category = $community->categories()->create(['name' => 'Viejo']);

        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/categories/{$category->id}", [
            'name' => 'Nuevo',
        ])->assertOk()
            ->assertJsonPath('category.name', 'Nuevo');

        $this->deleteJson("/api/communities/{$community->id}/categories/{$category->id}")
            ->assertOk();

        $this->assertDatabaseMissing('community_categories', ['id' => $category->id]);
    }
}
