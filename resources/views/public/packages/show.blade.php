@extends('layouts.public')

@section('title', $package->title ?? 'Paket')
@section('meta_description', $package->description ?? '')

@section('content')
<!-- PLACEHOLDER: Package detail. UI not yet implemented (Phase 3 backend only). -->
<p>{{ $package->title ?? '' }}</p>
@endsection
