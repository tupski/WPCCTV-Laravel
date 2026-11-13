@extends('layouts.public')

@section('title', $service->title ?? 'Layanan')
@section('meta_description', $service->description ?? '')

@section('content')
<!-- PLACEHOLDER: Service detail. UI not yet implemented (Phase 3 backend only). -->
<p>{{ $service->title ?? '' }}</p>
@endsection
