<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityEventApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Comunidad Eventos',
            'slug' => 'comunidad-eventos-' . uniqid(),
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_lists_events_ordered_by_start_date(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        $community->events()->create([
            'user_id' => $owner->id,
            'title' => 'Tarde posterior',
            'starts_at' => now()->addDays(5),
        ]);
        $community->events()->create([
            'user_id' => $owner->id,
            'title' => 'Primer evento',
            'starts_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/communities/{$community->id}/events")
            ->assertOk();

        $events = $response->json('data');
        $this->assertCount(2, $events);
        $this->assertEquals('Primer evento', $events[0]['title']);
        $this->assertEquals('Tarde posterior', $events[1]['title']);
    }

    public function test_staff_can_create_event(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Sanctum::actingAs($owner);

        $this->postJson("/api/communities/{$community->id}/events", [
            'title' => 'Reunión mensual',
            'description' => 'Charla de la comunidad',
            'starts_at' => now()->addWeek()->toIso8601String(),
            'ends_at' => now()->addWeek()->addHours(2)->toIso8601String(),
            'location' => 'Discord',
        ])
            ->assertStatus(201)
            ->assertJsonPath('event.title', 'Reunión mensual')
            ->assertJsonPath('event.location', 'Discord');

        $this->assertDatabaseHas('community_events', [
            'community_id' => $community->id,
            'user_id' => $owner->id,
            'title' => 'Reunión mensual',
        ]);
    }

    public function test_member_without_staff_role_cannot_create_event(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($member);

        $this->postJson("/api/communities/{$community->id}/events", [
            'title' => 'Intento de evento',
            'starts_at' => now()->addDay()->toIso8601String(),
        ])->assertStatus(403);
    }

    public function test_event_validation_requires_title_and_future_start(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Sanctum::actingAs($owner);

        $this->postJson("/api/communities/{$community->id}/events", [
            'title' => '',
            'starts_at' => now()->subDay()->toIso8601String(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'starts_at']);
    }

    public function test_event_end_must_be_after_start(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Sanctum::actingAs($owner);

        $this->postJson("/api/communities/{$community->id}/events", [
            'title' => 'Evento malo',
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDay()->subHours(3)->toIso8601String(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_staff_can_update_and_delete_event(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $event = $community->events()->create([
            'user_id' => $owner->id,
            'title' => 'Evento viejo',
            'starts_at' => now()->addDays(2),
        ]);

        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/events/{$event->id}", [
            'title' => 'Evento renombrado',
        ])
            ->assertOk()
            ->assertJsonPath('event.title', 'Evento renombrado');

        $this->deleteJson("/api/communities/{$community->id}/events/{$event->id}")
            ->assertOk();

        $this->assertDatabaseMissing('community_events', ['id' => $event->id]);
    }

    public function test_event_from_another_community_returns_404(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        [$otherOwner, $otherCommunity] = $this->createCommunityWithOwner();
        $foreignEvent = $otherCommunity->events()->create([
            'user_id' => $otherOwner->id,
            'title' => 'Ajeno',
            'starts_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/communities/{$community->id}/events/{$foreignEvent->id}")
            ->assertNotFound();
    }

    public function test_upcoming_filter_excludes_past_events(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        $community->events()->create([
            'user_id' => $owner->id,
            'title' => 'Pasado',
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDays(3)->addHours(2),
        ]);
        $community->events()->create([
            'user_id' => $owner->id,
            'title' => 'Futuro',
            'starts_at' => now()->addDays(3),
        ]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/communities/{$community->id}/events?upcoming=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Futuro');
    }
}
