import { Controller } from '@hotwired/stimulus';

/**
 * Admin web-push subscription lifecycle.
 *
 * Reads the VAPID public key from `data-application-server-key`, registers
 * `/sw.js`, and syncs the resulting subscription with
 * `POST admin/push-subscriptions`.
 */
export class PushSubscriptionController extends Controller {
    static targets = ['status', 'subscribeButton', 'unsubscribeButton'];

    static values = {
        indexUrl: String,
        subscribeUrl: String,
        // Contains the `__ID__` placeholder replaced with the row id.
        unsubscribeUrl: String,
        applicationServerKey: String,
        csrfToken: String,
    };

    connect() {
        this.boundOnPushSubscriptionChanged = this.onPushSubscriptionChanged.bind(this);
        this.boundOnTurboLoad = this.status.bind(this);

        // Turbo does not re-run connect() on cached restores; re-sync manually
        // and guard against duplicate listeners.
        if (!this.element.dataset.pushListenersBound) {
            navigator.serviceWorker?.addEventListener?.(
                'message',
                this.boundOnPushSubscriptionChanged,
            );
            document.addEventListener('turbo:load', this.boundOnTurboLoad);
            this.element.dataset.pushListenersBound = 'true';
        }

        this.status();
    }

    disconnect() {
        if (this.element.dataset.pushListenersBound === 'true') {
            navigator.serviceWorker?.removeEventListener?.(
                'message',
                this.boundOnPushSubscriptionChanged,
            );
            document.removeEventListener('turbo:load', this.boundOnTurboLoad);
            delete this.element.dataset.pushListenersBound;
        }
    }

    /**
     * Show the current browser subscription state.
     */
    async status() {
        if (!this.supported) {
            this.setStatus('Browser ini tidak mendukung notifikasi push.');
            return;
        }

        const registration = await navigator.serviceWorker.getRegistration('/sw.js');
        const subscription = registration ? await registration.pushManager.getSubscription() : null;

        this.setStatus(subscription ? 'Notifikasi push aktif di perangkat ini.' : 'Notifikasi push belum aktif.');
        this.toggleButtons(Boolean(subscription));
    }

    /**
     * Register the service worker, subscribe, and persist the subscription.
     */
    async register() {
        if (!this.supported) {
            this.setStatus('Browser ini tidak mendukung notifikasi push.');
            return;
        }

        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            this.setStatus('Izin notifikasi ditolak. Aktifkan lewat pengaturan browser.');
            return;
        }

        try {
            await navigator.serviceWorker.register('/sw.js', { scope: '/' });
            const registration = await navigator.serviceWorker.ready;

            const existing = await registration.pushManager.getSubscription();
            const subscription =
                existing ??
                (await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.applicationServerKeyBytes,
                }));

            await this.persist(subscription);
            this.setStatus('Notifikasi push berhasil diaktifkan.');
            this.toggleButtons(true);
        } catch (error) {
            console.error('[push] subscribe failed', error);
            this.setStatus('Gagal mengaktifkan notifikasi push.');
        }
    }

    /**
     * Unsubscribe in the browser and remove the server-side row.
     */
    async unregister() {
        if (!this.supported) {
            return;
        }

        try {
            const registration = await navigator.serviceWorker.getRegistration('/sw.js');
            const subscription = registration
                ? await registration.pushManager.getSubscription()
                : null;

            if (subscription) {
                await this.destroy(subscription.endpoint);
                await subscription.unsubscribe();
            }

            this.setStatus('Notifikasi push dinonaktifkan.');
            this.toggleButtons(false);
        } catch (error) {
            console.error('[push] unsubscribe failed', error);
            this.setStatus('Gagal menonaktifkan notifikasi push.');
        }
    }

    onPushSubscriptionChanged(event) {
        if (event.data && event.data.type === 'push-subscription-changed') {
            this.status();
        }
    }

    async persist(subscription) {
        const response = await fetch(this.subscribeUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                p256dh: this.toBase64(subscription.getKey('p256dh')),
                auth: this.toBase64(subscription.getKey('auth')),
                user_agent: navigator.userAgent,
                browser: this.browserName,
            }),
        });

        if (!response.ok) {
            throw new Error(`subscribe failed: ${response.status}`);
        }
    }

    async destroy(endpoint) {
        const id = await this.findSubscriptionId(endpoint);

        if (id === null) {
            return;
        }

        const response = await fetch(this.unsubscribeUrlValue.replace('__ID__', String(id)), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`unsubscribe failed: ${response.status}`);
        }
    }

    async findSubscriptionId(endpoint) {
        const response = await fetch(this.indexUrlValue, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return null;
        }

        const payload = await response.json();
        const rows = Array.isArray(payload.data) ? payload.data : [];
        const match = rows.find((row) => row.endpoint === endpoint);

        return match ? match.id : null;
    }

    get supported() {
        return 'serviceWorker' in navigator && 'PushManager' in window;
    }

    get csrfToken() {
        return this.csrfTokenValue || document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    get applicationServerKeyBytes() {
        const base64 = this.applicationServerKeyValue.replace(/-/g, '+').replace(/_/g, '/');
        const padded = base64.padEnd(base64.length + ((4 - (base64.length % 4)) % 4), '=');
        const raw = window.atob(padded);
        const bytes = new Uint8Array(raw.length);

        for (let i = 0; i < raw.length; i += 1) {
            bytes[i] = raw.charCodeAt(i);
        }

        return bytes;
    }

    get browserName() {
        const ua = navigator.userAgent;

        if (ua.includes('Edg/')) return 'Edge';
        if (ua.includes('OPR/') || ua.includes('Opera')) return 'Opera';
        if (ua.includes('Firefox')) return 'Firefox';
        if (ua.includes('Chrome')) return 'Chrome';
        if (ua.includes('Safari')) return 'Safari';

        return 'Unknown';
    }

    toBase64(buffer) {
        if (!buffer) {
            return '';
        }

        const bytes = new Uint8Array(buffer);
        let binary = '';

        for (let i = 0; i < bytes.byteLength; i += 1) {
            binary += String.fromCharCode(bytes[i]);
        }

        return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    setStatus(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }

    toggleButtons(subscribed) {
        if (this.hasSubscribeButtonTarget) {
            this.subscribeButtonTarget.hidden = subscribed;
        }

        if (this.hasUnsubscribeButtonTarget) {
            this.unsubscribeButtonTarget.hidden = !subscribed;
        }
    }
}
