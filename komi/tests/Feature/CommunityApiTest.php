<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Flutter Devs',
            'slug' => 'flutter-devs',
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_community_posts_requires_authentication(): void
    {
        [, $community] = $this->createCommunityWithOwner();

        $this->getJson("/api/communities/{$community->id}/posts")->assertUnauthorized();
    }

    public function test_can_list_community_posts(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Post::create([
            'user_id' => $owner->id,
            'community_id' => $community->id,
            'content' => 'Post de la comunidad',
        ]);
        Post::create([
            'user_id' => $owner->id,
            'content' => 'Post sin comunidad',
        ]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/communities/{$community->id}/posts")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Post de la comunidad')
            ->assertJsonPath('data.0.community.id', $community->id);
    }

    public function test_creating_post_in_community_as_member_succeeds(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Sanctum::actingAs($owner);

        $this->postJson('/api/posts', [
            'content' => 'Publicado en la comunidad',
            'community_id' => $community->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.community.id', $community->id);
    }

    public function test_creating_post_in_community_as_non_member_is_forbidden(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $outsider = User::factory()->create();

        Sanctum::actingAs($outsider);

        $this->postJson('/api/posts', [
            'content' => 'Intento de intruso',
            'community_id' => $community->id,
        ])->assertForbidden();
    }
}
