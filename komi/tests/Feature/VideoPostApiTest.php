<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VideoPostApiTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('youtubeUrlsProvider')]
    public function test_store_accepts_youtube_urls(string $url, string $expectedId): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Mira este video',
            'type' => 'video',
            'link_url' => $url,
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'video')
            ->assertJsonPath('data.video_provider', 'youtube')
            ->assertJsonPath('data.video_id', $expectedId)
            ->assertJsonPath('data.link_url', $url);
    }

    public static function youtubeUrlsProvider(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'short url' => ['https://youtu.be/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'shorts' => ['https://www.youtube.com/shorts/abcDEF12345', 'abcDEF12345'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
        ];
    }

    public function test_store_accepts_vimeo_urls(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Corto de Vimeo',
            'type' => 'video',
            'link_url' => 'https://vimeo.com/76979871',
        ])
            ->assertCreated()
            ->assertJsonPath('data.video_provider', 'vimeo')
            ->assertJsonPath('data.video_id', '76979871');
    }

    public function test_store_rejects_unsupported_video_urls(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Esto no es video',
            'type' => 'video',
            'link_url' => 'https://example.com/archivo.mp4',
        ])->assertStatus(422);
    }

    public function test_video_without_url_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Video sin url',
            'type' => 'video',
        ])->assertStatus(422);
    }

    public function test_plain_link_posts_keep_link_type_and_persist_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Un enlace interesante',
            'link_url' => 'https://example.com/articulo',
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'link')
            ->assertJsonPath('data.link_url', 'https://example.com/articulo')
            ->assertJsonPath('data.video_provider', null);
    }
}
