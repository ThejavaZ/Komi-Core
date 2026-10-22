<?php

namespace Tests\Feature;

use App\Models\FeatureEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureTelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_event_is_created(): void
    {
        $this->postJson('/api/telemetry/features', ['feature' => 'post.created'])
            ->assertCreated();

        $this->assertDatabaseHas('feature_events', [
            'feature' => 'post.created',
            'occurrences_count' => 1,
        ]);
    }

    public function test_feature_event_increments_on_repeat(): void
    {
        $this->postJson('/api/telemetry/features', ['feature' => 'chat.message_sent']);
        $this->postJson('/api/telemetry/features', ['feature' => 'chat.message_sent']);
        $this->postJson('/api/telemetry/features', ['feature' => 'chat.message_sent']);

        $this->assertSame(3, FeatureEvent::where('feature', 'chat.message_sent')->value('occurrences_count'));
    }

    public function test_invalid_feature_name_is_rejected(): void
    {
        $this->postJson('/api/telemetry/features', ['feature' => 'Espacios no válidos!'])
            ->assertUnprocessable();

        $this->postJson('/api/telemetry/features', [])
            ->assertUnprocessable();
    }
}
