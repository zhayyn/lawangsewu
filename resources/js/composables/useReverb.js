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
import { ensureReverb } from '@/reverbLoader';

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
    const channelName = `lawangsewu.queue.${type}`;
    let active = true;
    let subscribedEcho = null;

    ensureReverb().then((echo) => {
        if (!active || !echo) {
            return;
        }

        subscribedEcho = echo;
        echo.private(channelName).listen('.queue.updated', callback);
    });

    return () => {
        active = false;
        (subscribedEcho ?? window.Echo)?.leave(channelName);
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
