<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Models\WhatsAppClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Server-rendered lead workspace: policy scoping, the four write actions,
 * and the single-conversion rule.
 */
class AdminLeadUiTest extends TestCase
{
    use RefreshDatabase;

    public static function deniedRoles(): array
    {
        return [
            'content_editor' => [User::ROLE_CONTENT_EDITOR],
            'analyst' => [User::ROLE_ANALYST],
        ];
    }

    public function test_lead_index_renders_for_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Lead::factory()->create(['customer_name' => 'Agus Prasetyo', 'assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('admin.leads-ui.index'))
            ->assertOk()
            ->assertSee('Agus Prasetyo')
            ->assertSee('Filter status lead')
            ->assertSee('<th scope="col"', false);
    }

    public function test_lead_index_renders_for_lead_manager(): void
    {
        $manager = User::factory()->leadManager()->create();

        $this->actingAs($manager)
            ->get(route('admin.leads-ui.index'))
            ->assertOk();
    }

    #[DataProvider('deniedRoles')]
    public function test_lead_index_is_forbidden_for_the_wrong_role(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('admin.leads-ui.index'))
            ->assertForbidden();
    }

    public function test_lead_manager_only_sees_their_own_leads(): void
    {
        $manager = User::factory()->leadManager()->create();
        $other = User::factory()->leadManager()->create();

        Lead::factory()->create(['customer_name' => 'Lead Milik Saya', 'assigned_to' => $manager->id]);
        Lead::factory()->create(['customer_name' => 'Lead Orang Lain', 'assigned_to' => $other->id]);

        $this->actingAs($manager)
            ->get(route('admin.leads-ui.index'))
            ->assertOk()
            ->assertSee('Lead Milik Saya')
            ->assertDontSee('Lead Orang Lain');
    }

    public function test_lead_manager_cannot_open_another_managers_lead(): void
    {
        $manager = User::factory()->leadManager()->create();
        $foreign = Lead::factory()->create([
            'assigned_to' => User::factory()->leadManager()->create()->id,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.leads-ui.show', $foreign))
            ->assertForbidden();
    }

    public function test_lead_detail_shows_origin_and_newest_first_timeline(): void
    {
        $admin = User::factory()->administrator()->create();

        $click = WhatsAppClick::factory()->fromPackage()->create([
            'page_title' => 'Paket CCTV 4 Kamera Rumah',
            'device_category' => 'mobile',
        ]);

        $lead = Lead::factory()->create([
            'customer_name' => 'Dewi Lestari',
            'assigned_to' => $admin->id,
            'whatsapp_click_id' => $click->id,
            'follow_up_at' => now()->addDays(2),
        ]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $admin->id,
            'action' => LeadActivity::ACTION_NOTE,
            'new_value' => 'Pelanggan minta survei lokasi gudang.',
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.leads-ui.show', $lead))
            ->assertOk()
            ->assertSee('Paket CCTV 4 Kamera Rumah')
            ->assertSee('Halaman paket')
            ->assertSee('Pelanggan minta survei lokasi gudang.')
            ->assertSee('Terbaru')
            ->assertSee('Jadwal tindak lanjut');
    }

    public function test_status_change_persists_and_writes_a_lead_activity(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['status' => Lead::STATUS_NEW, 'assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->patch(route('admin.leads-ui.status.update', $lead), ['status' => Lead::STATUS_QUALIFIED])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(Lead::STATUS_QUALIFIED, $lead->fresh()->status);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'user_id' => $admin->id,
            'action' => LeadActivity::ACTION_STATUS_CHANGE,
            'old_value' => Lead::STATUS_NEW,
            'new_value' => Lead::STATUS_QUALIFIED,
        ]);
    }

    public function test_assignment_history_resolves_names_without_a_query_per_row(): void
    {
        $admin = User::factory()->administrator()->create();
        $first = User::factory()->leadManager()->create(['name' => 'Budi Penilai']);
        $second = User::factory()->leadManager()->create(['name' => 'Rina Penilai']);

        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        // Twelve reassignment rows would be twenty-four lookups if names were
        // resolved inside the row mapper.
        for ($i = 0; $i < 12; $i++) {
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => $admin->id,
                'action' => LeadActivity::ACTION_ASSIGN,
                'field' => 'assigned_to',
                'old_value' => (string) $first->id,
                'new_value' => (string) $second->id,
                'created_at' => now()->subMinutes($i),
            ]);
        }

        DB::enableQueryLog();
        $response = $this->actingAs($admin)->get(route('admin.leads-ui.show', $lead));
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk()
            ->assertSee('Budi Penilai')
            ->assertSee('Rina Penilai');

        $this->assertLessThan(20, $queries, 'Timeline merender terlalu banyak query: '.$queries);
    }

    public function test_status_change_rejects_an_unknown_status(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->patch(route('admin.leads-ui.status.update', $lead), ['status' => 'teleported'])
            ->assertSessionHasErrors('status');

        $this->assertSame(Lead::STATUS_NEW, $lead->fresh()->status);
    }

    public function test_note_is_stored_on_the_lead_timeline(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('admin.leads-ui.activities.store', $lead), [
                'note' => 'Sudah dikirim penawaran 8 kamera dan biaya kabel.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'user_id' => $admin->id,
            'action' => LeadActivity::ACTION_NOTE,
            'new_value' => 'Sudah dikirim penawaran 8 kamera dan biaya kabel.',
        ]);
    }

    public function test_note_requires_a_body(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('admin.leads-ui.activities.store', $lead), ['note' => ''])
            ->assertSessionHasErrors('note');
    }

    public function test_assignment_persists_and_is_audited(): void
    {
        $admin = User::factory()->administrator()->create();
        $manager = User::factory()->leadManager()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->patch(route('admin.leads-ui.assignee.update', $lead), ['assigned_to' => $manager->id])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame($manager->id, $lead->fresh()->assigned_to);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_ASSIGN,
            'field' => 'assigned_to',
            'new_value' => (string) $manager->id,
        ]);
    }

    public function test_follow_up_date_persists_and_is_audited(): void
    {
        $admin = User::factory()->administrator()->create();
        $lead = Lead::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->patch(route('admin.leads-ui.follow-up.update', $lead), ['follow_up_at' => '2026-12-01'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('2026-12-01', $lead->fresh()->follow_up_at->format('Y-m-d'));

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_FOLLOWUP,
            'field' => 'follow_up_at',
        ]);
    }

    public function test_conversion_form_renders_for_an_unconverted_click(): void
    {
        $admin = User::factory()->administrator()->create();
        $click = WhatsAppClick::factory()->fromProduct()->create([
            'page_title' => 'Kamera IP Bullet 5MP',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.leads-ui.convert.form', $click))
            ->assertOk()
            ->assertSee('Buat lead dari klik WhatsApp')
            ->assertSee('Kamera IP Bullet 5MP')
            ->assertSee('name="customer_name"', false);
    }

    public function test_conversion_creates_a_lead_and_redirects_to_it(): void
    {
        $admin = User::factory()->administrator()->create();
        $click = WhatsAppClick::factory()->fromProduct()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.leads-ui.convert.store', $click), [
                'customer_name' => 'Rudi Hartono',
                'whatsapp_number' => '6281234567890',
                'lead_type' => Lead::TYPE_INSTALLATION,
            ]);

        $lead = Lead::firstOrFail();

        $response->assertRedirect(route('admin.leads-ui.show', $lead))
            ->assertSessionHas('status');

        $this->assertSame('Rudi Hartono', $lead->customer_name);
        $this->assertSame($click->id, $lead->whatsapp_click_id);
        $this->assertSame($lead->id, $click->fresh()->converted_lead_id);
    }

    public function test_already_converted_click_redirects_instead_of_showing_a_second_form(): void
    {
        $admin = User::factory()->administrator()->create();
        $click = WhatsAppClick::factory()->fromProduct()->create();

        $lead = app(\App\Services\LeadConversionService::class)
            ->convert($click, ['customer_name' => 'Konversi Pertama'], $admin);

        $response = $this->actingAs($admin)
            ->get(route('admin.leads-ui.convert.form', $click->fresh()));

        $response->assertRedirect(route('admin.leads-ui.show', $lead))
            ->assertSessionHas('status');

        $this->assertStringNotContainsString(
            'name="customer_name"',
            $response->getContent() ?? '',
        );
    }

    public function test_double_conversion_post_does_not_create_a_second_lead(): void
    {
        $admin = User::factory()->administrator()->create();
        $click = WhatsAppClick::factory()->fromProduct()->create();

        $this->actingAs($admin)->post(route('admin.leads-ui.convert.store', $click), [
            'customer_name' => 'Konversi Pertama',
        ]);

        $this->actingAs($admin)->post(route('admin.leads-ui.convert.store', $click->fresh()), [
            'customer_name' => 'Percobaan Kedua',
        ])->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, Lead::count());
        $this->assertSame('Konversi Pertama', Lead::firstOrFail()->customer_name);
    }

    /**
     * The UI must contain no emoji and no placeholder filler text.
     *
     * Every field that reaches the page is pinned to a literal, because the
     * point of the assertion is to inspect the *template*. `LeadFactory`
     * generates `requirements` with `fake()->paragraph()`, and Faker's Text
     * provider falls back to the English base text — which is the literal
     * "Lorem ipsum dolor sit amet…" passage — for any locale without a
     * translated Text provider. Leaving that random value in the render path
     * made this test fail intermittently on the word "lorem" while the
     * template was perfectly correct (the same class of defect as the
     * `latest()` tie: a nondeterministic input reaching an assertion).
     *
     * Pinning the fixture keeps the assertion exactly as strict as it was and
     * removes the randomness that made it flap.
     */
    public function test_lead_detail_contains_no_emoji_and_no_lorem(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->create([
            'assigned_to' => $admin->id,
            'customer_name' => 'Pelanggan Uji',
            'whatsapp_number' => '+6281234567890',
            'requirements' => 'Empat kamera outdoor dan satu DVR delapan kanal.',
            'notes' => 'Dihubungi pagi, minta survei lokasi.',
        ]);

        $html = $this->actingAs($admin)->get(route('admin.leads-ui.show', $lead))->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]/u',
            $html,
        );
        $this->assertStringNotContainsStringIgnoringCase('lorem', $html);
    }
}
