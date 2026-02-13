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

                <!-- User info & logout -->
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-foreground">{{ auth.user?.name }}</span>
                    <Button variant="ghost" size="sm" class="text-destructive" @click="handleLogout">
                        Logout
                    </Button>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-6">
                <RouterView />
            </main>
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
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.store';
import { useUiStore } from '@/stores/ui.store';
import { Button } from '@/components/ui/button';

const router = useRouter();
const auth = useAuthStore();
const ui = useUiStore();

const navItems = [
    { label: 'Dashboard', to: '/dashboard' },
];

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>
