@extends('layouts.admin')

@section('title', 'Notifikasi | WP CCTV Indonesia')
@section('page_title', 'Notifikasi')
@section('page_subtitle', 'Langganan notifikasi browser pada perangkat ini dan preferensi pemberitahuan untuk akun Anda.')

@section('content')
    <div class="max-w-2xl space-y-10">
        <section aria-labelledby="perangkat-heading">
            <h2 id="perangkat-heading" class="text-base font-semibold text-brand-navy">Perangkat ini</h2>
            <p class="mt-1 text-sm text-slate-600">
                Izin browser hanya diminta setelah Anda menekan tombol di bawah. Selama tombol belum ditekan, tidak ada permintaan izin yang muncul.
            </p>

            @if($pushCapable)
                @include('admin.partials.push-subscription')
            @else
                @include('admin.partials.empty-state', [
                    'message' => 'Kunci publik VAPID belum dibuat di server, sehingga notifikasi browser belum dapat diaktifkan. Jalankan php artisan vapid:generate lalu muat ulang halaman ini.',
                    'actionUrl' => route('admin.settings-ui.edit'),
                    'actionLabel' => 'Buka pengaturan',
                ])
            @endif
        </section>

        <section aria-labelledby="preferensi-heading">
            <h2 id="preferensi-heading" class="text-base font-semibold text-brand-navy">Preferensi pemberitahuan</h2>
            <p class="mt-1 text-sm text-slate-600">
                Pengaturan ini berlaku untuk akun Anda di semua perangkat yang sudah berlangganan.
            </p>

            <form method="POST" action="{{ route('admin.push-settings.update') }}" class="mt-3 border-t border-slate-200 pt-3">
                @csrf
                @method('PUT')

                <div class="flex items-start gap-3 py-3">
                    <input
                        type="hidden"
                        name="whatsapp_clicks_enabled"
                        value="0"
                    >
                    <input
                        type="checkbox"
                        id="whatsapp_clicks_enabled"
                        name="whatsapp_clicks_enabled"
                        value="1"
                        @checked($preferences->whatsapp_clicks_enabled)
                        class="mt-1 h-5 w-5 rounded border-slate-300 text-brand-blue"
                    >
                    <div>
                        <label for="whatsapp_clicks_enabled" class="block text-sm font-medium text-slate-700">Klik WhatsApp baru</label>
                        <p class="mt-1 text-sm text-slate-600">
                            Beri tahu saya saat pengunjung menekan tombol WhatsApp, beserta halaman asal klik tersebut.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 border-t border-slate-200 py-3">
                    <input
                        type="hidden"
                        name="lead_updates_enabled"
                        value="0"
                    >
                    <input
                        type="checkbox"
                        id="lead_updates_enabled"
                        name="lead_updates_enabled"
                        value="1"
                        @checked($preferences->lead_updates_enabled)
                        class="mt-1 h-5 w-5 rounded border-slate-300 text-brand-blue"
                    >
                    <div>
                        <label for="lead_updates_enabled" class="block text-sm font-medium text-slate-700">Perubahan status lead</label>
                        <p class="mt-1 text-sm text-slate-600">
                            Beri tahu saya saat lead berpindah status, misalnya menjadi kualifikasi atau deal.
                        </p>
                    </div>
                </div>

                <button type="submit" class="mt-4 min-h-11 rounded-md bg-brand-blue px-5 text-sm font-semibold text-white">Simpan preferensi</button>
            </form>
        </section>
    </div>
@endsection
