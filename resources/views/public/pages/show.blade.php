@extends('layouts.public')

@section('title', $page->title ?? 'Halaman')
@section('meta_description', 'Halaman ' . ($page->title ?? ''))

@section('content')
<!-- PLACEHOLDER: Page detail. UI not yet implemented (Phase 3 backend only). -->
<p>{{ $page->title ?? '' }}</p>
@endsection
