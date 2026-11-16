<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ActivityLogService
    {
        return app(ActivityLogService::class);
    }

    public function test_log_persists_action_subject_and_context(): void
    {
        $user = User::factory()->administrator()->create();
        $lead = Lead::factory()->create();

        $log = $this->service()->log('lead.updated', $lead, ['note' => 'hello'], $user);

        $this->assertNotNull($log);
        $this->assertSame('lead.updated', $log->action);
        $this->assertSame(Lead::class, $log->subject_type);
        $this->assertSame($lead->id, $log->subject_id);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(['note' => 'hello'], $log->context);
        $this->assertNotNull($log->ip_address);
    }

    public function test_sensitive_context_keys_are_redacted(): void
    {
        $log = $this->service()->log('auth.attempt', null, [
            'password' => 'super-secret',
            'api_token' => 'tok_123',
            'client_secret' => 'sec_123',
            'stripe_key' => 'sk_123',
            'p256dh' => 'p256-dh-value',
            'auth' => 'auth-value',
            'Password_Confirmation' => 'confirmed',
            'safe_field' => 'keep-me',
            'nested' => [
                'user_password' => 'nested-secret',
                'visible' => 'nested-visible',
            ],
        ]);

        $this->assertNotNull($log);
        $context = $log->fresh()->context;

        $this->assertSame('[redacted]', $context['password']);
        $this->assertSame('[redacted]', $context['api_token']);
        $this->assertSame('[redacted]', $context['client_secret']);
        $this->assertSame('[redacted]', $context['stripe_key']);
        $this->assertSame('[redacted]', $context['p256dh']);
        $this->assertSame('[redacted]', $context['auth']);
        $this->assertSame('[redacted]', $context['Password_Confirmation']);
        $this->assertSame('keep-me', $context['safe_field']);
        $this->assertSame('[redacted]', $context['nested']['user_password']);
        $this->assertSame('nested-visible', $context['nested']['visible']);

        $raw = ActivityLog::first()->getRawOriginal('context');
        $this->assertStringNotContainsString('super-secret', (string) $raw);
        $this->assertStringNotContainsString('tok_123', (string) $raw);
        $this->assertStringNotContainsString('p256-dh-value', (string) $raw);
    }

    public function test_user_id_defaults_to_authenticated_user(): void
    {
        $user = User::factory()->administrator()->create();
        $this->actingAs($user);

        $log = $this->service()->log('settings.updated');

        $this->assertSame($user->id, $log->user_id);
    }

    public function test_user_id_is_null_when_unauthenticated(): void
    {
        $log = $this->service()->log('system.tick');

        $this->assertNull($log->user_id);
    }

    public function test_log_model_change_stores_only_changed_fields(): void
    {
        $lead = Lead::factory()->create();

        $log = $this->service()->logModelChange(
            'lead.updated',
            $lead,
            ['customer_name' => 'Old', 'status' => 'new'],
            ['customer_name' => 'Old', 'status' => 'qualified'],
        );

        $context = $log->fresh()->context;

        $this->assertArrayNotHasKey('customer_name', $context['changes']);
        $this->assertSame(['old' => 'new', 'new' => 'qualified'], $context['changes']['status']);
    }

    public function test_log_model_change_redacts_sensitive_fields(): void
    {
        $user = User::factory()->create();

        $log = $this->service()->logModelChange(
            'user.updated',
            $user,
            ['password' => 'old-hash'],
            ['password' => 'new-hash'],
        );

        $raw = (string) ActivityLog::first()->getRawOriginal('context');

        $this->assertStringNotContainsString('new-hash', $raw);
        $this->assertStringNotContainsString('old-hash', $raw);
    }

    public function test_service_never_throws_when_persistence_fails(): void
    {
        Log::spy();

        // Force a failure: break the activity_logs table for this connection.
        DB::statement('DROP TABLE activity_logs');

        $result = $this->service()->log('lead.updated', null, ['x' => 1]);

        $this->assertNull($result);
        Log::shouldHaveReceived('warning')->atLeast()->once();
    }

    public function test_lead_conversion_writes_activity_log_via_service(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create();

        app(\App\Services\LeadConversionService::class)->changeStatus($lead, Lead::STATUS_QUALIFIED, $admin);

        $log = ActivityLog::where('action', 'lead.status_changed')->first();

        $this->assertNotNull($log);
        $this->assertSame(Lead::class, $log->subject_type);
        $this->assertSame($lead->id, $log->subject_id);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('qualified', $log->context['new_value']);
    }

    public function test_activity_log_index_is_paginated_and_filterable(): void
    {
        $analyst = User::factory()->create(['role' => User::ROLE_ANALYST]);
        $other = User::factory()->administrator()->create();

        ActivityLog::factory()->create([
            'user_id' => $analyst->id,
            'action' => 'lead.updated',
            'created_at' => now(),
        ]);
        ActivityLog::factory()->create([
            'user_id' => $other->id,
            'action' => 'settings.updated',
            'created_at' => now(),
        ]);

        $this->actingAs($analyst)
            ->getJson('/admin/activity-logs?action=lead.updated')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'lead.updated')
            ->assertJsonPath('data.0.user.id', $analyst->id);
    }

    public function test_content_editor_is_forbidden_from_activity_logs(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($editor)
            ->getJson('/admin/activity-logs')
            ->assertForbidden();
    }
}
