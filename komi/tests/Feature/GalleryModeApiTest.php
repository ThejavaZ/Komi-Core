<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostImages;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GalleryModeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_mode_returns_only_posts_with_images(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Post::factory()->create(['user_id' => $user->id, 'type' => 'text', 'image_url' => null]);
        $withImage = Post::factory()->create(['user_id' => $user->id, 'type' => 'image', 'image_url' => 'posts/foto.jpg']);
        $withGallery = Post::factory()->create(['user_id' => $user->id, 'type' => 'text', 'image_url' => null]);
        PostImages::create(['post_id' => $withGallery->id, 'url' => 'posts/galeria.jpg', 'position' => 0]);

        $response = $this->getJson('/api/posts?mode=gallery')->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertCount(2, $ids);
        $this->assertTrue($ids->contains($withImage->id));
        $this->assertTrue($ids->contains($withGallery->id));
    }

    public function test_default_mode_includes_all_posts(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Post::factory()->create(['user_id' => $user->id, 'type' => 'text']);
        Post::factory()->create(['user_id' => $user->id, 'type' => 'image', 'image_url' => 'posts/a.jpg']);

        $response = $this->getJson('/api/posts')->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
