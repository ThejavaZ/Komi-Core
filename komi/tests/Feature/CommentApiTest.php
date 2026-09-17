<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_comments_index_requires_authentication(): void
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'content' => 'Mi post']);

        $this->getJson("/api/posts/{$post->id}/comments")->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_comments_with_author(): void
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'content' => 'Mi post']);
        Comment::create(['user_id' => $user->id, 'post_id' => $post->id, 'content' => 'Mi comentario']);

        Sanctum::actingAs($user);

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Mi comentario')
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'content',
                    'post_id',
                    'parent_id',
                    'created_at',
                    'created_at_human',
                    'user' => ['id', 'name', 'username', 'avatar_url', 'is_verified'],
                ]],
            ]);
    }

    public function test_authenticated_user_can_create_comment_and_increments_count(): void
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'content' => 'Mi post']);

        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Primer comentario'])
            ->assertCreated()
            ->assertJsonPath('data.content', 'Primer comentario');

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Primer comentario',
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'comments_count' => 1]);
    }

    public function test_authenticated_user_can_reply_to_a_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'content' => 'Mi post']);
        $parent = Comment::create(['user_id' => $user->id, 'post_id' => $post->id, 'content' => 'Padre']);

        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Respuesta',
            'parent_id' => $parent->id,
        ])->assertCreated()
            ->assertJsonPath('data.parent_id', $parent->id);

        $this->assertDatabaseHas('comments', [
            'id' => Comment::query()->where('content', 'Respuesta')->first()->id,
            'parent_id' => $parent->id,
        ]);
    }

    public function test_comment_parent_must_belong_to_the_same_post(): void
    {
        $user = User::factory()->create();
        $postOne = Post::create(['user_id' => $user->id, 'content' => 'Post 1']);
        $postTwo = Post::create(['user_id' => $user->id, 'content' => 'Post 2']);
        $foreignParent = Comment::create(['user_id' => $user->id, 'post_id' => $postTwo->id, 'content' => 'Padre ajeno']);

        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$postOne->id}/comments", [
            'content' => 'Intento',
            'parent_id' => $foreignParent->id,
        ])->assertStatus(422);

        $this->assertDatabaseCount('comments', 1);
    }
}
