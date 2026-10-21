<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BulletinApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Comunidad Boletín',
            'slug' => 'comunidad-bulletin-' . uniqid(),
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_staff_can_set_bulletin(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}", ['bulletin' => '¡Evento el viernes!'])
            ->assertOk()
            ->assertJsonPath('community.bulletin', '¡Evento el viernes!');
    }

    public function test_bulletin_must_be_at_most_300_chars(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}", ['bulletin' => str_repeat('a', 301)])
            ->assertUnprocessable();
    }

    public function test_regular_member_cannot_edit_bulletin(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        Sanctum::actingAs($member);

        $this->putJson("/api/communities/{$community->id}", ['bulletin' => 'Hackeo'])
            ->assertForbidden();
    }

    public function test_bulletin_is_null_by_default(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $viewer = User::factory()->create();
        $community->members()->attach($viewer->id, ['role' => 'member']);
        Sanctum::actingAs($viewer);

        $this->getJson("/api/communities/{$community->id}")
            ->assertOk()
            ->assertJsonPath('data.bulletin', null);
    }
}
