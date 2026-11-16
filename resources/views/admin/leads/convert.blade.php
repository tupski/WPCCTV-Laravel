@extends('layouts.admin')
@use('App\Support\Labels')

@section('title', 'Buat lead dari klik WhatsApp | WP CCTV Indonesia')
@section('page_title', 'Buat lead dari klik WhatsApp')

@section('page_subtitle')
    Klik pada {{ $when }} dari {{ $pageType }} lewat {{ $device }}.
@endsection

@section('page_actions')
    <a href="{{ route('admin.leads-ui.index') }}" class="inline-flex min-h-10 items-center text-sm font-medium text-brand-blue">Kembali ke daftar lead</a>
@endsection

@section('content')
    <div class="max-w-2xl">
        <dl class="grid gap-x-8 gap-y-2 border-t border-slate-200 pt-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-slate-600">Halaman asal klik</dt>
                <dd class="mt-0.5 text-slate-800">{{ $click->page_title ?: $click->page_url }}</dd>
            </div>
            <div>
                <dt class="text-slate-600">Alamat halaman</dt>
                <dd class="mt-0.5 break-all text-slate-800">{{ $click->page_url }}</dd>
            </div>
        </dl>

        <p class="mt-6 text-sm text-slate-700">
            Lengkapi data yang sudah diketahui dari percakapan. Kolom yang belum pasti boleh dikosongkan; isi sisanya setelah pelanggan membalas.
        </p>

        <form method="POST" action="{{ route('admin.leads-ui.convert.store', $click) }}" class="mt-6 space-y-5 border-t border-slate-200 pt-6">
            @csrf

            <div>
                <label for="customer_name" class="block text-sm font-medium text-slate-700">Nama pelanggan</label>
                <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    value="{{ old('customer_name') }}"
                    class="mt-1 min-h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700"
                >
            </div>

            <div>
                <label for="whatsapp_number" class="block text-sm font-medium text-slate-700">Nomor WhatsApp</label>
                <input
                    type="text"
                    id="whatsapp_number"
                    name="whatsapp_number"
                    value="{{ old('whatsapp_number') }}"
                    class="mt-1 min-h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700"
                >
                <p class="mt-1 text-xs text-slate-600">Tulis dengan kode negara, contoh 6281234567890.</p>
            </div>

            <div>
                <label for="lead_type" class="block text-sm font-medium text-slate-700">Jenis kebutuhan</label>
                <select id="lead_type" name="lead_type" class="mt-1 min-h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700">
                    <option value="">Belum ditentukan</option>
                    @foreach($leadTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('lead_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="requirements" class="block text-sm font-medium text-slate-700">Kebutuhan yang disampaikan</label>
                <textarea
                    id="requirements"
                    name="requirements"
                    rows="4"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700"
                    placeholder="Jumlah kamera, luas area, atau keluhan perangkat yang ada."
                >{{ old('requirements') }}</textarea>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="min-h-11 rounded-md bg-brand-blue px-4 text-sm font-semibold text-white">Buat lead</button>
                <a href="{{ route('admin.leads-ui.index') }}" class="inline-flex min-h-11 items-center px-2 text-sm font-medium text-slate-700">Batal</a>
            </div>

            <p class="text-xs text-slate-600">
                Lead yang dibuat akan ditugaskan kepada Anda dan tercatat pada log aktivitas.
            </p>
        </form>
    </div>
@endsection
