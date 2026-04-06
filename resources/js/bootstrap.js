import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo / Reverb — Real-time WebSocket
 * Only loaded when VITE_REVERB_APP_KEY is configured.
 */
if (import.meta.env.VITE_REVERB_APP_KEY) {
    import('./echo.js');
}
