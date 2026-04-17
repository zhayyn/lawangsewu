/**
 * useReverb — Vue composable untuk status koneksi Reverb real-time.
 *
 * Analogi: ini seperti lampu indikator jaringan di pojok layar.
 * Kalau walkie-talkie terhubung → lampu hijau.
 * Kalau sedang reconnect → lampu kuning berkedip.
 * Kalau putus → lampu merah.
 *
 * Usage:
 *   const { connected, reconnecting, error, subscribeQueue } = useReverb();
 *
 *   // Subscribe ke update antrian PTSP:
 *   subscribeQueue('ptsp', (event) => {
 *     todayTickets.value = [...]; // update state lokal
 *   });
 */
import { onUnmounted, readonly, ref } from 'vue';

const connected = ref(false);
const reconnecting = ref(false);
const error = ref(null);

// Sync dengan window.__reverbState yang diisi echo.js
let syncInterval = null;

function startSync() {
    if (syncInterval) return;
    syncInterval = setInterval(() => {
        const state = window.__reverbState;
        if (!state) return;
        connected.value = state.connected;
        error.value = state.error;
        reconnecting.value = !state.connected && state.reconnectAttempts > 0;
    }, 1000);
}

// Bersihkan interval saat seluruh app unmount (jarang terjadi, tapi aman)
function stopSync() {
    if (syncInterval) {
        clearInterval(syncInterval);
        syncInterval = null;
    }
}

/**
 * Subscribe ke private channel antrian dan panggil callback saat event masuk.
 * Mengembalikan fungsi unsubscribe.
 *
 * @param {'ptsp'|'sidang'} type
 * @param {(event: object) => void} callback
 * @returns {() => void} unsubscribe
 */
function subscribeQueue(type, callback) {
    const echo = window.Echo;
    if (!echo) {
        console.warn('[useReverb] Echo belum tersedia. Reverb mungkin tidak diaktifkan.');
        return () => {};
    }

    const channel = echo.private(`lawangsewu.queue.${type}`);
    channel.listen('.queue.updated', callback);

    return () => {
        echo.leave(`lawangsewu.queue.${type}`);
    };
}

export function useReverb() {
    startSync();

    onUnmounted(() => {
        // Hanya hentikan sync kalau tidak ada lagi yang menggunakannya
        // (composable ini bisa dipakai di banyak komponen sekaligus)
    });

    return {
        connected: readonly(connected),
        reconnecting: readonly(reconnecting),
        error: readonly(error),
        subscribeQueue,
    };
}
