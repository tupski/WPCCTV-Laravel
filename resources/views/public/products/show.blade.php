@extends('layouts.public')

@section('title', $product->name ?? 'Produk')
@section('meta_description', $product->description ?? '')

@section('content')
<!-- PLACEHOLDER: Product detail. UI not yet implemented (Phase 3 backend only). -->
<p>{{ $product->name ?? '' }}</p>
@endsection
