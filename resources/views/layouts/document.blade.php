<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WP CCTV Indonesia')</title>
    <meta name="description" content="@yield('meta_description', 'WP CCTV Indonesia')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'WP CCTV Indonesia')">
    <meta property="og:description" content="@yield('meta_description', 'WP CCTV Indonesia')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-brand-background text-slate-700 antialiased">
    @yield('body')
</body>
</html>
