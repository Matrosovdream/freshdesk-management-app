/**
 * Minimal realtime client. Intentionally lazy — we only import Echo/Pusher when
 * a consumer actually subscribes, so the dashboard boots even without the
 * backend Reverb server configured. Once the backend lands we swap the body
 * here for a real Laravel Echo client.
 */

const subscriptions = new Map();
let client = null;

async function ensureClient() {
    if (client) return client;
    // Placeholder — real implementation:
    //   import Echo from 'laravel-echo';
    //   import Pusher from 'pusher-js';
    //   client = new Echo({ broadcaster: 'reverb', key: import.meta.env.VITE_REVERB_APP_KEY, ... });
    client = {
        private(channel) {
            return {
                listen(event, cb) {
                    const key = `${channel}::${event}`;
                    const handlers = subscriptions.get(key) || [];
                    handlers.push(cb);
                    subscriptions.set(key, handlers);
                    return this;
                },
                stopListening(event) {
                    const key = `${channel}::${event}`;
                    subscriptions.delete(key);
                    return this;
                },
            };
        },
        leave(channel) {
            for (const key of Array.from(subscriptions.keys())) {
                if (key.startsWith(`${channel}::`)) subscriptions.delete(key);
            }
        },
    };
    return client;
}

export async function subscribe(channel, event, handler) {
    const c = await ensureClient();
    c.private(channel).listen(event, handler);
    return () => c.private(channel).stopListening(event);
}

export async function leave(channel) {
    const c = await ensureClient();
    c.leave(channel);
}

export default { subscribe, leave };
