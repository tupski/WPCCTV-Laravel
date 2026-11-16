@extends('layouts.public')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6 lg:py-32">
        <p class="text-sm font-semibold uppercase tracking-widest text-brand-blue">404</p>
        <h1 class="mt-3 text-3xl font-bold text-brand-navy sm:text-4xl">Halaman Tidak Ditemukan</h1>
        <p class="mx-auto mt-4 max-w-md text-sm leading-7 text-slate-600">Halaman yang Anda cari sudah dipindahkan, dihapus, atau alamatnya salah ketik. Coba mulai dari beranda, atau tanyakan langsung kebutuhan Anda.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-brand-navy px-5 py-2.5 text-sm font-semibold text-brand-navy transition hover:bg-brand-light">Kembali ke Beranda</a>
            @include('public.partials.whatsapp-cta', ['waLabel' => 'Tanya via WhatsApp'])
        </div>
    </section>
@endsection
