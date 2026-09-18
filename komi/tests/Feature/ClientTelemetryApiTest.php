<?php

namespace Tests\Feature;

use App\Models\ClientLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_endpoint_requires_message(): void
    {
        $this->postJson('/api/telemetry/logs', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    public function test_creates_a_new_log_with_occurrences_count_one(): void
    {
        $response = $this->postJson('/api/telemetry/logs', [
            'message' => 'TimeoutException: connection timed out',
            'stack_trace' => '#0 main()',
            'app_version' => '1.0.0+1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseCount('client_logs', 1);

        $log = ClientLog::first();
        $this->assertNotNull($log);
        $this->assertEquals(1, $log->occurrences_count);
        $this->assertEquals('1.0.0+1', $log->app_version);
        $this->assertEquals(
            ClientLog::hashFor('TimeoutException: connection timed out', '#0 main()'),
            $log->error_hash,
        );
    }

    public function test_duplicate_messages_do_not_create_new_rows(): void
    {
        $payloads = [
            ['message' => 'SocketException: Failed host lookup', 'stack_trace' => '#0 foo.dart'],
            ['message' => 'SocketException: Failed host lookup', 'stack_trace' => '#0 foo.dart'],
            ['message' => 'SocketException: Failed host lookup', 'stack_trace' => '#0 foo.dart'],
        ];

        foreach ($payloads as $payload) {
            $this->postJson('/api/telemetry/logs', $payload)
                ->assertCreated();
        }

        $this->assertDatabaseCount('client_logs', 1);

        $log = ClientLog::first();
        $this->assertEquals(3, $log->occurrences_count);
        $this->assertNotNull($log->last_seen_at);
    }

    public function test_same_message_with_different_stack_trace_is_a_separate_log(): void
    {
        $this->postJson('/api/telemetry/logs', [
            'message' => 'Boom',
            'stack_trace' => '#0 lib/a.dart',
        ])->assertCreated();

        $this->postJson('/api/telemetry/logs', [
            'message' => 'Boom',
            'stack_trace' => '#0 lib/b.dart',
        ])->assertCreated();

        $this->assertDatabaseCount('client_logs', 2);
    }

    public function test_upsert_refreshes_last_seen_at_on_duplicate(): void
    {
        $first = $this->postJson('/api/telemetry/logs', [
            'message' => 'HttpException: 500',
        ])->assertCreated();

        // El segundo reporte debe actualizar last_seen_at sin crear otra fila.
        $this->postJson('/api/telemetry/logs', [
            'message' => 'HttpException: 500',
        ])->assertCreated();

        $this->assertDatabaseCount('client_logs', 1);

        $log = ClientLog::first();
        $this->assertEquals(2, $log->occurrences_count);
    }
}