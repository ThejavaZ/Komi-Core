<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint3ChatMediaTest extends TestCase
{
    use RefreshDatabase;

    private function createDirectConversation(User $a, User $b): Conversation
    {
        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$a->id, $b->id]);

        return $conversation;
    }

    public function test_user_can_send_image_message(): void
    {
        Storage::fake('public');
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);

        $response = $this->post("/api/conversations/{$conversation->id}/messages", [
            'type' => 'image',
            'image' => UploadedFile::fake()->image('chat.png'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'image')
            ->assertJsonPath('data.content', null);

        $this->assertNotNull($response->json('data.media_url'));
        $this->assertStringStartsWith('messages/', $response->json('data.media_url'));
        $this->assertNotNull($response->json('data.media_full_url'));
        Storage::disk('public')->assertExists($response->json('data.media_url'));
    }

    public function test_message_requires_content_or_media(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);

        $this->post("/api/conversations/{$conversation->id}/messages", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_user_can_send_sticker_with_metadata(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);

        $this->post("/api/conversations/{$conversation->id}/messages", [
            'type' => 'sticker',
            'sticker_meta' => ['pack' => 'komi-basics', 'emoji' => '🎉'],
        ])->assertCreated()
            ->assertJsonPath('data.type', 'sticker')
            ->assertJsonPath('data.sticker_meta.pack', 'komi-basics')
            ->assertJsonPath('data.sticker_meta.emoji', '🎉');
    }

    public function test_user_can_send_gif_via_image_field(): void
    {
        Storage::fake('public');
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);

        // Un GIF subido via 'image' se clasifica como imagen pero conserva el gif.
        $this->post("/api/conversations/{$conversation->id}/messages", [
            'type' => 'gif',
            'image' => UploadedFile::fake()->image('fun.gif'),
        ])->assertCreated()
            ->assertJsonPath('data.type', 'image');
    }

    public function test_non_participant_cannot_send_media(): void
    {
        Storage::fake('public');
        $me = User::factory()->create();
        $other = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($outsider);

        $this->post("/api/conversations/{$conversation->id}/messages", [
            'type' => 'image',
            'image' => UploadedFile::fake()->image('hack.png'),
        ])->assertForbidden();
    }
}
