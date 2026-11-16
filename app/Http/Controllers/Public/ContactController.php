<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicLeadRequest;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Page;
use App\Services\SeoMetadataResolver;
use App\Services\StructuredData;
use App\Support\SiteInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Public contact page and lead capture form.
 *
 * The form writes a `leads` row exactly like the admin "manual lead" path so
 * the existing lead list, policy scoping and follow-up workflow pick it up
 * unchanged. No notification behaviour is added or altered here.
 */
class ContactController extends Controller
{
    public function __construct(
        protected SeoMetadataResolver $seo,
        protected StructuredData $schema,
    ) {}

    public function show(): View
    {
        $faqs = Faq::published()
            ->whereNull('context_type')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $page = Page::published()->where('slug', 'kontak')->first();

        $seoData = $this->seo->resolve($page ?? new Page(), [
            'seo_title' => 'Kontak WP CCTV Indonesia | Konsultasi Instalasi CCTV',
            'meta_description' => 'Hubungi WP CCTV Indonesia melalui WhatsApp '.SiteInfo::whatsappDisplay().' untuk konsultasi, survei lokasi, dan penawaran instalasi CCTV.',
        ]);

        return view('public.contact', [
            'faqs' => $faqs,
            'page' => $page,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->organization(),
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Kontak', 'url' => route('contact.show')],
                ]),
            ],
        ]);
    }

    public function store(StorePublicLeadRequest $request): RedirectResponse
    {
        // Honeypot: accept the request shape but create nothing, so a bot sees
        // the same success page and cannot tell it was filtered.
        if ($request->isSpam()) {
            return $this->confirmation();
        }

        $data = $request->safe()->only(['customer_name', 'whatsapp_number', 'lead_type', 'requirements']);

        $lead = Lead::create([
            'customer_name' => $data['customer_name'],
            'whatsapp_number' => $this->normalizePhone((string) $data['whatsapp_number']),
            'lead_type' => $data['lead_type'] ?? Lead::TYPE_CONSULTATION,
            'requirements' => $data['requirements'],
            'status' => Lead::STATUS_NEW,
            'priority' => 'medium',
        ]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => null,
            'action' => LeadActivity::ACTION_IDENTIFY,
            'field' => 'source',
            'new_value' => 'formulir kontak publik',
            'created_at' => now(),
        ]);

        return $this->confirmation();
    }

    protected function confirmation(): RedirectResponse
    {
        return redirect()
            ->route('contact.show')
            ->with('status', 'Terima kasih. Permintaan Anda sudah kami terima dan akan ditindaklanjuti melalui WhatsApp pada jam operasional.')
            ->with('contact_submitted', true);
    }

    /**
     * Store the number in international form when possible so the lead list is
     * consistent with numbers captured from WhatsApp clicks.
     */
    protected function normalizePhone(string $value): string
    {
        $normalized = SiteInfo::normalizeWhatsapp($value);

        return $normalized !== '' ? $normalized : $value;
    }
}
