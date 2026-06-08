let reverbImportPromise = null;

export async function ensureReverb() {
    if (typeof window === 'undefined') {
        return null;
    }

    if (window.Echo) {
        return window.Echo;
    }

    const enabled = import.meta.env.VITE_REVERB_ENABLED === 'true';
    const appKey = import.meta.env.VITE_REVERB_APP_KEY;

    if (!enabled || !appKey) {
        window.__reverbState = {
            connected: false,
            error: 'Realtime tidak diaktifkan.',
            reconnectAttempts: 0,
        };

        return null;
    }

    reverbImportPromise ??= import('./echo.js')
        .then(() => window.Echo ?? null)
        .catch((err) => {
            window.__reverbState = {
                connected: false,
                error: err?.message ?? 'Gagal memuat Reverb.',
                reconnectAttempts: 0,
            };

            return null;
        });

    return reverbImportPromise;
}
