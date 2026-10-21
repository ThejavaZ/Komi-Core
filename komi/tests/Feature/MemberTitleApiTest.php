<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberTitleApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Comunidad Placas',
            'slug' => 'comunidad-placas-' . uniqid(),
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_staff_can_grant_custom_title(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/members/{$member->id}/title", [
            'custom_title' => 'Artista Estrella',
        ])
            ->assertOk()
            ->assertJsonPath('member.custom_title', 'Artista Estrella');

        $response = $this->getJson("/api/communities/{$community->id}/members")
            ->assertOk();

        $memberJson = collect($response->json('data'))->firstWhere('id', $member->id);
        $this->assertSame('Artista Estrella', $memberJson['custom_title']);
        $this->assertSame('member', $memberJson['role']);
    }

    public function test_staff_can_revoke_custom_title_with_null(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member', 'custom_title' => 'Viejo Título']);
        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/members/{$member->id}/title", [
            'custom_title' => null,
        ])
            ->assertOk()
            ->assertJsonPath('member.custom_title', null);
    }

    public function test_regular_member_cannot_grant_titles(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $other = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        $community->members()->attach($other->id, ['role' => 'member']);
        Sanctum::actingAs($member);

        $this->putJson("/api/communities/{$community->id}/members/{$other->id}/title", [
            'custom_title' => 'Falso',
        ])->assertForbidden();
    }

    public function test_title_must_be_at_most_30_chars(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/members/{$member->id}/title", [
            'custom_title' => str_repeat('a', 31),
        ])->assertUnprocessable();
    }

    public function test_owner_cannot_be_titled(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/members/{$owner->id}/title", [
            'custom_title' => 'Yo mismo',
        ])->assertStatus(422);
    }
}
