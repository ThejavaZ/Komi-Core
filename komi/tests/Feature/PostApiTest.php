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

    public function test_delete_post_requires_authentication(): void
    {
        $post = Post::create([
            'user_id' => User::factory()->create()->id,
            'content' => 'Post a eliminar',
        ]);

        $this->deleteJson("/api/posts/{$post->id}")->assertUnauthorized();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'deleted_at' => null,
        ]);
    }

    public function test_owner_can_soft_delete_their_post(): void
    {
        $user = User::factory()->create();
        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Adiós Komi',
            'likes_count' => 3,
            'comments_count' => 1,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_non_owner_cannot_delete_a_post(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $post = Post::create([
            'user_id' => $owner->id,
            'content' => 'Post ajeno',
        ]);

        Sanctum::actingAs($intruder);

        $this->deleteJson("/api/posts/{$post->id}")->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'deleted_at' => null,
        ]);
    }

    public function test_feed_masks_deleted_posts(): void
    {
        $user = User::factory()->create();
        $deleted = Post::create([
            'user_id' => $user->id,
            'content' => 'Contenido secreto',
        ]);
        $deleted->delete();

        // Los posts eliminados no aparecen en el feed.
        $this->assertDatabaseMissing('posts', [
            'id' => $deleted->id,
            'deleted_at' => null,
        ]);
    }
}
