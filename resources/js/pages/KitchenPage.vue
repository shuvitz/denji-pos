<script setup>
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { fetchKitchenOrders, markOrderReady } from '@/api/kitchen.api';

const orders = ref([]);
const isLoading = ref(false);
let pollInterval = null;

async function loadOrders() {
    try {
        isLoading.value = orders.value.length === 0;
        orders.value = await fetchKitchenOrders();
    } catch (_) {
    } finally {
        isLoading.value = false;
    }
}

async function handleMarkReady(orderId) {
    try {
        await markOrderReady(orderId);
        orders.value = orders.value.filter((o) => o.id !== orderId);
    } catch (_) {}
}

function formatTime(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function minutesAgo(dateStr) {
    const diff = Date.now() - new Date(dateStr).getTime();
    return Math.floor(diff / 60000);
}

const now = ref(Date.now());
let tickInterval = null;

onMounted(() => {
    loadOrders();
    pollInterval = setInterval(loadOrders, 5000); // Poll every 5s
    tickInterval = setInterval(() => { now.value = Date.now(); }, 10000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
    if (tickInterval) clearInterval(tickInterval);
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">🍳 Kitchen</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Orders waiting to be prepared • Auto-refreshes every 5s
                </p>
            </div>
            <span class="flex items-center gap-2 text-xs text-muted-foreground">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                Live
            </span>
        </div>

        <!-- Loading state -->
        <div v-if="isLoading" class="flex items-center justify-center py-20">
            <div class="animate-pulse text-muted-foreground">Loading orders…</div>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="orders.length === 0"
            class="flex flex-col items-center justify-center py-20 text-center"
        >
            <div class="text-5xl mb-4">✅</div>
            <p class="text-lg font-medium text-foreground">All clear!</p>
            <p class="text-sm text-muted-foreground mt-1">No pending orders right now.</p>
        </div>

        <!-- Order cards grid -->
        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div
                v-for="order in orders"
                :key="order.id"
                class="rounded-xl border border-border bg-card shadow-sm overflow-hidden"
            >
                <!-- Card header -->
                <div class="flex items-center justify-between border-b border-border px-4 py-3 bg-muted/30">
                    <div>
                        <span class="text-sm font-bold text-foreground">{{ order.order_number }}</span>
                        <span
                            v-if="order.order_type === 'dine-in' && order.table"
                            class="ml-2 rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-800"
                        >
                            {{ order.table.name }}
                        </span>
                        <span
                            v-else
                            class="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-800"
                        >
                            Takeaway
                        </span>
                    </div>
                    <div class="text-right text-xs text-muted-foreground leading-tight">
                        <div>{{ formatTime(order.created_at) }}</div>
                        <div
                            :class="minutesAgo(order.created_at) > 10 ? 'text-red-500 font-semibold' : ''"
                        >
                            {{ minutesAgo(order.created_at) }}m ago
                        </div>
                    </div>
                </div>

                <!-- Order items list -->
                <div class="px-4 py-3 space-y-2">
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="flex items-start gap-2"
                    >
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-primary/10 text-xs font-bold text-primary">
                            {{ item.qty }}×
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-foreground truncate">
                                {{ item.variant?.item?.name ?? 'Item' }}
                                <span v-if="item.variant?.name" class="text-muted-foreground font-normal">
                                    — {{ item.variant.name }}
                                </span>
                            </p>
                            <p
                                v-if="item.note"
                                class="text-xs text-amber-600 mt-0.5 italic"
                            >
                                📝 {{ item.note }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card action -->
                <div class="border-t border-border px-4 py-3">
                    <button
                        type="button"
                        class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.98]"
                        @click="handleMarkReady(order.id)"
                    >
                        ✓ Mark as Ready
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
