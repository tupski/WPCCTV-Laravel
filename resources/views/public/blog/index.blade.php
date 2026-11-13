@extends('layouts.public')

@section('title', 'Blog')
@section('meta_description', 'Artikel blog WP CCTV Indonesia')

@section('content')
<!-- PLACEHOLDER: Public blog listing. UI not yet implemented (Phase 3 backend only). -->
@if($articles->count() > 0)
    @foreach($articles as $article)
        <p>{{ $article->title }}</p>
    @endforeach
@else
    <p>Belum ada artikel.</p>
@endif
@endsection
