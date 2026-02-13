import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '../api/auth.api';

export const useAuthStore = defineStore('auth', () => {
    // ── State ──────────────────────────────────────────
    const user = ref(null);
    const isLoading = ref(false);
    const errors = ref({});

    // ── Getters ────────────────────────────────────────
    const isAuthenticated = computed(() => !!user.value);

    // ── Actions ────────────────────────────────────────

    /**
     * Fetch the current authenticated user from the server.
     */
    async function fetchUser() {
        isLoading.value = true;
        try {
            const { data } = await authApi.getUser();
            user.value = data.data;
        } catch {
            user.value = null;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Log in with the given credentials.
     */
    async function login(credentials) {
        isLoading.value = true;
        errors.value = {};
        try {
            const { data } = await authApi.login(credentials);
            user.value = data.data;
            return data;
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Register a new account.
     */
    async function register(formData) {
        isLoading.value = true;
        errors.value = {};
        try {
            const { data } = await authApi.register(formData);
            user.value = data.data;
            return data;
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Log the user out.
     */
    async function logout() {
        isLoading.value = true;
        try {
            await authApi.logout();
            user.value = null;
        } finally {
            isLoading.value = false;
        }
    }

    return {
        user,
        isLoading,
        errors,
        isAuthenticated,
        fetchUser,
        login,
        register,
        logout,
    };
});
