import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo / Reverb — Real-time WebSocket
 * Keep this behind an explicit flag so polling can operate independently
 * when the realtime transport is not healthy in production.
 */
if (import.meta.env.VITE_REVERB_ENABLED === 'true' && import.meta.env.VITE_REVERB_APP_KEY) {
    import('./echo.js');
}
