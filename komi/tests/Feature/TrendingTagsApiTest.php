<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendingTagsApiTest extends TestCase
{
    use RefreshDatabase;

    private function attachTag(Post $post, string $name): Tag
    {
        $tag = Tag::firstOrCreate(
            ['slug' => strtolower($name)],
            ['name' => $name],
        );
        $tag->posts()->attach($post->id);

        return $tag;
    }

    public function test_trending_orders_tags_by_recent_post_count(): void
    {
        $user = User::factory()->create();
        $hot = Post::factory()->create(['user_id' => $user->id, 'created_at' => now()]);
        $this->attachTag($hot, 'Hot');

        $old = Post::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(40)]);
        $this->attachTag($old, 'Viejo');

        // Histórico: ambos tags aparecen.
        $response = $this->getJson('/api/tags/trending')->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('hot'));
        $this->assertTrue($slugs->contains('viejo'));

        // Ventana 7d: solo el tag reciente.
        $response = $this->getJson('/api/tags/trending?window=7d')->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('hot'));
        $this->assertFalse($slugs->contains('viejo'));
    }

    public function test_trending_filters_by_community(): void
    {
        $user = User::factory()->create();
        $community = Community::create([
            'name' => 'Comunidad Trends',
            'slug' => 'comunidad-trends-' . uniqid(),
            'owner_id' => $user->id,
        ]);
        $community->members()->attach($user->id, ['role' => 'owner']);

        $inCommunity = Post::factory()->create([
            'user_id' => $user->id,
            'community_id' => $community->id,
        ]);
        $this->attachTag($inCommunity, 'Comunidad');

        $global = Post::factory()->create(['user_id' => $user->id, 'community_id' => null]);
        $this->attachTag($global, 'Global');

        $response = $this->getJson("/api/tags/trending?community_id={$community->id}")
            ->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug');

        $this->assertTrue($slugs->contains('comunidad'));
        $this->assertFalse($slugs->contains('global'));
    }

    public function test_trending_excludes_tags_without_posts(): void
    {
        Tag::create(['name' => 'Vacio', 'slug' => 'vacio']);

        $response = $this->getJson('/api/tags/trending')->assertOk();
        $this->assertSame([], $response->json('data'));
    }
}
