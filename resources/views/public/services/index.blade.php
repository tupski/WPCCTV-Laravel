@extends('layouts.public')

@section('title', 'Layanan')
@section('meta_description', 'Layanan instalasi dan perbaikan CCTV')

@section('content')
<!-- PLACEHOLDER: Public services. UI not yet implemented (Phase 3 backend only). -->
@if($services->count() > 0)
    @foreach($services as $service)
        <p>{{ $service->title }}</p>
    @endforeach
@else
    <p>Belum ada layanan.</p>
@endif
@endsection
