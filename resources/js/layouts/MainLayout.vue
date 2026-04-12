<template>
    <div class="min-h-screen flex bg-background">
        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-sidebar-border bg-sidebar transition-transform duration-300 lg:static lg:translate-x-0',
                ui.sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Brand -->
            <div class="flex h-16 items-center gap-2 px-6 border-b border-sidebar-border">
                <span class="text-xl font-bold text-sidebar-primary">Denji</span>
            </div>

            <!-- Nav links -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <RouterLink
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition"
                    active-class="bg-sidebar-accent text-sidebar-accent-foreground"
                >
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path :d="item.icon" /></svg>
                    {{ item.label }}
                </RouterLink>
            </nav>
        </aside>

        <!-- Sidebar overlay (mobile) -->
        <div
            v-if="ui.sidebarOpen"
            class="fixed inset-0 z-20 bg-black/30 lg:hidden"
            @click="ui.toggleSidebar()"
        />

        <!-- Main content area -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="flex h-16 items-center justify-between border-b border-border bg-card px-6">
                <Button
                    variant="ghost"
                    size="icon"
                    class="lg:hidden"
                    @click="ui.toggleSidebar()"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </Button>

                <div class="flex-1" />

                <div class="relative flex items-center gap-4">
                    <button
                        type="button"
                        class="flex items-center gap-2 text-sm font-medium text-foreground"
                        @click="toggleUserMenu"
                    >
                        <span>{{ auth.user?.name }}</span>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                            <path
                                d="M5.25 7.5L10 12.5L14.75 7.5"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                    <div
                        v-if="userMenuOpen"
                        class="absolute right-0 top-full mt-2 w-40 rounded-md border border-border bg-popover shadow-lg"
                    >
                        <button
                            type="button"
                            class="block w-full px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                            @click="openPasswordDialog"
                        >
                            Change password
                        </button>
                        <div class="my-1 h-px bg-border" />
                        <button
                            type="button"
                            class="block w-full px-3 py-2 text-left text-sm text-destructive hover:bg-destructive/10"
                            @click="handleLogout"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-6">
                <RouterView />
            </main>
        </div>
    </div>

    <div
        v-if="passwordDialogOpen"
        class="fixed inset-0 z-40 flex items-center justify-center bg-black/40"
    >
        <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Change password</h2>
                <button
                    type="button"
                    class="text-muted-foreground hover:text-foreground"
                    @click="closePasswordDialog"
                >
                    ×
                </button>
            </div>
            <form @submit.prevent="handleChangePassword" class="space-y-4">
                <div class="space-y-2">
                    <Label for="current_password">Current password</Label>
                    <Input
                        id="current_password"
                        v-model="passwordForm.current_password"
                        type="password"
                        autocomplete="current-password"
                    />
                    <p v-if="auth.errors?.current_password" class="text-xs text-destructive">
                        {{ auth.errors.current_password[0] }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label for="new_password">New password</Label>
                    <Input
                        id="new_password"
                        v-model="passwordForm.password"
                        type="password"
                        autocomplete="new-password"
                    />
                    <p v-if="auth.errors?.password" class="text-xs text-destructive">
                        {{ auth.errors.password[0] }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label for="password_confirmation">Confirm new password</Label>
                    <Input
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" @click="closePasswordDialog">
                        Cancel
                    </Button>
                    <Button type="submit">
                        <span v-if="auth.isLoading">Saving…</span>
                        <span v-else>Save</span>
                    </Button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast notification -->
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="ui.toast"
            class="fixed bottom-6 right-6 z-50 rounded-lg px-5 py-3 text-sm font-medium text-primary-foreground shadow-lg"
            :class="{
                'bg-primary': ui.toast.type === 'success',
                'bg-destructive': ui.toast.type === 'error',
                'bg-secondary': ui.toast.type === 'info',
            }"
        >
            {{ ui.toast.message }}
        </div>
    </Transition>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.store';
import { useUiStore } from '@/stores/ui.store';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const router = useRouter();
const auth = useAuthStore();
const ui = useUiStore();

const passwordDialogOpen = ref(false);
const userMenuOpen = ref(false);
const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const navItems = [
    { 
        label: 'Dashboard', 
        to: '/dashboard', 
        icon: 'm3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10' 
    },
    { 
        label: 'Laporan Harian', 
        to: '/daily-report', 
        icon: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8' 
    },
    { 
        label: 'Items', 
        to: '/items', 
        icon: 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z M3.27 6.96L12 12.01l8.73-5.05 M12 22.08V12' 
    },
    { 
        label: 'Item variants', 
        to: '/item-variants', 
        icon: 'M12 2 2 7l10 5 10-5-10-5Z M2 17l10 5 10-5 M2 12l10 5 10-5' 
    },
    { 
        label: 'Customers', 
        to: '/customers', 
        icon: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75' 
    },
    { 
        label: 'Stock movements', 
        to: '/item-movements', 
        icon: 'm7 15 5 5 5-5 M7 9l5-5 5 5 M12 4v16' 
    },
    { 
        label: 'Kitchen', 
        to: '/kitchen', 
        icon: 'M2 21h20 M12 3v1 M16 3v1 M8 3v1 M5 7a4 4 0 0 0 4 4h6a4 4 0 0 0 4-4V5H5v2Z M12 11v10' 
    },
    { 
        label: 'Reference types', 
        to: '/reference-types', 
        icon: 'M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z M7 7h.01' 
    },
    { 
        label: 'Categories', 
        to: '/categories', 
        icon: 'M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z' 
    },
];

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}

function toggleUserMenu() {
    userMenuOpen.value = !userMenuOpen.value;
}

function openPasswordDialog() {
    passwordDialogOpen.value = true;
}

function closePasswordDialog() {
    passwordDialogOpen.value = false;
    passwordForm.current_password = '';
    passwordForm.password = '';
    passwordForm.password_confirmation = '';
    auth.errors = {};
}

async function handleChangePassword() {
    try {
        await auth.updatePassword({
            current_password: passwordForm.current_password,
            password: passwordForm.password,
            password_confirmation: passwordForm.password_confirmation,
        });
        ui.showToast('Password updated successfully', 'success');
        closePasswordDialog();
    } catch (error) {
        ui.showToast(
            error.response?.data?.message ?? 'Could not update password',
            'error',
        );
    }
}
</script>
