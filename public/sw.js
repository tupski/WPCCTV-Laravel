/* WP CCTV Indonesia — admin web push service worker.
 *
 * Scope: /admin (registered from admin pages only).
 * Handles push delivery with event-level dedupe and click deep-linking.
 */

const DEDUPE_WINDOW_MS = 60 * 1000;
const DEDUPE_KEY = 'wpc-last-push-event';

/* ── Dedupe storage ────────────────────────────────────────────────────
 * The Notification Triggers "Storage API" (self.registration.storage) is not
 * shipped in any stable browser. We use the same shape ({ get, set }) backed
 * by the Cache API, which is available in every service worker context.
 */
async function getDedupeStore() {
    if (self.registration && self.registration.storage) {
        return {
            get: (key) => self.registration.storage.getItem(key),
            set: (key, value) => self.registration.storage.setItem(key, value),
        };
    }

    const cache = await caches.open('wpc-push-dedupe');

    return {
        async get(key) {
            const response = await cache.match(new Request(`/__push-dedupe/${key}`));
            return response ? response.text() : null;
        },
        async set(key, value) {
            await cache.put(
                new Request(`/__push-dedupe/${key}`),
                new Response(value, { headers: { 'Content-Type': 'text/plain' } }),
            );
        },
    };
}

async function isDuplicate(eventId) {
    if (!eventId) {
        return false;
    }

    const store = await getDedupeStore();
    const raw = await store.get(DEDUPE_KEY);

    if (raw) {
        try {
            const previous = JSON.parse(raw);

            if (
                previous.event_id === eventId &&
                Date.now() - Number(previous.timestamp) < DEDUPE_WINDOW_MS
            ) {
                return true;
            }
        } catch (_error) {
            // Corrupt entry: treat as absent.
        }
    }

    await store.set(
        DEDUPE_KEY,
        JSON.stringify({ event_id: eventId, timestamp: Date.now() }),
    );

    return false;
}

/* ── Lifecycle ───────────────────────────────────────────────────────── */

self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            // Drop stale dedupe caches from earlier worker versions.
            const names = await caches.keys();
            await Promise.all(
                names
                    .filter((name) => name.startsWith('wpc-push-dedupe-') && name !== 'wpc-push-dedupe')
                    .map((name) => caches.delete(name)),
            );

            await self.clients.claim();
        })(),
    );
});

/* ── Push ────────────────────────────────────────────────────────────── */

self.addEventListener('push', (event) => {
    let payload = {};

    if (event.data) {
        try {
            payload = event.data.json();
        } catch (_error) {
            payload = { title: 'Notifikasi', body: event.data.text() };
        }
    }

    const eventId = payload.event_id || payload.eventId || null;

    event.waitUntil(
        (async () => {
            await self.skipWaiting();

            if (await isDuplicate(eventId)) {
                return;
            }

            const title = payload.title || 'WP CCTV Indonesia';
            const options = {
                body: payload.body || '',
                icon: payload.icon || '/favicon.ico',
                badge: payload.badge || '/favicon.ico',
                tag: payload.tag || eventId || undefined,
                renotify: Boolean(payload.tag || eventId),
                data: {
                    url: (payload.data && payload.data.url) || payload.url || '/admin',
                    event_id: eventId,
                },
            };

            await self.registration.showNotification(title, options);
        })(),
    );
});

/* ── Notification click ──────────────────────────────────────────────── */

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) || '/admin';

    event.waitUntil(
        (async () => {
            const allClients = await self.clients.matchAll({
                type: 'window',
                includeUncontrolled: true,
            });

            for (const client of allClients) {
                const clientUrl = new URL(client.url);

                if (clientUrl.origin === self.location.origin && 'focus' in client) {
                    await client.focus();

                    if ('navigate' in client) {
                        await client.navigate(targetUrl);
                    }

                    return;
                }
            }

            await self.clients.openWindow(targetUrl);
        })(),
    );
});

/* ── Subscription changed by the browser ─────────────────────────────── */

self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil(
        (async () => {
            const allClients = await self.clients.matchAll({
                type: 'window',
                includeUncontrolled: true,
            });

            for (const client of allClients) {
                client.postMessage({ type: 'push-subscription-changed' });
            }
        })(),
    );
});
