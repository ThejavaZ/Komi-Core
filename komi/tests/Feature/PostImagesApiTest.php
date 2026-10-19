<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostImages;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostImagesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_post_with_multiple_images(): void
    {
        Storage::fake('public');
        $me = User::factory()->create();
        Sanctum::actingAs($me);

        $response = $this->post('/api/posts', [
            'content' => 'Galeria de prueba',
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.content', 'Galeria de prueba');

        $images = $response->json('data.images');
        $this->assertCount(3, $images);
        $this->assertSame([0, 1, 2], array_column($images, 'position'));
        $this->assertNotNull($response->json('data.image_url'));

        $postId = $response->json('data.id');
        $this->assertDatabaseHas('post_images', ['post_id' => $postId, 'position' => 0]);
        $this->assertSame(3, PostImages::where('post_id', $postId)->count());
    }

    public function test_user_can_add_images_to_own_post(): void
    {
        Storage::fake('public');
        $me = User::factory()->create();
        $post = $me->posts()->create(['content' => 'Post con una imagen', 'type' => 'image', 'image_url' => 'posts/old.jpg']);
        $post->images()->create(['url' => 'posts/old.jpg', 'position' => 0]);

        Sanctum::actingAs($me);

        $response = $this->post("/api/posts/{$post->id}/images", [
            'images' => [
                UploadedFile::fake()->image('extra1.jpg'),
                UploadedFile::fake()->image('extra2.jpg'),
            ],
        ]);

        $response->assertOk();

        $images = $response->json('data.images');
        $this->assertCount(3, $images);
        $this->assertSame([0, 1, 2], array_column($images, 'position'));
    }

    public function test_user_cannot_exceed_five_images_per_post(): void
    {
        Storage::fake('public');
        $me = User::factory()->create();
        $post = $me->posts()->create(['content' => 'Post lleno', 'type' => 'image']);
        foreach (range(0, 4) as $i) {
            $post->images()->create(['url' => "posts/{$i}.jpg", 'position' => $i]);
        }

        Sanctum::actingAs($me);

        $this->post("/api/posts/{$post->id}/images", [
            'images' => [UploadedFile::fake()->image('extra.jpg')],
        ])->assertStatus(422);
    }

    public function test_user_cannot_add_images_to_someone_elses_post(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $post = $author->posts()->create(['content' => 'Ajeno', 'type' => 'text']);
        $other = User::factory()->create();

        Sanctum::actingAs($other);

        $this->post("/api/posts/{$post->id}/images", [
            'images' => [UploadedFile::fake()->image('hack.jpg')],
        ])->assertForbidden();
    }

    public function test_user_can_remove_image_and_promotes_next_as_cover(): void
    {
        Storage::fake('public');
        $me = User::factory()->create();
        $post = $me->posts()->create(['content' => 'Galeria', 'type' => 'image', 'image_url' => 'posts/first.jpg']);
        $first = $post->images()->create(['url' => 'posts/first.jpg', 'position' => 0]);
        $second = $post->images()->create(['url' => 'posts/second.jpg', 'position' => 1]);

        Sanctum::actingAs($me);

        $this->delete("/api/posts/{$post->id}/images/{$first->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Imagen eliminada.');

        $this->assertDatabaseMissing('post_images', ['id' => $first->id]);
        $this->assertSame('posts/second.jpg', $post->fresh()->image_url);
        Storage::disk('public')->assertMissing('posts/first.jpg');
        $this->assertNotNull($second->fresh());
    }

    public function test_remove_image_rejects_image_from_another_post(): void
    {
        Storage::fake('public');
        $me = User::factory()->create();
        $mine = $me->posts()->create(['content' => 'mia', 'type' => 'text']);
        $otherPost = $me->posts()->create(['content' => 'otra', 'type' => 'text']);
        $foreignImage = $otherPost->images()->create(['url' => 'posts/foreign.jpg', 'position' => 0]);

        Sanctum::actingAs($me);

        $this->delete("/api/posts/{$mine->id}/images/{$foreignImage->id}")
            ->assertNotFound();
    }

    public function test_post_detail_includes_images_array(): void
    {
        Storage::fake('public');
        $me = User::factory()->create();
        $post = $me->posts()->create(['content' => 'Detalle', 'type' => 'image']);
        $post->images()->create(['url' => 'posts/x.jpg', 'position' => 0]);

        Sanctum::actingAs($me);

        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.images.0.url', 'posts/x.jpg')
            ->assertJsonPath('data.images.0.position', 0);
    }
}
