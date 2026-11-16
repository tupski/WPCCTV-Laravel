<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Models\WhatsAppClick;
use App\Services\LeadConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_converting_a_click_creates_a_lead_with_audit_trail(): void
    {
        $admin = User::factory()->administrator()->create();
        $click = WhatsAppClick::factory()->fromProduct()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.leads.convert'), [
            'whatsapp_click_id' => $click->id,
            'customer_name' => 'Budi Santoso',
            'whatsapp_number' => '6281234567890',
            'lead_type' => Lead::TYPE_INSTALLATION,
            'requirements' => 'Butuh 8 kamera untuk gudang.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer_name', 'Budi Santoso')
            ->assertJsonPath('data.status', Lead::STATUS_NEW)
            ->assertJsonPath('data.assigned_to', $admin->id);

        $lead = Lead::firstOrFail();

        $this->assertSame($click->id, $lead->whatsapp_click_id);
        $this->assertNotNull($lead->converted_at);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'user_id' => $admin->id,
            'action' => LeadActivity::ACTION_CONVERT,
            'whatsapp_click_id' => $click->id,
        ]);

        $this->assertSame($lead->id, $click->fresh()->converted_lead_id);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'lead.converted',
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
        ]);
    }

    public function test_conversion_is_idempotent_for_the_same_click(): void
    {
        $admin = User::factory()->administrator()->create();
        $click = WhatsAppClick::factory()->create();

        $service = app(LeadConversionService::class);

        $first = $service->convert($click, ['customer_name' => 'First'], $admin);
        $second = $service->convert($click, ['customer_name' => 'Second'], $admin);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Lead::count());
        $this->assertSame(
            1,
            LeadActivity::where('action', LeadActivity::ACTION_CONVERT)->count(),
            'Convert activity must be written once only.',
        );
    }

    public function test_assigned_to_is_forced_from_the_acting_admin(): void
    {
        $admin = User::factory()->administrator()->create();
        $other = User::factory()->administrator()->create();
        $click = WhatsAppClick::factory()->create();

        $this->actingAs($admin)->postJson(route('admin.leads.convert'), [
            'whatsapp_click_id' => $click->id,
            'customer_name' => 'Siti',
            'assigned_to' => $other->id,
        ])->assertCreated();

        $this->assertSame($admin->id, Lead::firstOrFail()->assigned_to);
    }

    public function test_status_change_audits_old_and_new_values(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['status' => Lead::STATUS_NEW, 'assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->putJson(route('admin.leads.update', $lead), ['status' => Lead::STATUS_CONTACTED])
            ->assertOk()
            ->assertJsonPath('data.status', Lead::STATUS_CONTACTED);

        $activity = LeadActivity::where('lead_id', $lead->id)
            ->where('action', LeadActivity::ACTION_STATUS_CHANGE)
            ->firstOrFail();

        $this->assertSame('status', $activity->field);
        $this->assertSame(Lead::STATUS_NEW, $activity->old_value);
        $this->assertSame(Lead::STATUS_CONTACTED, $activity->new_value);
        $this->assertSame($admin->id, $activity->user_id);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'lead.status_changed',
            'subject_id' => $lead->id,
        ]);
    }

    public function test_qualified_status_sets_converted_at_when_null(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create([
            'status' => Lead::STATUS_NEW,
            'assigned_to' => $admin->id,
            'converted_at' => null,
        ]);

        $this->actingAs($admin)
            ->putJson(route('admin.leads.update', $lead), ['status' => Lead::STATUS_QUALIFIED])
            ->assertOk();

        $this->assertNotNull($lead->fresh()->converted_at);
    }

    public function test_note_is_appended_to_the_timeline(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)->postJson(route('admin.leads.activities.store', $lead), [
            'action' => LeadActivity::ACTION_NOTE,
            'note' => 'Sudah dihubungi via WhatsApp.',
        ])->assertCreated();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'user_id' => $admin->id,
            'action' => LeadActivity::ACTION_NOTE,
            'new_value' => 'Sudah dihubungi via WhatsApp.',
        ]);
    }

    public function test_assignment_change_is_audited(): void
    {
        $admin = User::factory()->administrator()->create();
        $assignee = User::factory()->leadManager()->create();
        $lead = Lead::factory()->create(['assigned_to' => null]);

        $this->actingAs($admin)
            ->putJson(route('admin.leads.update', $lead), ['assigned_to' => $assignee->id])
            ->assertOk();

        $this->assertSame($assignee->id, $lead->fresh()->assigned_to);

        $activity = LeadActivity::where('lead_id', $lead->id)
            ->where('action', LeadActivity::ACTION_ASSIGN)
            ->firstOrFail();

        $this->assertSame('assigned_to', $activity->field);
        $this->assertNull($activity->old_value);
        $this->assertSame((string) $assignee->id, $activity->new_value);
    }

    public function test_follow_up_schedule_is_persisted_and_audited(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->putJson(route('admin.leads.update', $lead), ['follow_up_at' => '2026-12-01 09:00:00'])
            ->assertOk();

        $this->assertNotNull($lead->fresh()->follow_up_at);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_FOLLOWUP,
            'field' => 'follow_up_at',
        ]);
    }

    public function test_manual_lead_creation_forces_status_and_assignment(): void
    {
        $admin = User::factory()->administrator()->create();
        $other = User::factory()->administrator()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.leads.store'), [
            'customer_name' => 'Pak Andi',
            'whatsapp_number' => '628111222333',
            'lead_type' => Lead::TYPE_REPAIR,
            'requirements' => 'Perbaikan DVR.',
            'priority' => 'high',
            'notes' => 'Datang dari referral.',
            'status' => Lead::STATUS_WON,
            'assigned_to' => $other->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Lead::STATUS_NEW)
            ->assertJsonPath('data.assigned_to', $admin->id)
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('leads', [
            'customer_name' => 'Pak Andi',
            'status' => Lead::STATUS_NEW,
            'assigned_to' => $admin->id,
            'priority' => 'high',
        ]);
    }

    public function test_index_filters_by_status_type_priority_and_search(): void
    {
        $admin = User::factory()->administrator()->create();

        Lead::factory()->create([
            'customer_name' => 'Budi Gudang',
            'whatsapp_number' => '628111111111',
            'status' => Lead::STATUS_NEW,
            'lead_type' => Lead::TYPE_INSTALLATION,
            'priority' => 'high',
            'assigned_to' => $admin->id,
        ]);

        Lead::factory()->create([
            'customer_name' => 'Siti Rumah',
            'whatsapp_number' => '628222222222',
            'status' => Lead::STATUS_WON,
            'lead_type' => Lead::TYPE_REPAIR,
            'priority' => 'low',
            'assigned_to' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.leads.index', ['status' => Lead::STATUS_NEW]))
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->actingAs($admin)
            ->getJson(route('admin.leads.index', ['search' => 'Siti']))
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.customer_name', 'Siti Rumah');

        $this->actingAs($admin)
            ->getJson(route('admin.leads.index', ['priority' => 'high']))
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->actingAs($admin)
            ->getJson(route('admin.leads.index', ['lead_type' => Lead::TYPE_REPAIR]))
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_lead_manager_only_sees_their_own_assigned_leads(): void
    {
        $manager = User::factory()->leadManager()->create();
        $otherManager = User::factory()->leadManager()->create();

        $mine = Lead::factory()->create(['assigned_to' => $manager->id]);
        $theirs = Lead::factory()->create(['assigned_to' => $otherManager->id]);

        $this->actingAs($manager)
            ->getJson(route('admin.leads.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $mine->id);

        $this->actingAs($manager)
            ->getJson(route('admin.leads.show', $theirs))
            ->assertForbidden();

        $this->actingAs($manager)
            ->getJson(route('admin.leads.show', $mine))
            ->assertOk();
    }

    public function test_administrator_sees_all_leads(): void
    {
        $admin = User::factory()->administrator()->create();
        $manager = User::factory()->leadManager()->create();

        Lead::factory()->create(['assigned_to' => $manager->id]);
        Lead::factory()->create(['assigned_to' => null]);

        $this->actingAs($admin)
            ->getJson(route('admin.leads.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data.data');
    }

    public function test_analyst_has_no_lead_access(): void
    {
        $analyst = User::factory()->analyst()->create();
        $lead = Lead::factory()->create();

        $this->actingAs($analyst)->getJson(route('admin.leads.index'))->assertForbidden();
        $this->actingAs($analyst)->getJson(route('admin.leads.show', $lead))->assertForbidden();
    }

    public function test_timeline_returns_activities_newest_first(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_NOTE,
            'new_value' => 'older',
            'created_at' => now()->subHours(2),
        ]);

        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_NOTE,
            'new_value' => 'newer',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.leads.timeline.index', $lead))
            ->assertOk();

        $this->assertSame('newer', $response->json('data.data.0.new_value'));
        $this->assertSame('older', $response->json('data.data.1.new_value'));
    }

    public function test_only_admin_roles_can_delete_a_lead(): void
    {
        $manager = User::factory()->leadManager()->create();
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $manager->id]);

        $this->actingAs($manager)
            ->deleteJson(route('admin.leads.destroy', $lead))
            ->assertForbidden();

        $this->assertDatabaseHas('leads', ['id' => $lead->id]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.leads.destroy', $lead))
            ->assertOk()
            ->assertJsonPath('message', 'Lead berhasil dihapus.');

        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_status_change_writes_activity_log(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->putJson(route('admin.leads.update', $lead), ['status' => Lead::STATUS_WON])
            ->assertOk();

        $log = ActivityLog::where('action', 'lead.status_changed')->firstOrFail();

        $this->assertSame(Lead::class, $log->subject_type);
        $this->assertSame($lead->id, $log->subject_id);
        $this->assertSame(Lead::STATUS_NEW, $log->context['old_value']);
        $this->assertSame(Lead::STATUS_WON, $log->context['new_value']);
    }
}
