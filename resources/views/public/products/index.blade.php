@extends('layouts.public')

@section('title', 'Produk')
@section('meta_description', 'Katalog produk CCTV')

@section('content')
<!-- PLACEHOLDER: Public product catalog. UI not yet implemented (Phase 3 backend only). -->
@if($products->count() > 0)
    @foreach($products as $product)
        <p>{{ $product->name }}</p>
    @endforeach
@else
    <p>Belum ada produk.</p>
@endif
@endsection
