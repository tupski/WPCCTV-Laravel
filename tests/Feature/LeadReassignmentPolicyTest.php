<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Policies\LeadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Audit M5: a `lead_manager` could reassign a lead they owned to any user.
 *
 * Decided rule (see `LeadPolicy::reassign()`): reassignment requires
 * `manage-settings`, so a lead_manager may not reassign at all, while keeping
 * every other ability over the lead they own.
 *
 * The role x action matrix below is the contract; the HTTP tests after it prove
 * the rule is enforced server-side in both request classes rather than only in
 * the view.
 */
class LeadReassignmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function roleMatrix(): array
    {
        return [
            'super_admin may reassign' => ['superAdmin', true],
            'administrator may reassign' => ['administrator', true],
            'lead_manager may not reassign' => ['leadManager', false],
            'content_editor may not reassign' => ['contentEditor', false],
            'analyst may not reassign' => ['analyst', false],
        ];
    }

    #[DataProvider('roleMatrix')]
    public function test_reassign_ability_per_role(string $factoryState, bool $expected): void
    {
        $actor = User::factory()->{$factoryState}()->create();
        $lead = Lead::factory()->create(['assigned_to' => $actor->id]);

        $this->assertSame(
            $expected,
            (new LeadPolicy())->reassign($actor, $lead),
            "Role [{$factoryState}] reassignability does not match the decided rule."
        );
    }

    public function test_lead_manager_keeps_update_and_view_over_their_own_lead(): void
    {
        $manager = User::factory()->leadManager()->create();
        $lead = Lead::factory()->create(['assigned_to' => $manager->id]);

        $policy = new LeadPolicy();

        $this->assertTrue($policy->view($manager, $lead));
        $this->assertTrue($policy->update($manager, $lead), 'M5 must not reduce the ability to work the lead.');
        $this->assertFalse($policy->reassign($manager, $lead), '...only the ability to hand it away.');
    }

    public function test_reassign_requires_visibility_on_the_lead(): void
    {
        $otherManager = User::factory()->leadManager()->create();
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $otherManager->id]);

        $this->assertTrue((new LeadPolicy())->reassign($admin, $lead));
        $this->assertFalse(
            (new LeadPolicy())->reassign($otherManager, $lead),
            'A lead_manager cannot reassign even a lead they can see through another path.'
        );
    }

    /**
     * HTML form path — `AssignLeadRequest::authorize()`.
     */
    public function test_lead_manager_cannot_reassign_via_the_html_form(): void
    {
        $manager = User::factory()->leadManager()->create();
        $target = User::factory()->leadManager()->create();
        $lead = Lead::factory()->create(['assigned_to' => $manager->id]);

        $this->actingAs($manager)
            ->patch(route('admin.leads-ui.assignee.update', $lead), ['assigned_to' => $target->id])
            ->assertForbidden();

        $this->assertSame($manager->id, $lead->fresh()->assigned_to, 'Assignment must be unchanged.');
    }

    public function test_administrator_can_reassign_via_the_html_form(): void
    {
        $admin = User::factory()->administrator()->create();
        $target = User::factory()->leadManager()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->patch(route('admin.leads-ui.assignee.update', $lead), ['assigned_to' => $target->id])
            ->assertRedirect();

        $this->assertSame($target->id, $lead->fresh()->assigned_to);
    }

    /**
     * JSON path — `UpdateLeadRequest::withValidator()`.
     */
    public function test_lead_manager_cannot_reassign_via_the_json_update(): void
    {
        $manager = User::factory()->leadManager()->create();
        $target = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $manager->id]);

        // The route is PUT (`leads.update`); PATCH would be a 405, not a 422.
        $this->actingAs($manager)
            ->putJson(route('admin.leads.update', $lead), ['assigned_to' => $target->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assigned_to');

        $this->assertSame($manager->id, $lead->fresh()->assigned_to);
    }

    public function test_lead_manager_can_still_update_other_fields_they_own(): void
    {
        $manager = User::factory()->leadManager()->create();
        $lead = Lead::factory()->create([
            'assigned_to' => $manager->id,
            'status' => Lead::STATUS_NEW,
        ]);

        $this->actingAs($manager)
            ->putJson(route('admin.leads.update', $lead), ['notes' => 'Sudah ditelepon'])
            ->assertOk();

        $this->assertSame('Sudah ditelepon', $lead->fresh()->notes);
    }

    /**
     * A round-tripped form that resubmits the *unchanged* assignee is not a
     * reassignment, so it must not 422 for a lead_manager.
     */
    public function test_resubmitting_the_same_assignee_is_not_a_reassignment(): void
    {
        $manager = User::factory()->leadManager()->create();
        $lead = Lead::factory()->create(['assigned_to' => $manager->id]);

        $this->actingAs($manager)
            ->putJson(route('admin.leads.update', $lead), [
                'assigned_to' => $manager->id,
                'notes' => 'tidak berubah',
            ])
            ->assertOk();

        $this->assertSame($manager->id, $lead->fresh()->assigned_to);
    }

    public function test_administrator_can_reassign_via_the_json_update(): void
    {
        $admin = User::factory()->administrator()->create();
        $target = User::factory()->leadManager()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->putJson(route('admin.leads.update', $lead), ['assigned_to' => $target->id])
            ->assertOk();

        $this->assertSame($target->id, $lead->fresh()->assigned_to);
    }
}
