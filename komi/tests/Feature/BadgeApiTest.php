<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BadgeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_first_post_awards_first_post_badge(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', ['content' => 'Hola Komi'])->assertCreated();

        $this->assertTrue($user->fresh()->badges->contains('key', 'first_post'));
    }

    public function test_creating_community_awards_founder_badge(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/communities', [
            'name' => 'Mi Comunidad Fundadora',
        ])->assertCreated();

        $this->assertTrue(
            $user->fresh()->badges->contains('key', 'community_founder')
        );
    }

    public function test_tenth_like_on_a_post_awards_author_popular_post(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $post = $author->posts()->create([
            'content' => 'Post a punto de viralizarse',
            'likes_count' => 9,
        ]);

        Sanctum::actingAs($liker);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('likes_count', 10);

        $this->assertTrue(
            $author->fresh()->badges->contains('key', 'popular_post')
        );
    }

    public function test_tenth_comment_awards_commenter_badge(): void
    {
        $user = User::factory()->create();
        $post = User::factory()->create()->posts()->create([
            'content' => 'Post ajeno para comentar',
        ]);

        Sanctum::actingAs($user);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/posts/{$post->id}/comments", [
                'content' => "Comentario número {$i}",
            ])->assertCreated();
        }

        $this->assertTrue($user->fresh()->badges->contains('key', 'commenter'));
    }

    public function test_badges_are_exposed_in_public_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/posts', ['content' => 'Primer post'])->assertCreated();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $response = $this->getJson("/api/users/{$user->username}")
            ->assertOk();

        $keys = array_column($response->json('data.badges') ?? [], 'key');
        $this->assertContains('first_post', $keys);
    }
}
