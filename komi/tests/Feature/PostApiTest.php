<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_index_requires_authentication(): void
    {
        $this->getJson('/api/posts')->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_the_feed(): void
    {
        $user = User::factory()->create();
        Post::create([
            'user_id' => $user->id,
            'content' => 'Hola Komi',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Hola Komi')
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonPath('data.0.is_liked', false)
            ->assertJsonPath('data.0.is_liked_by_me', false)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'content',
                        'media_url',
                        'likes_count',
                        'comments_count',
                        'is_liked',
                        'is_liked_by_me',
                        'created_at',
                        'created_at_human',
                        'user' => ['id', 'name', 'username', 'avatar_url', 'is_verified'],
                        'community',
                    ],
                ],
            ]);
    }

    public function test_authenticated_user_can_create_a_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', ['content' => 'Mi primer post'])
            ->assertCreated()
            ->assertJsonPath('data.content', 'Mi primer post')
            ->assertJsonPath('data.is_liked_by_me', false);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Mi primer post',
            'likes_count' => 0,
            'comments_count' => 0,
        ]);
    }

    public function test_store_requires_valid_content(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/posts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }

    public function test_toggle_like_adds_and_removes_a_reaction(): void
    {
        $user = User::factory()->create();
        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Dale like',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('liked', false)
            ->assertJsonPath('likes_count', 0);

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }
}
