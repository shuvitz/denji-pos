import axios from 'axios';

const http = axios.create({
    baseURL: '/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

/**
 * Response interceptor:
 * - 401 → redirect to login
 * - 419 → CSRF token expired, fetch new cookie & retry
 */
http.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        // CSRF token mismatch — fetch cookie and retry once
        if (error.response?.status === 419 && !originalRequest._retried) {
            originalRequest._retried = true;
            await axios.get('/sanctum/csrf-cookie');
            return http(originalRequest);
        }

        // Unauthenticated — redirect to login
        if (error.response?.status === 401) {
            window.location.href = '/login';
        }

        return Promise.reject(error);
    }
);

export default http;
