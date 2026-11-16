<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\StructuredData;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __construct(protected StructuredData $schema) {}

    public function index(): View
    {
        // Global FAQs only: context-bound questions belong to the product,
        // package or service page they explain, and are rendered there.
        $faqs = Faq::published()
            ->whereNull('context_type')
            ->orderBy('sort_order')
            ->get();

        return view('public.faqs.index', [
            'faqs' => $faqs,
            'jsonLd' => [
                $this->schema->faqPage($faqs),
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'FAQ', 'url' => route('faqs.index')],
                ]),
            ],
        ]);
    }
}
