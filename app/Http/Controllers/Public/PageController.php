<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\SeoMetadataResolver;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(protected SeoMetadataResolver $seo)
    {
    }

    public function show(string $slug): View
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->with('seo')
            ->firstOrFail();

        $seoData = $this->seo->resolve($page);

        return view('public.pages.show', compact('page', 'seoData'));
    }
}
