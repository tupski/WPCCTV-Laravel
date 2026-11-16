<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Page;
use App\Services\SeoMetadataResolver;
use App\Services\StructuredData;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        protected SeoMetadataResolver $seo,
        protected StructuredData $schema,
    ) {}

    public function show(string $slug): View
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->with('seo')
            ->firstOrFail();

        $faqs = Faq::published()->forContext($page)->get();

        $seoData = $this->seo->resolve($page);

        return view('public.pages.show', [
            'page' => $page,
            'faqs' => $faqs,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => $page->title, 'url' => route('pages.show', $page->slug)],
                ]),
                $this->schema->faqPage($faqs),
            ],
        ]);
    }
}
