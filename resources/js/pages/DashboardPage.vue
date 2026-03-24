

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth.store';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { fetchDashboardStats, fetchDashboardTrends, fetchTopSellingVariants } from '@/api/dashboard.api';
import VueApexCharts from 'vue3-apexcharts';

const auth = useAuthStore();

const period = ref('1m');

const periodOptions = [
    { value: '7d', label: '7 days' },
    { value: '1m', label: '1 month' },
    { value: '3m', label: '3 month' },
    { value: '6m', label: '6 month' },
    { value: '9m', label: '9 month' },
    { value: '1y', label: '1 year' },
    { value: 'ytd', label: 'Year to date' },
];

const periodDays = computed(() => {
    const today = new Date();
    if (period.value === '7d') return 7;
    if (period.value === '1m') return 30;
    if (period.value === '3m') return 90;
    if (period.value === '6m') return 180;
    if (period.value === '9m') return 270;
    if (period.value === '1y') return 365;
    if (period.value === 'ytd') {
        const start = new Date(today.getFullYear(), 0, 1);
        const diffDays = Math.floor((today.getTime() - start.getTime()) / 86400000) + 1;
        return Math.max(1, diffDays);
    }
    return 30;
});

const periodLabel = computed(() => periodOptions.find((p) => p.value === period.value)?.label ?? '1 month');

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
const axisDateFmt = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' });
const tooltipDateFmt = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

function formatYmd(d) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function percentChange(current, previous) {
    const c = Number(current || 0);
    const p = Number(previous || 0);
    if (p === 0) return null;
    return ((c - p) / p) * 100;
}

function formatPct(value) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return '—';
    const v = Number(value);
    const sign = v > 0 ? '+' : '';
    return `${sign}${v.toFixed(1)}%`;
}

function calcPrevEndYmd(days) {
    const end = new Date();
    const start = new Date(end);
    start.setDate(start.getDate() - (days - 1));
    const prevEnd = new Date(start);
    prevEnd.setDate(prevEnd.getDate() - 1);
    return formatYmd(prevEnd);
}

function statChipClass(pct) {
    if (pct === null || pct === undefined) return 'bg-muted text-muted-foreground';
    if (pct > 0) return 'bg-emerald-100 text-emerald-800';
    if (pct < 0) return 'bg-red-100 text-red-800';
    return 'bg-muted text-muted-foreground';
}

function formatAxisDate(value) {
    if (!value) return '';
    const d = new Date(`${value}T00:00:00`);
    if (Number.isNaN(d.getTime())) return value;
    return axisDateFmt.format(d);
}

function formatTooltipDate(value) {
    if (!value) return '';
    const d = new Date(`${value}T00:00:00`);
    if (Number.isNaN(d.getTime())) return value;
    return tooltipDateFmt.format(d);
}

async function loadStats() {
    try {
        const days = periodDays.value;
        const [stats, prev] = await Promise.all([
            fetchDashboardStats(days),
            fetchDashboardStats(days, calcPrevEndYmd(days)),
        ]);

        const salesPct = percentChange(stats.total_sales_amount, prev.total_sales_amount);
        const purchasePct = percentChange(stats.total_cost_amount, prev.total_cost_amount);
        const marginPct = percentChange(stats.total_margin_amount, prev.total_margin_amount);
        const qtyPct = percentChange(stats.total_sales_qty, prev.total_sales_qty);

        primaryStats.value = [
            { label: 'Total sales', value: currency.format(stats.total_sales_amount || 0), pct: salesPct },
            { label: 'Total purchase', value: currency.format(stats.total_cost_amount || 0), pct: purchasePct },
            { label: 'Total margin', value: currency.format(stats.total_margin_amount || 0), pct: marginPct },
            { label: 'Low stock variants', value: numberFmt.format(stats.low_stock_count || 0) },
            // { label: 'Total selling by pcs', value: numberFmt.format(stats.total_sales_qty || 0), pct: qtyPct },
            // { label: 'Total current stock', value: numberFmt.format(stats.total_stock || 0) },
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
        const series = await fetchDashboardTrends(periodDays.value);
        trendSeries.value = series;
    } catch (_) {}
}

async function loadTopSellingVariants() {
    try {
        topSellingVariants.value = await fetchTopSellingVariants(periodDays.value, 10);
    } catch (_) {}
}

const trendDates = computed(() => trendSeries.value.map((r) => r.date));

const trendLineSeries = computed(() => [
    { name: 'Sales', data: trendSeries.value.map((r) => r.sales) },
]);

const trendLineOptions = computed(() => ({
    chart: {
        type: 'line',
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    stroke: { curve: 'smooth', width: 2 },
    grid: { borderColor: 'var(--color-border)' },
    colors: ['var(--color-chart-1)'],
    xaxis: {
        categories: trendDates.value,
        tooltip: { enabled: false },
        labels: {
            style: { colors: 'var(--color-muted-foreground)' },
            formatter: (value) => {
                const idx = trendDates.value.indexOf(value);
                if (idx === -1) return value;
                if (idx === 0 || idx === trendDates.value.length - 1) return formatAxisDate(value);
                return idx % 6 === 0 ? formatAxisDate(value) : '';
            },
        },
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
        show: false,
    },
    tooltip: {
        custom: ({ dataPointIndex }) => {
            const row = trendSeries.value[dataPointIndex];
            if (!row) return '';

            const date = formatTooltipDate(row.date);
            const amount = currency.format(row.sales || 0);
            const qty = numberFmt.format(row.sales_qty || 0);

            return `
                <div style="padding:8px 10px;color:var(--color-foreground);background:var(--color-popover);border:1px solid var(--color-border);border-radius:8px;">
                    <div style="font-weight:600;margin-bottom:4px;">${date}</div>
                    <div style="display:flex;justify-content:space-between;gap:12px;">
                        <span style="color:var(--color-muted-foreground);">Sales</span>
                        <span>${amount} - ${qty} pcs</span>
                    </div>
                </div>
            `;
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
    applyPeriod();
});

async function applyPeriod() {
    await Promise.all([
        loadStats(),
        loadTrends(),
        loadTopSellingVariants(),
    ]);
}

async function setPeriod(next) {
    period.value = next;
    await applyPeriod();
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Dashboard</h1>
                <p class="mt-1 text-sm text-muted-foreground">Welcome back, {{ auth.user?.name }}!</p>
            </div>
            <div class="flex flex-wrap items-center rounded-md border border-border bg-muted/40 p-1">
                <button
                    v-for="p in periodOptions"
                    :key="p.value"
                    type="button"
                    :class="[
                        'h-8 rounded-sm px-3 text-xs font-medium transition',
                        period === p.value
                            ? 'bg-background text-foreground shadow'
                            : 'text-muted-foreground hover:bg-background/60 hover:text-foreground',
                    ]"
                    @click="setPeriod(p.value)"
                >
                    {{ p.label }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
            <Card v-for="stat in primaryStats" :key="stat.label">
                <CardHeader>
                    <div class="flex items-start justify-between gap-3">
                        <CardDescription>{{ stat.label }}</CardDescription>
                        <span
                            v-if="stat.pct !== undefined"
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="statChipClass(stat.pct)"
                        >
                            {{ formatPct(stat.pct) }}
                        </span>
                    </div>
                    <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                    <div v-if="stat.pct !== undefined" class="text-xs text-muted-foreground">
                        {{ stat.pct === null ? 'No comparison data' : (stat.pct > 0 ? 'Up' : (stat.pct < 0 ? 'Down' : 'No change')) }} vs previous period
                    </div>
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
                <CardTitle>Sales trend ({{ periodLabel }})</CardTitle>
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
                <CardTitle>Top selling variants ({{ periodLabel }})</CardTitle>
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
