<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AmaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_can_close_and_reopen_ama(): void
    {
        $host = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $host->id,
            'type' => 'ama',
            'ama_status' => 'open',
        ]);
        Sanctum::actingAs($host);

        $this->putJson("/api/posts/{$post->id}/ama-status", ['ama_status' => 'closed'])
            ->assertOk()
            ->assertJsonPath('ama_status', 'closed');

        $this->putJson("/api/posts/{$post->id}/ama-status", ['ama_status' => 'open'])
            ->assertOk()
            ->assertJsonPath('ama_status', 'open');
    }

    public function test_non_host_cannot_close_ama(): void
    {
        $host = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $host->id,
            'type' => 'ama',
            'ama_status' => 'open',
        ]);
        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $this->putJson("/api/posts/{$post->id}/ama-status", ['ama_status' => 'closed'])
            ->assertForbidden();
    }

    public function test_ama_status_rejected_on_non_ama_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'type' => 'text',
        ]);
        Sanctum::actingAs($author);

        $this->putJson("/api/posts/{$post->id}/ama-status", ['ama_status' => 'closed'])
            ->assertUnprocessable();
    }

    public function test_ama_post_created_with_open_status(): void
    {
        $host = User::factory()->create();
        Sanctum::actingAs($host);

        $response = $this->postJson('/api/posts', [
            'content' => 'Pregúntame lo que quieras sobre Flutter',
            'type' => 'ama',
        ])->assertCreated();

        $this->assertSame('ama', $response->json('data.type'));
        $this->assertSame('open', $response->json('data.ama_status'));
    }

    public function test_host_can_mark_question_as_answered(): void
    {
        $host = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $host->id,
            'type' => 'ama',
            'ama_status' => 'open',
        ]);
        $asker = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $asker->id,
            'post_id' => $post->id,
            'content' => '¿Cuál es tu framework favorito?',
        ]);
        Sanctum::actingAs($host);

        $this->postJson("/api/comments/{$comment->id}/answer")
            ->assertOk()
            ->assertJsonPath('is_answered', true);

        // Toggle: se desmarca.
        $this->postJson("/api/comments/{$comment->id}/answer")
            ->assertOk()
            ->assertJsonPath('is_answered', false);
    }

    public function test_non_host_cannot_mark_answered(): void
    {
        $host = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $host->id,
            'type' => 'ama',
        ]);
        $comment = Comment::factory()->create([
            'user_id' => $host->id,
            'post_id' => $post->id,
        ]);
        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $this->postJson("/api/comments/{$comment->id}/answer")
            ->assertForbidden();
    }

    public function test_mark_answered_rejected_on_non_ama_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'type' => 'text',
        ]);
        $comment = Comment::factory()->create([
            'user_id' => $author->id,
            'post_id' => $post->id,
        ]);
        Sanctum::actingAs($author);

        $this->postJson("/api/comments/{$comment->id}/answer")
            ->assertUnprocessable();
    }
}
