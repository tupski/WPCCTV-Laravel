@extends('layouts.public')

@section('title', 'Paket')
@section('meta_description', 'Paket layanan instalasi CCTV')

@section('content')
<!-- PLACEHOLDER: Public package catalog. UI not yet implemented (Phase 3 backend only). -->
@if($packages->count() > 0)
    @foreach($packages as $package)
        <p>{{ $package->title }}</p>
    @endforeach
@else
    <p>Belum ada paket.</p>
@endif
@endsection
