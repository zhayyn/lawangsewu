import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.__reverbState = window.__reverbState ?? {
    connected: false,
    error: null,
    reconnectAttempts: 0,
};
