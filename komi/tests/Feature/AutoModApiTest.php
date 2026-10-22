<?php

namespace Tests\Feature;

use App\Models\AutoModKeyword;
use App\Models\Community;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutoModApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_flagged_keyword_creates_post_and_auto_report(): void
    {
        AutoModKeyword::create(['keyword' => 'estafa', 'action' => 'flag']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/posts', [
            'content' => 'Este giveaway es una estafa total',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('reports', [
            'reportable_type' => 'App\Models\Post',
            'reason' => 'automod',
            'status' => 'pending',
        ]);
    }

    public function test_blocked_keyword_rejects_post(): void
    {
        AutoModKeyword::create(['keyword' => 'keygen', 'action' => 'block']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Descarga el keygen aquí',
        ])->assertUnprocessable();
    }

    public function test_blocked_keyword_rejects_comment(): void
    {
        AutoModKeyword::create(['keyword' => 'te voy a matar', 'action' => 'block']);
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'type' => 'text']);
        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'bro te voy a matar si sigues',
        ])->assertUnprocessable();
    }

    public function test_clean_content_is_not_flagged(): void
    {
        AutoModKeyword::create(['keyword' => 'estafa', 'action' => 'flag']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'Hola a todos, qué buen sábado',
        ])->assertCreated();

        $this->assertSame(0, Report::where('reason', 'automod')->count());
    }

    public function test_keywords_are_case_insensitive(): void
    {
        AutoModKeyword::create(['keyword' => 'ESTAFA', 'action' => 'block']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'content' => 'esto es una estafa grave',
        ])->assertUnprocessable();
    }
}
