import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
    // ── State ──────────────────────────────────────────
    const sidebarOpen = ref(true);
    const isLoading = ref(false);
    const toast = ref(null); // { message, type: 'success' | 'error' | 'info' }

    // ── Actions ────────────────────────────────────────

    function toggleSidebar() {
        sidebarOpen.value = !sidebarOpen.value;
    }

    function showToast(message, type = 'info', duration = 3000) {
        toast.value = { message, type };
        setTimeout(() => {
            toast.value = null;
        }, duration);
    }

    function setLoading(value) {
        isLoading.value = value;
    }

    return {
        sidebarOpen,
        isLoading,
        toast,
        toggleSidebar,
        showToast,
        setLoading,
    };
});
