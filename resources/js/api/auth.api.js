import http from './http';

export const authApi = {
    /**
     * Log in with email & password.
     */
    login(credentials) {
        return http.post('/login', credentials);
    },

    /**
     * Register a new account.
     */
    register(data) {
        return http.post('/register', data);
    },

    /**
     * Log out the current user.
     */
    logout() {
        return http.post('/logout');
    },

    updatePassword(data) {
        return http.post('/user/password', data);
    },

    /**
     * Fetch the authenticated user.
     */
    getUser() {
        return http.get('/me');
    },
};
