import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && (error.response.status === 419 || error.response.status === 401)) {
            if (!window.__sessionExpiredRedirecting) {
                window.__sessionExpiredRedirecting = true;
                setTimeout(() => {
                    window.location.href = '/login';
                }, 1500);
            }
        }
        return Promise.reject(error);
    }
);
