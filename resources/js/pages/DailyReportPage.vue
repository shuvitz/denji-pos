
<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { fetchDailyReport, saveDailyCost } from '@/api/dashboard.api';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { useUiStore } from '@/stores/ui.store';

const ui = useUiStore();

const isLoading = ref(false);
const selectedDate = ref(formatYmd(new Date()));
const report = ref(null);

const isEditingCost = ref(false);
const isSavingCost = ref(false);
const costForm = ref({ amount: 0, note: '' });

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const displayDate = computed(() => {
    if (!report.value?.date) return '';
    const d = new Date(`${report.value.date}T00:00:00`);
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(d);
});

const isToday = computed(() => {
    return selectedDate.value === formatYmd(new Date());
});

const profitClass = computed(() => {
    if (!report.value) return '';
    if (report.value.profit > 0) return 'profit-positive';
    if (report.value.profit < 0) return 'profit-negative';
    return '';
});

function formatYmd(d) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function goToday() {
    selectedDate.value = formatYmd(new Date());
}

function goPrevDay() {
    const d = new Date(`${selectedDate.value}T00:00:00`);
    d.setDate(d.getDate() - 1);
    selectedDate.value = formatYmd(d);
}

function goNextDay() {
    const d = new Date(`${selectedDate.value}T00:00:00`);
    d.setDate(d.getDate() + 1);
    selectedDate.value = formatYmd(d);
}

async function loadReport() {
    isLoading.value = true;
    try {
        report.value = await fetchDailyReport(selectedDate.value);
        if (report.value.cost_source === 'manual' && report.value.daily_cost) {
            costForm.value.amount = report.value.daily_cost.amount;
            costForm.value.note = report.value.daily_cost.note || '';
        } else {
            costForm.value.amount = report.value.cost;
            costForm.value.note = '';
        }
        isEditingCost.value = false;
    } catch (e) {
        report.value = null;
    } finally {
        isLoading.value = false;
    }
}

async function submitCost() {
    isSavingCost.value = true;
    try {
        await saveDailyCost({
            date: report.value.date,
            amount: costForm.value.amount,
            note: costForm.value.note
        });
        ui.showToast('Modal harian berhasil disimpan', 'success');
        isEditingCost.value = false;
        await loadReport();
    } catch (e) {
        ui.showToast(e.response?.data?.message || 'Gagal menyimpan modal harian', 'error');
    } finally {
        isSavingCost.value = false;
    }
}

watch(selectedDate, () => {
    loadReport();
});

onMounted(() => {
    loadReport();
});
</script>

<template>
    <div class="daily-report">
        <!-- Header -->
        <div class="report-header">
            <div>
                <h1 class="report-title">📋 Laporan Harian</h1>
                <p class="report-subtitle">Ringkasan operasional harian</p>
            </div>
            <div class="date-controls">
                <button class="date-nav-btn" @click="goPrevDay" title="Hari sebelumnya">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <div class="date-picker-wrap">
                    <input
                        type="date"
                        v-model="selectedDate"
                        class="date-input"
                    />
                </div>
                <button class="date-nav-btn" @click="goNextDay" title="Hari berikutnya">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </button>
                <button
                    v-if="!isToday"
                    class="today-btn"
                    @click="goToday"
                >
                    Hari ini
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="loading-state">
            <div class="loading-spinner"></div>
            <span>Memuat laporan...</span>
        </div>

        <!-- Report Content -->
        <div v-else-if="report" class="report-content">
            <!-- Date banner -->
            <div class="date-banner">
                <span class="date-banner-icon">📅</span>
                <span class="date-banner-text">{{ displayDate }}</span>
                <span v-if="isToday" class="today-badge">Hari ini</span>
            </div>

            <!-- Financial summary cards -->
            <div class="finance-grid">
                <!-- Revenue -->
                <div class="finance-card revenue-card">
                    <div class="finance-card-header">
                        <span class="finance-icon">💰</span>
                        <span class="finance-label">Omzet</span>
                    </div>
                    <div class="finance-value">{{ currency.format(report.revenue) }}</div>
                    <div class="finance-meta">{{ report.order_count }} pesanan</div>
                </div>

                <!-- Cost -->
                <div class="finance-card cost-card">
                    <div class="finance-card-header">
                        <span class="finance-icon">📦</span>
                        <span class="finance-label">Modal</span>
                        <button v-if="!isEditingCost" class="edit-cost-btn" @click="isEditingCost = true">Edit</button>
                    </div>

                    <div v-if="!isEditingCost">
                        <div class="finance-value">{{ currency.format(report.cost) }}</div>
                        <div class="finance-meta">
                            {{ report.daily_cost?.note || 'Input modal secara manual' }}
                        </div>
                    </div>

                    <form v-else @submit.prevent="submitCost" class="inline-cost-form">
                        <input
                            type="number"
                            v-model="costForm.amount"
                            class="cost-input"
                            required
                            min="0"
                            placeholder="Rp..."
                        />
                        <input
                            type="text"
                            v-model="costForm.note"
                            class="cost-note-input"
                            placeholder="Catatan (opsional)"
                        />
                        <div class="cost-form-actions">
                            <button type="button" @click="isEditingCost = false" class="cost-cancel-btn">Batal</button>
                            <button type="submit" class="cost-save-btn" :disabled="isSavingCost">Simpan</button>
                        </div>
                    </form>
                </div>

                <!-- Profit -->
                <div class="finance-card profit-card" :class="profitClass">
                    <div class="finance-card-header">
                        <span class="finance-icon">{{ report.profit >= 0 ? '📈' : '📉' }}</span>
                        <span class="finance-label">Keuntungan</span>
                    </div>
                    <div class="finance-value">{{ currency.format(report.profit) }}</div>
                    <div class="finance-meta" v-if="report.revenue > 0">
                        Margin {{ ((report.profit / report.revenue) * 100).toFixed(1) }}%
                    </div>
                </div>
            </div>

            <!-- Sales breakdown -->
            <div class="breakdown-section">
                <div class="breakdown-header">
                    <span class="breakdown-icon">🍽️</span>
                    <h2 class="breakdown-title">Rincian Penjualan</h2>
                </div>

                <div v-if="report.sales_breakdown.length === 0" class="empty-breakdown">
                    <span class="empty-icon">📭</span>
                    <p>Belum ada penjualan hari ini</p>
                </div>

                <div v-else class="breakdown-list">
                    <div
                        v-for="(item, idx) in report.sales_breakdown"
                        :key="idx"
                        class="breakdown-item"
                    >
                        <div class="breakdown-item-left">
                            <span class="breakdown-rank">{{ idx + 1 }}</span>
                            <span class="breakdown-name">{{ item.label }}</span>
                        </div>
                        <div class="breakdown-item-right">
                            <span class="breakdown-qty">{{ item.qty }} pcs</span>
                            <span class="breakdown-amount">{{ currency.format(item.amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Totals row -->
                <div v-if="report.sales_breakdown.length > 0" class="breakdown-total">
                    <span class="breakdown-total-label">Total item terjual</span>
                    <span class="breakdown-total-value">
                        {{ report.sales_breakdown.reduce((sum, i) => sum + i.qty, 0) }} pcs
                    </span>
                </div>
            </div>
        </div>

        <!-- Error / no data -->
        <div v-else class="empty-state">
            <span class="empty-state-icon">📋</span>
            <p>Tidak ada data untuk tanggal ini</p>
        </div>
    </div>
</template>

<style scoped>
.daily-report {
    max-width: 640px;
    margin: 0 auto;
}

/* Header */
.report-header {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
}

@media (min-width: 640px) {
    .report-header {
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
    }
}

.report-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-foreground);
    margin: 0;
}

.report-subtitle {
    font-size: 0.875rem;
    color: var(--color-muted-foreground);
    margin: 4px 0 0;
}

/* Date controls */
.date-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.date-nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    color: var(--color-foreground);
    cursor: pointer;
    transition: all 0.15s ease;
}

.date-nav-btn:hover {
    background: var(--color-accent);
    color: var(--color-accent-foreground);
}

.date-picker-wrap {
    position: relative;
}

.date-input {
    height: 36px;
    padding: 0 12px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    color: var(--color-foreground);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: border-color 0.15s ease;
}

.date-input:focus {
    outline: none;
    border-color: var(--color-ring);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-ring) 25%, transparent);
}

.today-btn {
    height: 36px;
    padding: 0 14px;
    border-radius: 8px;
    border: none;
    background: var(--color-primary);
    color: var(--color-primary-foreground);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s ease;
}

.today-btn:hover {
    opacity: 0.9;
}

/* Loading */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 80px 20px;
    color: var(--color-muted-foreground);
    font-size: 0.875rem;
}

.loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid var(--color-border);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Date banner */
.date-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    background: var(--color-muted);
    border-radius: 12px;
    margin-bottom: 20px;
}

.date-banner-icon {
    font-size: 1.125rem;
}

.date-banner-text {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-foreground);
}

.today-badge {
    margin-left: auto;
    padding: 3px 10px;
    border-radius: 999px;
    background: var(--color-primary);
    color: var(--color-primary-foreground);
    font-size: 0.75rem;
    font-weight: 600;
}

/* Finance grid */
.finance-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    margin-bottom: 24px;
}

@media (min-width: 480px) {
    .finance-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.finance-card {
    padding: 20px;
    border-radius: 14px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.finance-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.finance-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.finance-icon {
    font-size: 1.125rem;
}

.finance-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.finance-value {
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--color-foreground);
    line-height: 1.2;
    margin-bottom: 4px;
}

.finance-meta {
    font-size: 0.75rem;
    color: var(--color-muted-foreground);
}

/* Profit color states */
.profit-positive {
    border-color: hsl(142, 60%, 85%);
    background: hsl(142, 60%, 97%);
}

.profit-positive .finance-value {
    color: hsl(142, 70%, 35%);
}

.profit-negative {
    border-color: hsl(0, 60%, 85%);
    background: hsl(0, 60%, 97%);
}

.profit-negative .finance-value {
    color: hsl(0, 70%, 45%);
}

/* Breakdown section */
.breakdown-section {
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-card);
    overflow: hidden;
}

.breakdown-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--color-border);
}

.breakdown-icon {
    font-size: 1.125rem;
}

.breakdown-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-foreground);
    margin: 0;
}

.breakdown-list {
    padding: 4px 0;
}

.breakdown-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    transition: background 0.1s ease;
}

.breakdown-item:hover {
    background: var(--color-muted);
}

.breakdown-item + .breakdown-item {
    border-top: 1px solid color-mix(in srgb, var(--color-border) 50%, transparent);
}

.breakdown-item-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.breakdown-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: var(--color-muted);
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--color-muted-foreground);
    flex-shrink: 0;
}

.breakdown-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-foreground);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.breakdown-item-right {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-shrink: 0;
}

.breakdown-qty {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--color-foreground);
    white-space: nowrap;
}

.breakdown-amount {
    font-size: 0.8125rem;
    color: var(--color-muted-foreground);
    white-space: nowrap;
    min-width: 90px;
    text-align: right;
}

.breakdown-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-top: 2px solid var(--color-border);
    background: var(--color-muted);
}

.breakdown-total-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-foreground);
}

.breakdown-total-value {
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--color-foreground);
}

/* Empty states */
.empty-breakdown {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 40px 20px;
    color: var(--color-muted-foreground);
}

.empty-icon {
    font-size: 2rem;
}

.empty-breakdown p {
    font-size: 0.875rem;
    margin: 0;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 80px 20px;
    color: var(--color-muted-foreground);
}

.empty-state-icon {
    font-size: 2.5rem;
}

.empty-state p {
    font-size: 0.9375rem;
    margin: 0;
}

/* Dark mode adjustments for profit cards */
@media (prefers-color-scheme: dark) {
    .profit-positive {
        border-color: hsl(142, 40%, 25%);
        background: hsl(142, 30%, 12%);
    }
    .profit-positive .finance-value {
        color: hsl(142, 70%, 60%);
    }
    .profit-negative {
        border-color: hsl(0, 40%, 25%);
        background: hsl(0, 30%, 12%);
    }
    .profit-negative .finance-value {
        color: hsl(0, 70%, 60%);
    }
}

/* Edit Cost Form Styling */
.edit-cost-btn {
    margin-left: auto;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-primary);
    background: transparent;
    border: none;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.15s ease;
}
.edit-cost-btn:hover {
    opacity: 1;
    text-decoration: underline;
}
.inline-cost-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 4px;
}
.cost-input, .cost-note-input {
    width: 100%;
    height: 32px;
    padding: 0 10px;
    border-radius: 6px;
    border: 1px solid var(--color-border);
    background: var(--color-background);
    color: var(--color-foreground);
    font-size: 0.8125rem;
    box-sizing: border-box;
}
.cost-input:focus, .cost-note-input:focus {
    outline: none;
    border-color: var(--color-primary);
}
.cost-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 6px;
    margin-top: 2px;
}
.cost-cancel-btn {
    padding: 0 10px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    color: var(--color-foreground);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
}
.cost-cancel-btn:hover {
    background: var(--color-muted);
}
.cost-save-btn {
    padding: 0 10px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: var(--color-primary);
    color: var(--color-primary-foreground);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
}
.cost-save-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
