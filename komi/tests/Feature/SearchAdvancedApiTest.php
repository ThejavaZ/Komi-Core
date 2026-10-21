<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchAdvancedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_type_filter_restricts_sections(): void
    {
        Sanctum::actingAs(User::factory()->create(['name' => 'BuscadorZeta', 'username' => 'buscadorzeta']));

        $response = $this->getJson('/api/search?q=Buscador&type=users')
            ->assertOk();

        $this->assertCount(1, $response->json('users'));
        $this->assertCount(0, $response->json('posts'));
        $this->assertCount(0, $response->json('communities'));
        $this->assertCount(0, $response->json('tags'));
    }

    public function test_post_search_filters_by_date_range(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $old = $user->posts()->create([
            'content' => 'noticia antigua de prueba',
        ]);
        $recent = $user->posts()->create([
            'content' => 'noticia reciente de prueba',
        ]);

        // created_at no es mass-assignable: se fija via query builder.
        Post::whereKey($old->id)->update(['created_at' => now()->subDays(30)]);
        Post::whereKey($recent->id)->update(['created_at' => now()->subDay()]);

        $from = now()->subDays(7)->format('Y-m-d');
        $response = $this->getJson("/api/search?q=noticia&type=posts&from={$from}")
            ->assertOk();

        $ids = collect($response->json('posts'))->pluck('id');
        $this->assertTrue($ids->contains($recent->id));
        $this->assertFalse($ids->contains($old->id));
    }

    public function test_post_search_filters_by_community_and_author(): void
    {
        $owner = User::factory()->create(['username' => 'autorbusca']);
        $other = User::factory()->create();

        $community = Community::create([
            'name' => 'Filtrable',
            'slug' => 'filtrable-' . $owner->id,
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        $inCommunity = $owner->posts()->create([
            'content' => 'contenido filtrable comunidad',
            'community_id' => $community->id,
        ]);
        $owner->posts()->create(['content' => 'contenido filtrable global']);
        $other->posts()->create(['content' => 'contenido filtrable de otro autor']);

        Sanctum::actingAs($owner);

        $response = $this->getJson(
            "/api/search?q=filtrable&type=posts&community_id={$community->id}"
        )->assertOk();
        $ids = collect($response->json('posts'))->pluck('id');
        $this->assertEquals([$inCommunity->id], $ids->values()->all());

        $response = $this->getJson('/api/search?q=filtrable&type=posts&author=autorbusca')
            ->assertOk();
        $ids = collect($response->json('posts'))->pluck('id');
        $this->assertTrue($ids->contains($inCommunity->id));
        $this->assertFalse($ids->contains(
            Post::where('content', 'like', '%de otro autor%')->first()->id
        ));
    }

    public function test_invalid_date_returns_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/search?q=x&type=posts&from=no-es-fecha')
            ->assertStatus(422);
    }

    public function test_search_without_filters_still_returns_all_sections(): void
    {
        Sanctum::actingAs(User::factory()->create(['name' => 'GlobalMix']));

        $this->getJson('/api/search?q=GlobalMix')
            ->assertOk()
            ->assertJsonStructure(['users', 'posts', 'communities', 'tags']);
    }
}
