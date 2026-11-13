<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\SeoMetadataResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __construct(protected SeoMetadataResolver $seo)
    {
    }

    public function index(Request $request): View
    {
        $query = Package::published()->with(['brand', 'seo']);

        if ($categorySlug = $request->input('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($usageSlug = $request->input('usage')) {
            $query->whereHas('usageCategories', fn ($q) => $q->where('slug', $usageSlug));
        }

        $packages = $query->latest()->paginate(12)->appends($request->query());

        $seoData = $this->seo->resolve($packages->first() ?? new Package(['title' => 'Paket']));

        return view('public.packages.index', compact('packages', 'seoData'));
    }

    public function show(string $slug): View
    {
        $package = Package::published()
            ->where('slug', $slug)
            ->with(['brand', 'seo', 'categories', 'usageCategories'])
            ->firstOrFail();

        $seoData = $this->seo->resolve($package);

        return view('public.packages.show', compact('package', 'seoData'));
    }
}
