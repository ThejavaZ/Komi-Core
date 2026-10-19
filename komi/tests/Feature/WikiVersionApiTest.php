<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WikiVersionApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un wiki via el flujo normal de creacion de posts.
     *
     * @return array{wiki_id: int, post_id: int}
     */
    private function createWikiPost(User $author, array $extra = []): array
    {
        Sanctum::actingAs($author);

        $response = $this->postJson('/api/posts', array_merge([
            'content' => 'Contenido inicial del wiki',
            'wiki_title' => 'Guia de Komi',
        ], $extra));

        $response->assertCreated();

        return [
            'wiki_id' => $response->json('data.wiki.id'),
            'post_id' => $response->json('data.id'),
        ];
    }

    public function test_wiki_post_creation_stores_first_version_and_content(): void
    {
        $author = User::factory()->create();
        ['wiki_id' => $wikiId] = $this->createWikiPost($author);

        Sanctum::actingAs($author);

        $this->getJson("/api/wikis/{$wikiId}")
            ->assertOk()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.content', 'Contenido inicial del wiki')
            ->assertJsonPath('data.title', 'Guia de Komi')
            ->assertJsonPath('data.author.id', $author->id);
    }

    public function test_author_can_edit_wiki_and_it_creates_a_new_version(): void
    {
        $author = User::factory()->create();
        ['wiki_id' => $wikiId, 'post_id' => $postId] = $this->createWikiPost($author);

        Sanctum::actingAs($author);

        $this->putJson("/api/wikis/{$wikiId}", [
            'content' => 'Contenido editado v2',
            'edit_summary' => 'Agrego una seccion',
        ])
            ->assertOk()
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.content', 'Contenido editado v2');

        $this->getJson("/api/wikis/{$wikiId}/versions")
            ->assertOk()
            ->assertJsonPath('data.0.version', 2)
            ->assertJsonPath('data.0.edit_summary', 'Agrego una seccion')
            ->assertJsonPath('data.0.editor.id', $author->id)
            ->assertJsonPath('data.1.version', 1);

        // El post que alimenta el feed queda sincronizado
        $this->getJson("/api/posts/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.content', 'Contenido editado v2')
            ->assertJsonPath('data.wiki.version', 2);
    }

    public function test_editing_wiki_post_through_post_update_creates_a_version(): void
    {
        $author = User::factory()->create();
        ['wiki_id' => $wikiId, 'post_id' => $postId] = $this->createWikiPost($author);

        Sanctum::actingAs($author);

        $this->putJson("/api/posts/{$postId}", [
            'content' => 'Editado desde el post',
            'edit_summary' => 'Edicion rapida',
        ])->assertOk();

        $this->getJson("/api/wikis/{$wikiId}/versions")
            ->assertOk()
            ->assertJsonPath('data.0.version', 2)
            ->assertJsonPath('data.0.edit_summary', 'Edicion rapida');

        $this->getJson("/api/wikis/{$wikiId}")
            ->assertOk()
            ->assertJsonPath('data.content', 'Editado desde el post');
    }

    public function test_stranger_cannot_edit_wiki(): void
    {
        $author = User::factory()->create();
        ['wiki_id' => $wikiId] = $this->createWikiPost($author);

        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/wikis/{$wikiId}", [
            'content' => 'Contenido malicioso',
        ])->assertForbidden();
    }

    public function test_community_moderator_can_edit_wiki(): void
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Wiki Comm',
            'slug' => 'wiki-comm-' . $owner->id,
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        $author = User::factory()->create();
        $community->members()->attach($author->id, ['role' => 'member']);

        ['wiki_id' => $wikiId] = $this->createWikiPost($author, ['community_id' => $community->id]);

        Sanctum::actingAs($owner);

        $this->putJson("/api/wikis/{$wikiId}", [
            'content' => 'Editado por el moderador',
        ])->assertOk()->assertJsonPath('data.version', 2);
    }

    public function test_version_detail_returns_single_version_content(): void
    {
        $author = User::factory()->create();
        ['wiki_id' => $wikiId] = $this->createWikiPost($author);

        Sanctum::actingAs($author);

        $this->putJson("/api/wikis/{$wikiId}", ['content' => 'Version dos'])->assertOk();

        $this->getJson("/api/wikis/{$wikiId}/versions/1")
            ->assertOk()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.content', 'Contenido inicial del wiki');

        $this->getJson("/api/wikis/{$wikiId}/versions/99")->assertNotFound();
    }

    public function test_rollback_restores_old_content_as_a_new_version(): void
    {
        $author = User::factory()->create();
        ['wiki_id' => $wikiId, 'post_id' => $postId] = $this->createWikiPost($author);

        Sanctum::actingAs($author);

        $this->putJson("/api/wikis/{$wikiId}", ['content' => 'Version dos'])->assertOk();

        $this->postJson("/api/wikis/{$wikiId}/rollback", ['version' => 1])
            ->assertOk()
            ->assertJsonPath('data.version', 3)
            ->assertJsonPath('data.content', 'Contenido inicial del wiki');

        $this->getJson("/api/wikis/{$wikiId}")
            ->assertOk()
            ->assertJsonPath('data.version', 3)
            ->assertJsonPath('data.content', 'Contenido inicial del wiki');

        $this->getJson("/api/posts/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.content', 'Contenido inicial del wiki');
    }

    public function test_rollback_to_current_version_is_rejected(): void
    {
        $author = User::factory()->create();
        ['wiki_id' => $wikiId] = $this->createWikiPost($author);

        Sanctum::actingAs($author);

        $this->postJson("/api/wikis/{$wikiId}/rollback", ['version' => 1])
            ->assertStatus(422);
    }

    public function test_rollback_to_missing_version_is_rejected(): void
    {
        $author = User::factory()->create();
        ['wiki_id' => $wikiId] = $this->createWikiPost($author);

        Sanctum::actingAs($author);

        $this->postJson("/api/wikis/{$wikiId}/rollback", ['version' => 42])
            ->assertNotFound();
    }

    public function test_post_resource_wiki_includes_content_and_post_id(): void
    {
        $author = User::factory()->create();
        ['wiki_id' => $wikiId, 'post_id' => $postId] = $this->createWikiPost($author);

        Sanctum::actingAs($author);

        $this->getJson("/api/posts/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.wiki.id', $wikiId)
            ->assertJsonPath('data.wiki.content', 'Contenido inicial del wiki')
            ->assertJsonPath('data.wiki.post_id', $postId);
    }
}
