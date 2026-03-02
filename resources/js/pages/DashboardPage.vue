<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div>
            <h1 class="text-2xl font-bold text-foreground">Dashboard</h1>
            <p class="mt-1 text-sm text-muted-foreground">Welcome back, {{ auth.user?.name }}!</p>
        </div>

        <!-- Primary stats: total sales, total purchase (HPP), total margin -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <Card v-for="stat in primaryStats" :key="stat.label">
                <CardHeader>
                    <CardDescription>{{ stat.label }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                </CardHeader>
            </Card>
        </div>
        <!-- Secondary stats: low stock variants, total sales of item variants -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <Card v-for="stat in secondaryStats" :key="stat.label">
                <CardHeader>
                    <CardDescription>{{ stat.label }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth.store';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { fetchDashboardStats } from '@/api/dashboard.api';

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

onMounted(loadStats);
</script>
