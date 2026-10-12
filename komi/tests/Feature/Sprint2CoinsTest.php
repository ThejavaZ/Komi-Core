<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint2CoinsTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkin_requires_authentication(): void
    {
        $this->getJson('/api/checkin/status')->assertUnauthorized();
        $this->postJson('/api/checkin')->assertUnauthorized();
    }

    public function test_user_can_claim_first_daily_checkin(): void
    {
        $user = User::factory()->create(['coins' => 0, 'checkin_streak' => 0, 'last_checkin_at' => null]);

        Sanctum::actingAs($user);

        $this->postJson('/api/checkin')
            ->assertCreated()
            ->assertJsonPath('data.reward', 10)
            ->assertJsonPath('data.streak', 1)
            ->assertJsonPath('data.coins', 10)
            ->assertJsonPath('data.claimed_today', true);

        $this->assertSame(10, $user->fresh()->coins);
        $this->assertSame(1, $user->fresh()->checkin_streak);
    }

    public function test_cannot_claim_checkin_twice_same_day(): void
    {
        $user = User::factory()->create(['coins' => 0]);
        Sanctum::actingAs($user);

        $this->postJson('/api/checkin')->assertCreated();

        $this->postJson('/api/checkin')
            ->assertStatus(409)
            ->assertJsonPath('claimed_today', true);

        $this->assertSame(10, $user->fresh()->coins);
    }

    public function test_checkin_streak_increments_on_consecutive_day(): void
    {
        $user = User::factory()->create([
            'coins' => 10,
            'checkin_streak' => 1,
            'last_checkin_at' => now()->subDay()->toDateString(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/checkin')
            ->assertCreated()
            ->assertJsonPath('data.streak', 2)
            ->assertJsonPath('data.reward', 12) // 10 + (2-1)*2
            ->assertJsonPath('data.coins', 22);

        $this->assertSame(22, $user->fresh()->coins);
    }

    public function test_checkin_streak_resets_after_gap(): void
    {
        $user = User::factory()->create([
            'coins' => 50,
            'checkin_streak' => 7,
            'last_checkin_at' => now()->subDays(3)->toDateString(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/checkin')
            ->assertCreated()
            ->assertJsonPath('data.streak', 1)
            ->assertJsonPath('data.reward', 10)
            ->assertJsonPath('data.coins', 60);
    }

    public function test_checkin_reward_is_capped(): void
    {
        $user = User::factory()->create([
            'coins' => 0,
            'checkin_streak' => 20,
            'last_checkin_at' => now()->subDay()->toDateString(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/checkin')
            ->assertCreated()
            ->assertJsonPath('data.reward', 30) // tope
            ->assertJsonPath('data.streak', 21);
    }

    public function test_checkin_status_reflects_claimed_and_next_reward(): void
    {
        $user = User::factory()->create(['coins' => 0, 'checkin_streak' => 0, 'last_checkin_at' => null]);
        Sanctum::actingAs($user);

        // Sin reclamar: next_reward = 10.
        $this->getJson('/api/checkin/status')
            ->assertOk()
            ->assertJsonPath('claimed_today', false)
            ->assertJsonPath('next_reward', 10);

        $this->postJson('/api/checkin')->assertCreated();

        // Tras reclamar: claimed_today=true y next_reward=0.
        $this->getJson('/api/checkin/status')
            ->assertOk()
            ->assertJsonPath('claimed_today', true)
            ->assertJsonPath('next_reward', 0);
    }

    // ─── Tips ───────────────────────────────────────────────────────────────

    public function test_user_can_tip_another_user(): void
    {
        $tipper = User::factory()->create(['coins' => 50]);
        $receiver = User::factory()->create(['coins' => 0]);

        Sanctum::actingAs($tipper);

        $this->postJson("/api/users/{$receiver->id}/tip", ['amount' => 20])
            ->assertCreated()
            ->assertJsonPath('data.amount', 20)
            ->assertJsonPath('data.receiver_id', $receiver->id)
            ->assertJsonPath('data.coins_remaining', 30);

        $this->assertSame(30, $tipper->fresh()->coins);
        $this->assertSame(20, $receiver->fresh()->coins);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $receiver->id,
            'from_user_id' => $tipper->id,
            'type' => 'tip',
        ]);
    }

    public function test_cannot_tip_self(): void
    {
        $user = User::factory()->create(['coins' => 50]);
        Sanctum::actingAs($user);

        $this->postJson("/api/users/{$user->id}/tip", ['amount' => 10])->assertStatus(422);
        $this->assertSame(50, $user->fresh()->coins);
    }

    public function test_tip_fails_with_insufficient_coins(): void
    {
        $tipper = User::factory()->create(['coins' => 5]);
        $receiver = User::factory()->create(['coins' => 0]);

        Sanctum::actingAs($tipper);

        $this->postJson("/api/users/{$receiver->id}/tip", ['amount' => 50])->assertStatus(402);

        $this->assertSame(5, $tipper->fresh()->coins);
        $this->assertSame(0, $receiver->fresh()->coins);
    }

    public function test_tip_validation(): void
    {
        $tipper = User::factory()->create(['coins' => 50]);
        $receiver = User::factory()->create();
        Sanctum::actingAs($tipper);

        $this->postJson("/api/users/{$receiver->id}/tip", ['amount' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }
}
