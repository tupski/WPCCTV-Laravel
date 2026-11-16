@php
    // The key arrives from the controller; this partial never queries. The
    // widget only asks for permission after the button is pressed, so loading
    // the page is silent.
    $vapidPublicKey = $vapidPublicKey ?? null;
@endphp

@if($vapidPublicKey)
    <section
        class="mt-4 border-t border-slate-200 pt-4"
        data-controller="push-subscription"
        data-push-subscription-index-url-value="{{ route('admin.push-subscriptions.index') }}"
        data-push-subscription-subscribe-url-value="{{ route('admin.push-subscriptions.store') }}"
        data-push-subscription-unsubscribe-url-value="{{ route('admin.push-subscriptions.destroy', ['subscription' => '__ID__']) }}"
        data-push-subscription-application-server-key-value="{{ $vapidPublicKey }}"
        data-push-subscription-csrf-token-value="{{ csrf_token() }}"
    >
        <h3 class="text-sm font-semibold text-brand-navy">Status langganan di browser ini</h3>
        <p class="mt-1 text-sm text-slate-600" data-push-subscription-target="status">
            Memeriksa status notifikasi&hellip;
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            <button
                type="button"
                class="min-h-11 rounded-md bg-brand-blue px-4 text-sm font-semibold text-white"
                data-push-subscription-target="subscribeButton"
                data-action="push-subscription#register"
            >
                Aktifkan notifikasi di perangkat ini
            </button>
            <button
                type="button"
                class="min-h-11 rounded-md border border-slate-300 px-4 text-sm font-semibold text-slate-700"
                data-push-subscription-target="unsubscribeButton"
                data-action="push-subscription#unregister"
                hidden
            >
                Nonaktifkan notifikasi
            </button>
        </div>
        <p class="mt-2 text-xs text-slate-600">
            Izin browser hanya diminta setelah tombol ditekan. Notifikasi dipakai untuk memberi tahu klik WhatsApp baru dan perubahan status lead.
        </p>
    </section>
@endif
