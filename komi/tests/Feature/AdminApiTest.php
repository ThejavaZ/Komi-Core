<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_logs(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/logs')->assertForbidden();
    }

    public function test_guest_cannot_access_admin_logs(): void
    {
        $this->getJson('/api/admin/logs')->assertUnauthorized();
    }

    public function test_admin_can_access_admin_logs(): void
    {
        $admin = User::factory()->create(['is_global_admin' => true]);
        Sanctum::actingAs($admin);

        AdminLog::log('user.update', $admin, ['status' => 'active'], ['status' => 'suspended']);

        $this->getJson('/api/admin/logs')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id', 'admin_id', 'action', 'target_type', 'target_id',
                    'old_values', 'new_values', 'ip_address', 'created_at',
                ]],
            ]);
    }

    public function test_me_exposes_is_global_admin_to_self(): void
    {
        $admin = User::factory()->create(['is_global_admin' => true]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.is_global_admin', true);

        $user = User::factory()->create(['is_global_admin' => false]);
        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.is_global_admin', false);
    }

    public function test_logs_can_filter_by_action(): void
    {
        $admin = User::factory()->create(['is_global_admin' => true]);
        Sanctum::actingAs($admin);

        AdminLog::log('user.update', $admin, null, null);
        AdminLog::log('post.delete', $admin, null, null);

        $response = $this->getJson('/api/admin/logs?action=user.update')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('user.update', $response->json('data.0.action'));
    }
}
