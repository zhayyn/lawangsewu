/**
 * Laravel Echo — Reverb WebSocket Configuration
 * Provides real-time event broadcasting for Lawangsewu chat.
 *
 * Connection state is exported via `window.__reverbState` so Vue composables
 * can reactively reflect the connection health in the UI without requiring
 * a full page reload on disconnect/reconnect.
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Reactive state that composables can poll / watch
window.__reverbState = {
    connected: false,
    error: null,
    reconnectAttempts: 0,
};

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: (() => {
        const rawPort = Number(import.meta.env.VITE_REVERB_PORT ?? 0);
        if (Number.isFinite(rawPort) && rawPort > 0) {
            return rawPort;
        }
        return 80;
    })(),
    wssPort: (() => {
        const rawPort = Number(import.meta.env.VITE_REVERB_PORT ?? 0);
        const envScheme = String(import.meta.env.VITE_REVERB_SCHEME ?? '').toLowerCase();
        const forceTls = envScheme ? envScheme === 'https' : window.location.protocol === 'https:';

        if (Number.isFinite(rawPort) && rawPort > 0) {
            // Common production pitfall: env still set to 8080 while app served via HTTPS.
            if (forceTls && rawPort === 8080) {
                return 443;
            }

            return rawPort;
        }

        return 443;
    })(),
    forceTLS: (() => {
        const envScheme = String(import.meta.env.VITE_REVERB_SCHEME ?? '').toLowerCase();
        return envScheme ? envScheme === 'https' : window.location.protocol === 'https:';
    })(),
    enabledTransports: ['ws', 'wss'],
    // Exponential backoff: 1s → 2s → 4s … max 30s
    activityTimeout: 30_000,
    pongTimeout: 10_000,
});

// Track connection lifecycle for UI indicators
echo.connector.pusher.connection.bind('connected', () => {
    window.__reverbState.connected = true;
    window.__reverbState.error = null;
    window.__reverbState.reconnectAttempts = 0;
});

echo.connector.pusher.connection.bind('disconnected', () => {
    window.__reverbState.connected = false;
});

echo.connector.pusher.connection.bind('error', (err) => {
    window.__reverbState.connected = false;
    window.__reverbState.error = err?.error?.data?.message ?? 'Koneksi Reverb error';
});

echo.connector.pusher.connection.bind('state_change', (states) => {
    if (states.current === 'connecting') {
        window.__reverbState.reconnectAttempts += 1;
    }
});

window.Echo = echo;
