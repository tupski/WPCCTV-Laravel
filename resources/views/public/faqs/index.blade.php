@extends('layouts.public')

@section('title', 'FAQ')
@section('meta_description', 'Pertanyaan yang sering diajukan')

@section('content')
<!-- PLACEHOLDER: Public FAQ listing. UI not yet implemented (Phase 3 backend only). -->
<p>Daftar FAQ: {{ $faqs->count() }}</p>
@endsection
