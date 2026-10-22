<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AudioUploadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_can_be_created_with_audio_note(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'type' => 'text']);
        Sanctum::actingAs($user);

        $audio = UploadedFile::fake()->create('nota.m4a', 120, 'audio/mp4');

        $response = $this->post("/api/posts/{$post->id}/comments", [
            'audio' => $audio,
        ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $this->assertNotNull($response->json('data.audio_url'));
        $this->assertStringStartsWith('comments-audio/', $response->json('data.audio_url'));
    }

    public function test_comment_without_audio_still_requires_content(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'type' => 'text']);
        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/comments", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_post_can_be_created_with_audio_track(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $audio = UploadedFile::fake()->create('pista.mp3', 400, 'audio/mpeg');

        $response = $this->post('/api/posts', [
            'content' => 'Mi primera pista de fondo',
            'type' => 'text',
            'audio' => $audio,
        ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $this->assertStringStartsWith('posts-audio/', $response->json('data.audio_url'));
    }

    public function test_oversized_audio_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // 6MB > 5MB máximo para audio de posts.
        $audio = UploadedFile::fake()->create('larga.mp3', 6144, 'audio/mpeg');

        $this->post('/api/posts', [
            'content' => 'Pista gigante',
            'audio' => $audio,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['audio']);
    }

    public function test_comment_audio_exposed_in_comment_resource(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'type' => 'text']);
        Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => null,
            'audio_url' => 'comments-audio/nota.m4a',
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.audio_url', 'comments-audio/nota.m4a');
    }
}
