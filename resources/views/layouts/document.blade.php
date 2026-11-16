434<!doctype html>
<html lang="id">
<head>
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
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:type" content="{{ $seoOgType }}">
    <meta property="og:title" content="{{ $seoOgTitle }}">
    <meta property="og:description" content="{{ $seoOgDescription }}">
    @if($seoOgImage)
        <meta property="og:image" content="{{ $seoOgImage }}">
    @endif
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta name="twitter:card" content="{{ $seoTwitterCard }}">
    <meta name="twitter:title" content="{{ $seoTwitterTitle }}">
    <meta name="twitter:description" content="{{ $seoTwitterDescription }}">
    @if($seoTwitterImage)
        <meta name="twitter:image" content="{{ $seoTwitterImage }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-brand-background text-slate-700 antialiased">
    @yield('body')
</body>
</html>
