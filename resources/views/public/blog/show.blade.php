@extends('layouts.public')

@section('title', $article->title ?? 'Artikel')
@section('meta_description', $article->excerpt ?? '')

@section('content')
<!-- PLACEHOLDER: Blog article detail. UI not yet implemented (Phase 3 backend only). -->
<p>{{ $article->title ?? '' }}</p>
@endsection
