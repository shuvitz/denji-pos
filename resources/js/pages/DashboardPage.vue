 

<script setup>
import { onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth.store';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { fetchDashboardStats, fetchDashboardTrends } from '@/api/dashboard.api';

const auth = useAuthStore();

const primaryStats = ref([
    { label: 'Total sales', value: '—' },
    { label: 'Total purchase', value: '—' },
    { label: 'Total margin', value: '—' },
]);
const secondaryStats = ref([
    { label: 'Low stock variants', value: '—' },
    { label: 'Total sales of item variants', value: '—' },
]);

const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const numberFmt = new Intl.NumberFormat(undefined);

async function loadStats() {
    try {
        const stats = await fetchDashboardStats();
        primaryStats.value = [
            { label: 'Total sales', value: currency.format(stats.total_sales_amount || 0) },
            { label: 'Total purchase', value: currency.format(stats.total_cost_amount || 0) },
            { label: 'Total margin', value: currency.format(stats.total_margin_amount || 0) },
        ];
        secondaryStats.value = [
            { label: 'Low stock variants', value: numberFmt.format(stats.low_stock_count || 0) },
            { label: 'Total selling by pcs', value: numberFmt.format(stats.total_sales_qty || 0) },
            { label: 'Total current stock', value: numberFmt.format(stats.total_stock || 0) },
        ];
    } catch (e) {
    }
}

const trendSeries = ref([]);

function makePoints(values, maxY, width, height, padding) {
    const n = values.length;
    if (n === 0) return '';
    const xStep = (width - padding * 2) / Math.max(n - 1, 1);
    return values.map((y, i) => {
        const x = padding + i * xStep;
        const v = y || 0;
        const yPos = height - padding - (maxY > 0 ? (v / maxY) * (height - padding * 2) : 0);
        return `${x},${yPos}`;
    }).join(' ');
}

async function loadTrends() {
    try {
        const series = await fetchDashboardTrends(30);
        trendSeries.value = series;
    } catch (_) {}
}

onMounted(() => {
    loadStats();
    loadTrends();
});
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Dashboard</h1>
            <p class="mt-1 text-sm text-muted-foreground">Welcome back, {{ auth.user?.name }}!</p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <Card v-for="stat in primaryStats" :key="stat.label">
                <CardHeader>
                    <CardDescription>{{ stat.label }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                </CardHeader>
            </Card>
        </div>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <Card v-for="stat in secondaryStats" :key="stat.label">
                <CardHeader>
                    <CardDescription>{{ stat.label }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Sales vs Purchase (30 days)</CardTitle>
                <CardDescription>IDR</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="w-full">
                    <svg :viewBox="'0 0 800 300'" class="w-full h-64">
                        <rect x="0" y="0" width="800" height="300" fill="transparent" />
                        <g>
                            <polyline
                                :points="makePoints(trendSeries.map(s => s.sales), Math.max(...trendSeries.map(s => Math.max(s.sales, s.purchase), 0)), 800, 300, 32)"
                                fill="none"
                                stroke="currentColor"
                                class="text-primary"
                                stroke-width="2"
                            />
                            <polyline
                                :points="makePoints(trendSeries.map(s => s.purchase), Math.max(...trendSeries.map(s => Math.max(s.sales, s.purchase), 0)), 800, 300, 32)"
                                fill="none"
                                stroke="currentColor"
                                class="text-green-500"
                                stroke-width="2"
                            />
                        </g>
                    </svg>
                    <div class="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                        <span class="inline-flex items-center gap-2"><span class="inline-block h-2 w-2 rounded-full bg-primary"></span> Sales</span>
                        <span class="inline-flex items-center gap-2"><span class="inline-block h-2 w-2 rounded-full bg-green-500"></span> Purchase</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
