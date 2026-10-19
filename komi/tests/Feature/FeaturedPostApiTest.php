<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeaturedPostApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Community}
     */
    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Komi News',
            'slug' => 'komi-news-' . $owner->id,
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_community_owner_can_feature_and_unfeature_a_post(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        $author = User::factory()->create();
        $community->members()->attach($author->id, ['role' => 'member']);

        $post = $author->posts()->create([
            'content' => 'Noticia destacable',
            'community_id' => $community->id,
        ]);

        Sanctum::actingAs($owner);

        $this->postJson("/api/posts/{$post->id}/feature")
            ->assertOk()
            ->assertJsonPath('is_featured', true);

        $this->assertTrue($post->refresh()->is_featured);

        $this->postJson("/api/posts/{$post->id}/feature")
            ->assertOk()
            ->assertJsonPath('is_featured', false);

        $this->assertFalse($post->refresh()->is_featured);
    }

    public function test_plain_member_cannot_feature_a_post(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        $post = $owner->posts()->create([
            'content' => 'Post del owner',
            'community_id' => $community->id,
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/posts/{$post->id}/feature")->assertForbidden();
        $this->assertFalse($post->refresh()->is_featured);
    }

    public function test_non_member_cannot_feature_a_global_post(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create(['content' => 'Post global']);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/posts/{$post->id}/feature")->assertForbidden();
    }

    public function test_global_admin_can_feature_any_post(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create(['content' => 'Post global']);

        Sanctum::actingAs(User::factory()->create(['is_global_admin' => true]));

        $this->postJson("/api/posts/{$post->id}/feature")
            ->assertOk()
            ->assertJsonPath('is_featured', true);
    }

    public function test_feed_puts_featured_posts_first(): void
    {
        $user = User::factory()->create();

        $old = $user->posts()->create(['content' => 'Viejo pero destacado']);
        $old->forceFill(['is_featured' => true])->save();

        $new = $user->posts()->create(['content' => 'Nuevo normal']);

        Sanctum::actingAs($user);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $old->id)
            ->assertJsonPath('data.0.is_featured', true)
            ->assertJsonPath('data.1.id', $new->id);
    }

    public function test_resource_exposes_can_feature_for_moderators(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        $author = User::factory()->create();
        $community->members()->attach($author->id, ['role' => 'member']);

        $post = $author->posts()->create([
            'content' => 'Post en comunidad',
            'community_id' => $community->id,
        ]);

        Sanctum::actingAs($owner);
        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.can_feature', true);

        Sanctum::actingAs($author);
        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.can_feature', false)
            ->assertJsonPath('data.is_featured', false);
    }
}
