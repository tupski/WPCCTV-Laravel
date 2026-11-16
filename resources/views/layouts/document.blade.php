@php
    // Central SEO head: resolver output ($seoData) wins, then per-page
    // yields, then config('seo.defaults'), then url()->current().
    $seoDefaults = config('seo.defaults', []);
    $seoTitle = $seoData['seo_title'] ?? null
        ?? $__env->yieldContent('title') ?: ($seoDefaults['seo_title'] ?? 'WP CCTV Indonesia');
    $seoDescription = $seoData['meta_description'] ?? null
        ?? $__env->yieldContent('meta_description') ?: ($seoDefaults['meta_description'] ?? 'WP CCTV Indonesia');
    $seoCanonical = $seoData['canonical'] ?? null
        ?? ($seoDefaults['canonical'] ?? null)
        ?? url()->current();
    $seoRobots = $seoData['robots'] ?? $seoDefaults['robots'] ?? 'index, follow';
    $seoOgTitle = $seoData['og_title'] ?? $seoTitle;
    $seoOgDescription = $seoData['og_description'] ?? $seoDescription;
    $seoOgImage = $seoData['og_image'] ?? null;
    $seoOgType = $seoData['og_type'] ?? $seoDefaults['og_type'] ?? 'website';
    $seoTwitterCard = $seoData['twitter_card'] ?? $seoDefaults['twitter_card'] ?? 'summary_large_image';
    $seoTwitterTitle = $seoData['twitter_title'] ?? $seoTitle;
    $seoTwitterDescription = $seoData['twitter_description'] ?? $seoDescription;
    $seoTwitterImage = $seoData['twitter_image'] ?? $seoOgImage;
    $jsonLdBlocks = array_filter($jsonLd ?? [], fn ($node) => is_array($node));
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:site_name" content="{{ \App\Support\SiteInfo::name() }}">
    <meta property="og:type" content="{{ $seoOgType }}">
    <meta property="og:title" content="{{ $seoOgTitle }}">
    <meta property="og:description" content="{{ $seoOgDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:locale" content="id_ID">
    @if($seoOgImage)
        <meta property="og:image" content="{{ $seoOgImage }}">
    @endif
    <meta name="twitter:card" content="{{ $seoTwitterCard }}">
    <meta name="twitter:title" content="{{ $seoTwitterTitle }}">
    <meta name="twitter:description" content="{{ $seoTwitterDescription }}">
    @if($seoTwitterImage)
        <meta name="twitter:image" content="{{ $seoTwitterImage }}">
    @endif
    {{-- JSON-LD must be emitted raw: {{ }} would escape the quotes and produce
         invalid structured data. The HEX_TAG/HEX_AMP/HEX_APOS/HEX_QUOT flags
         make the JSON itself inert inside a <script> element, so strings from
         the database cannot break out of the tag. --}}
    @foreach($jsonLdBlocks as $block)
        <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endforeach
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-brand-background text-slate-700 antialiased">
    @yield('body')
    @stack('scripts')
</body>
</html>
