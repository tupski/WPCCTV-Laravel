@extends('layouts.admin')
@use('App\Support\Format')

@section('title', 'Pengaturan | WP CCTV Indonesia')
@section('page_title', 'Pengaturan')
@section('page_subtitle', 'Nomor WhatsApp tujuan, kunci notifikasi publik, dan masa simpan data analitik.')

@section('content')
    <form method="POST" action="{{ route('admin.settings-ui.update') }}" class="max-w-2xl space-y-10">
        @csrf
        @method('PUT')

        <section aria-labelledby="whatsapp-heading">
            <h2 id="whatsapp-heading" class="text-base font-semibold text-brand-navy">WhatsApp</h2>
            <div class="mt-3 border-t border-slate-200 pt-3">
                <label for="whatsapp_number" class="block text-sm font-medium text-slate-700">Nomor WhatsApp tujuan</label>
                <input
                    type="text"
                    id="whatsapp_number"
                    name="whatsapp_number"
                    value="{{ old('whatsapp_number', $whatsappNumber) }}"
                    inputmode="numeric"
                    class="mt-1 min-h-10 w-full max-w-sm rounded-md border border-slate-300 px-3 text-sm text-slate-700"
                >
                <p class="mt-1 text-sm text-slate-600">
                    Tulis 8 sampai 15 digit dengan kode negara, contoh 6281234567890. Nomor ini dipakai semua tombol WhatsApp di situs.
                </p>
            </div>
        </section>

        <section aria-labelledby="vapid-heading">
            <h2 id="vapid-heading" class="text-base font-semibold text-brand-navy">Kunci notifikasi browser</h2>
            <div class="mt-3 border-t border-slate-200 pt-3">
                <label for="vapid_public_key" class="block text-sm font-medium text-slate-700">Kunci publik VAPID</label>
                <div class="mt-1 flex max-w-xl items-stretch gap-2">
                    <input
                        type="text"
                        id="vapid_public_key"
                        value="{{ $vapidPublicKey ?? 'Belum dibuat' }}"
                        readonly
                        class="min-h-10 w-full rounded-md border border-slate-300 bg-slate-50 px-3 font-mono text-xs text-slate-700"
                    >
                    @if($vapidPublicKey)
                        <button
                            type="button"
                            class="min-h-10 shrink-0 rounded-md border border-slate-300 px-3 text-sm font-semibold text-brand-navy"
                            data-copy-target="vapid_public_key"
                        >Salin</button>
                    @endif
                </div>
                <p class="mt-1 text-sm text-slate-600">
                    Kunci ini aman dibagikan karena hanya dipakai browser untuk mendaftar notifikasi. Kunci privat tersimpan di berkas <code>.env</code> server dan tidak ditampilkan di sini.
                </p>
                @if(! $vapidPublicKey)
                    <p class="mt-1 text-sm text-slate-600">
                        Jalankan perintah <code>php artisan vapid:generate</code> di server untuk membuat pasangan kunci.
                    </p>
                @endif
            </div>
        </section>

        <section aria-labelledby="retensi-heading">
            <h2 id="retensi-heading" class="text-base font-semibold text-brand-navy">Masa simpan data</h2>
            <p class="mt-1 text-sm text-slate-600">
                Data yang lebih tua dari batas ini dihapus otomatis oleh pembersihan harian. Nilai dalam hari.
            </p>

            <div class="mt-3 grid gap-4 border-t border-slate-200 pt-3 sm:grid-cols-3">
                <div>
                    <label for="retention_page_views_days" class="block text-sm font-medium text-slate-700">Kunjungan halaman</label>
                    <input
                        type="number"
                        id="retention_page_views_days"
                        name="retention[page_views_days]"
                        value="{{ old('retention.page_views_days', $retention['page_views_days']) }}"
                        min="7"
                        max="3650"
                        class="mt-1 min-h-10 w-full rounded-md border border-slate-300 px-3 text-sm tabular-nums text-slate-700"
                    >
                    <p class="mt-1 text-xs text-slate-600">Saat ini {{ Format::number($retention['page_views_days']) }} hari.</p>
                </div>

                <div>
                    <label for="retention_whatsapp_clicks_days" class="block text-sm font-medium text-slate-700">Klik WhatsApp</label>
                    <input
                        type="number"
                        id="retention_whatsapp_clicks_days"
                        name="retention[whatsapp_clicks_days]"
                        value="{{ old('retention.whatsapp_clicks_days', $retention['whatsapp_clicks_days']) }}"
                        min="7"
                        max="3650"
                        class="mt-1 min-h-10 w-full rounded-md border border-slate-300 px-3 text-sm tabular-nums text-slate-700"
                    >
                    <p class="mt-1 text-xs text-slate-600">Saat ini {{ Format::number($retention['whatsapp_clicks_days']) }} hari.</p>
                </div>

                <div>
                    <label for="retention_activity_logs_days" class="block text-sm font-medium text-slate-700">Log aktivitas</label>
                    <input
                        type="number"
                        id="retention_activity_logs_days"
                        name="retention[activity_logs_days]"
                        value="{{ old('retention.activity_logs_days', $retention['activity_logs_days']) }}"
                        min="7"
                        max="3650"
                        class="mt-1 min-h-10 w-full rounded-md border border-slate-300 px-3 text-sm tabular-nums text-slate-700"
                    >
                    <p class="mt-1 text-xs text-slate-600">Saat ini {{ Format::number($retention['activity_logs_days']) }} hari.</p>
                </div>
            </div>
        </section>

        <div class="border-t border-slate-200 pt-4">
            <button type="submit" class="min-h-11 rounded-md bg-brand-blue px-5 text-sm font-semibold text-white">Simpan pengaturan</button>
        </div>
    </form>

    @push('scripts')
        <script>
            // Copy affordance for the public key. No framework, no prompt on load.
            document.querySelectorAll('[data-copy-target]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var field = document.getElementById(button.dataset.copyTarget);
                    if (!field) { return; }
                    field.select();
                    if (navigator.clipboard) { navigator.clipboard.writeText(field.value); }
                    button.textContent = 'Tersalin';
                });
            });
        </script>
    @endpush
@endsection
