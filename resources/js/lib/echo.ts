import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { csrfToken } from '@/lib/tecturn/beacon';

let instance: Echo<'reverb'> | null = null;

/**
 * The presence member id sent to /broadcasting/auth when joining a presence
 * channel. Audience members have no login, so each page declares the id it
 * wants to appear as (its viewer id) before calling `.join()`. Read lazily by
 * the authorizer so it stays correct even though the Echo client is a
 * long-lived singleton across Inertia visits.
 */
let presenceIdentity = '';

export function setPresenceIdentity(id: string): void {
    presenceIdentity = id;
}

/**
 * Lazily create the Echo connection so only pages that subscribe to
 * broadcast channels (e.g. the presenter screen) open a websocket.
 */
export function getEcho(): Echo<'reverb'> {
    if (!instance) {
        (window as typeof window & { Pusher: typeof Pusher }).Pusher = Pusher;

        instance = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
            forceTLS:
                (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            // Custom authorizer so anonymous viewers can carry their (login-less)
            // presence identity to the auth endpoint. Reads the id lazily per
            // subscription, not at construction, so it reflects the current page.
            authorizer: (channel: { name: string }) => ({
                authorize: (
                    socketId: string,
                    callback: (error: Error | null, data: unknown) => void,
                ) => {
                    fetch('/broadcasting/auth', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            socket_id: socketId,
                            channel_name: channel.name,
                            viewer_id: presenceIdentity,
                        }),
                    })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error(
                                    `Auth failed with status ${response.status}`,
                                );
                            }

                            return response.json();
                        })
                        .then((data) => callback(null, data))
                        .catch((error) => callback(error, null));
                },
            }),
        });
    }

    return instance;
}
