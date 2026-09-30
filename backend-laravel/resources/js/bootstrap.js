import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Auto-reload page when Axios encounters a 419 (CSRF token / session expiry)
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 419) {
            console.warn('Session expired (419). Auto-reloading page...');
            window.location.reload();
        }
        return Promise.reject(error);
    }
);
