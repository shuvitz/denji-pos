 

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth.store';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { fetchDashboardStats, fetchDashboardTrends, fetchTopSellingVariants } from '@/api/dashboard.api';
import VueApexCharts from 'vue3-apexcharts';

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
            { label: 'Low stock variants', value: numberFmt.format(stats.low_stock_count || 0) },
            { label: 'Total selling by pcs', value: numberFmt.format(stats.total_sales_qty || 0) },
            { label: 'Total current stock', value: numberFmt.format(stats.total_stock || 0) },
        ];
        // secondaryStats.value = [
        //     { label: 'Low stock variants', value: numberFmt.format(stats.low_stock_count || 0) },
        //     { label: 'Total selling by pcs', value: numberFmt.format(stats.total_sales_qty || 0) },
        //     { label: 'Total current stock', value: numberFmt.format(stats.total_stock || 0) },
        // ];
    } catch (e) {
    }
}

const trendSeries = ref([]);
const topSellingVariants = ref([]);

async function loadTrends() {
    try {
        const series = await fetchDashboardTrends(30);
        trendSeries.value = series;
    } catch (_) {}
}

async function loadTopSellingVariants() {
    try {
        topSellingVariants.value = await fetchTopSellingVariants(30, 10);
    } catch (_) {}
}

const trendDates = computed(() => trendSeries.value.map((r) => r.date));

const trendLineSeries = computed(() => [
    { name: 'Sales', data: trendSeries.value.map((r) => r.sales) },
    { name: 'Purchase', data: trendSeries.value.map((r) => r.purchase) },
]);

const trendLineOptions = computed(() => ({
    chart: {
        type: 'line',
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    stroke: { curve: 'smooth', width: 2 },
    grid: { borderColor: 'var(--color-border)' },
    colors: ['var(--color-chart-1)', 'var(--color-chart-2)'],
    xaxis: {
        categories: trendDates.value,
        labels: { style: { colors: 'var(--color-muted-foreground)' } },
        axisBorder: { color: 'var(--color-border)' },
        axisTicks: { color: 'var(--color-border)' },
    },
    yaxis: {
        labels: {
            style: { colors: 'var(--color-muted-foreground)' },
            formatter: (v) => currency.format(v || 0),
        },
    },
    legend: {
        labels: { colors: 'var(--color-foreground)' },
    },
    tooltip: {
        shared: true,
        intersect: false,
        y: {
            formatter: (v) => currency.format(v || 0),
        },
    },
}));

const topSellingCategories = computed(() => topSellingVariants.value.map((r) => r.label));
const topSellingQtySeries = computed(() => [{ name: 'Qty', data: topSellingVariants.value.map((r) => r.qty) }]);

const topSellingBarOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
    },
    grid: { borderColor: 'var(--color-border)' },
    colors: ['var(--color-chart-3)'],
    plotOptions: {
        bar: {
            vertical: true,
            borderRadius: 4,
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: topSellingCategories.value,
        labels: { style: { colors: 'var(--color-muted-foreground)' } },
        axisBorder: { color: 'var(--color-border)' },
        axisTicks: { color: 'var(--color-border)' },
    },
    yaxis: {
        labels: { style: { colors: 'var(--color-muted-foreground)' } },
    },
    tooltip: {
        custom: ({ dataPointIndex }) => {
            const row = topSellingVariants.value[dataPointIndex];
            if (!row) return '';

            return `
                <div style="padding:8px 10px;color:var(--color-foreground);background:var(--color-popover);border:1px solid var(--color-border);border-radius:8px;">
                    <div style="font-weight:600;margin-bottom:4px;">${row.label}</div>
                    <div style="display:flex;justify-content:space-between;gap:12px;">
                        <span style="color:var(--color-muted-foreground);">Qty</span>
                        <span>${numberFmt.format(row.qty || 0)}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:12px;">
                        <span style="color:var(--color-muted-foreground);">Sales</span>
                        <span>${currency.format(row.amount || 0)}</span>
                    </div>
                </div>
            `;
        },
    },
}));

onMounted(() => {
    loadStats();
    loadTrends();
    loadTopSellingVariants();
});
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Dashboard</h1>
            <p class="mt-1 text-sm text-muted-foreground">Welcome back, {{ auth.user?.name }}!</p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-6">
            <Card v-for="stat in primaryStats" :key="stat.label">
                <CardHeader>
                    <CardDescription>{{ stat.label }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                </CardHeader>
            </Card>
        </div>
        <!-- <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <Card v-for="stat in secondaryStats" :key="stat.label">
                <CardHeader>
                    <CardDescription>{{ stat.label }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                </CardHeader>
            </Card>
        </div> -->

        <Card>
            <CardHeader>
                <CardTitle>Sales vs Purchase (30 days)</CardTitle>
                <CardDescription>IDR</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="w-full">
                    <div v-if="trendSeries.length" class="w-full">
                        <VueApexCharts type="line" height="260" :options="trendLineOptions" :series="trendLineSeries" />
                    </div>
                    <div v-else class="h-64 flex items-center justify-center text-sm text-muted-foreground">
                        No data
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Top selling variants (30 days)</CardTitle>
                <CardDescription>By quantity sold</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="w-full">
                    <div v-if="topSellingVariants.length" class="w-full">
                        <VueApexCharts type="bar" height="320" :options="topSellingBarOptions" :series="topSellingQtySeries" />
                    </div>
                    <div v-else class="h-64 flex items-center justify-center text-sm text-muted-foreground">
                        No data
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
