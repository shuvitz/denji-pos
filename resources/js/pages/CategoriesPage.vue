
<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { fetchCategories, createCategory, updateCategory, deleteCategory } from '@/api/categories.api';
import { useUiStore } from '@/stores/ui.store';

const ui = useUiStore();

const categories = ref([]);
const pagination = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});
const isLoading = ref(false);
const isSaving = ref(false);
const search = ref('');

const dialogOpen = ref(false);
const editingCategory = ref(null);
const form = reactive({
    name: '',
    code: '',
    description: '',
});

const filteredCategories = computed(() => {
    if (!search.value.trim()) return categories.value;
    const q = search.value.toLowerCase();
    return categories.value.filter(
        (c) =>
            c.name.toLowerCase().includes(q) ||
            c.code.toLowerCase().includes(q)
    );
});

async function load(page = 1) {
    isLoading.value = true;
    try {
        const { data } = await fetchCategories(page);
        categories.value = data.data.data;
        pagination.current_page = data.data.current_page;
        pagination.last_page = data.data.last_page;
        pagination.total = data.data.total;
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not load categories', 'error');
    } finally {
        isLoading.value = false;
    }
}

function changePage(page) {
    if (page < 1 || page > pagination.last_page) return;
    load(page);
}

function openCreate() {
    editingCategory.value = null;
    form.name = '';
    form.code = '';
    form.description = '';
    dialogOpen.value = true;
}

function openEdit(category) {
    editingCategory.value = category;
    form.name = category.name;
    form.code = category.code;
    form.description = category.description ?? '';
    dialogOpen.value = true;
}

function closeDialog() {
    dialogOpen.value = false;
}

async function save() {
    isSaving.value = true;
    try {
        if (editingCategory.value) {
            await updateCategory(editingCategory.value.id, {
                name: form.name,
                code: form.code,
                description: form.description,
            });
            ui.showToast('Kategori diperbarui', 'success');
        } else {
            await createCategory({
                name: form.name,
                code: form.code,
                description: form.description,
            });
            ui.showToast('Kategori ditambahkan', 'success');
        }
        dialogOpen.value = false;
        await load(pagination.current_page);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Gagal menyimpan kategori', 'error');
    } finally {
        isSaving.value = false;
    }
}

async function confirmDelete(category) {
    if (!window.confirm(`Hapus kategori "${category.name}"?`)) return;
    try {
        await deleteCategory(category.id);
        ui.showToast('Kategori dihapus', 'success');
        const targetPage =
            categories.value.length === 1 && pagination.current_page > 1
                ? pagination.current_page - 1
                : pagination.current_page;
        await load(targetPage);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Gagal menghapus kategori', 'error');
    }
}

onMounted(() => {
    load();
});
</script>

<template>
    <div class="categories-page">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">📂 Categories</h1>
                <p class="page-subtitle">Kelola kategori menu</p>
            </div>
            <button class="add-btn" @click="openCreate">
                + Tambah Kategori
            </button>
        </div>

        <!-- Search -->
        <div class="search-bar">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" /></svg>
            <input
                v-model="search"
                type="text"
                class="search-input"
                placeholder="Cari kategori..."
            />
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="loading-state">
            <div class="loading-spinner"></div>
            <span>Memuat data...</span>
        </div>

        <!-- Category list -->
        <div v-else-if="filteredCategories.length > 0" class="category-section">
            <div class="category-section-header">
                <span class="section-icon">🏷️</span>
                <h2 class="section-title">Daftar Kategori</h2>
                <span class="section-count">{{ pagination.total }} kategori</span>
            </div>

            <div class="category-list">
                <div
                    v-for="category in filteredCategories"
                    :key="category.id"
                    class="category-item"
                >
                    <div class="category-item-main">
                        <div class="category-name-row">
                            <span class="category-name">{{ category.name }}</span>
                            <span class="category-code">{{ category.code }}</span>
                        </div>
                        <p v-if="category.description" class="category-desc">{{ category.description }}</p>
                    </div>
                    <div class="category-item-meta">
                        <span class="item-count-badge">
                            {{ category.items_count ?? 0 }} item{{ (category.items_count ?? 0) !== 1 ? 's' : '' }}
                        </span>
                        <div class="category-actions">
                            <button class="action-btn edit-btn" @click="openEdit(category)" title="Edit">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                            </button>
                            <button class="action-btn delete-btn" @click="confirmDelete(category)" title="Hapus">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="pagination-bar">
                <span class="pagination-info">
                    Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}
                </span>
                <div class="pagination-actions">
                    <button
                        class="page-btn"
                        :disabled="pagination.current_page <= 1"
                        @click="changePage(pagination.current_page - 1)"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        Sebelumnya
                    </button>
                    <button
                        class="page-btn"
                        :disabled="pagination.current_page >= pagination.last_page"
                        @click="changePage(pagination.current_page + 1)"
                    >
                        Berikutnya
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="empty-state">
            <span class="empty-icon">📭</span>
            <p v-if="search.trim()">Tidak ada kategori yang cocok</p>
            <p v-else>Belum ada kategori</p>
        </div>

        <!-- Dialog -->
        <div v-if="dialogOpen" class="dialog-overlay" @click.self="closeDialog">
            <div class="dialog-content">
                <div class="dialog-header">
                    <h2 class="dialog-title">
                        {{ editingCategory ? 'Edit Kategori' : 'Tambah Kategori' }}
                    </h2>
                    <button type="button" class="dialog-close" @click="closeDialog">×</button>
                </div>
                <form @submit.prevent="save" class="dialog-form">
                    <div class="form-group">
                        <label class="form-label" for="cat-name">Nama</label>
                        <input id="cat-name" v-model="form.name" type="text" class="form-input" required placeholder="contoh: Makanan" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cat-code">Kode</label>
                        <input id="cat-code" v-model="form.code" type="text" class="form-input" required placeholder="contoh: MKN" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cat-desc">Deskripsi</label>
                        <input id="cat-desc" v-model="form.description" type="text" class="form-input" placeholder="Opsional" />
                    </div>
                    <div class="dialog-footer">
                        <button type="button" class="cancel-btn" @click="closeDialog">Batal</button>
                        <button type="submit" class="save-btn" :disabled="isSaving">
                            {{ isSaving ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.categories-page {
    max-width: 640px;
    margin: 0 auto;
}

/* Header */
.page-header {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 20px;
}

@media (min-width: 640px) {
    .page-header {
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
    }
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-foreground);
    margin: 0;
}

.page-subtitle {
    font-size: 0.875rem;
    color: var(--color-muted-foreground);
    margin: 4px 0 0;
}

.add-btn {
    height: 36px;
    padding: 0 16px;
    border-radius: 8px;
    border: none;
    background: var(--color-primary);
    color: var(--color-primary-foreground);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s ease;
    white-space: nowrap;
}

.add-btn:hover {
    opacity: 0.9;
}

/* Search */
.search-bar {
    position: relative;
    margin-bottom: 20px;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-muted-foreground);
    pointer-events: none;
}

.search-input {
    width: 100%;
    height: 40px;
    padding: 0 12px 0 36px;
    border-radius: 10px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    color: var(--color-foreground);
    font-size: 0.875rem;
    transition: border-color 0.15s ease;
    box-sizing: border-box;
}

.search-input::placeholder {
    color: var(--color-muted-foreground);
}

.search-input:focus {
    outline: none;
    border-color: var(--color-ring);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-ring) 25%, transparent);
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

/* Category section */
.category-section {
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-card);
    overflow: hidden;
}

.category-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--color-border);
}

.section-icon {
    font-size: 1.125rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-foreground);
    margin: 0;
}

.section-count {
    margin-left: auto;
    padding: 3px 10px;
    border-radius: 999px;
    background: var(--color-muted);
    color: var(--color-muted-foreground);
    font-size: 0.75rem;
    font-weight: 600;
}

/* Category list */
.category-list {
    padding: 4px 0;
}

.category-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    gap: 16px;
    transition: background 0.1s ease;
}

.category-item:hover {
    background: var(--color-muted);
}

.category-item + .category-item {
    border-top: 1px solid color-mix(in srgb, var(--color-border) 50%, transparent);
}

.category-item-main {
    min-width: 0;
    flex: 1;
}

.category-name-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.category-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-foreground);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.category-code {
    padding: 2px 8px;
    border-radius: 6px;
    background: var(--color-muted);
    color: var(--color-muted-foreground);
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}

.category-desc {
    font-size: 0.8125rem;
    color: var(--color-muted-foreground);
    margin: 4px 0 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Meta + actions */
.category-item-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.item-count-badge {
    padding: 4px 10px;
    border-radius: 8px;
    background: var(--color-muted);
    color: var(--color-foreground);
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
}

.category-actions {
    display: flex;
    gap: 4px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    color: var(--color-muted-foreground);
    cursor: pointer;
    transition: all 0.15s ease;
}

.edit-btn:hover {
    background: var(--color-accent);
    color: var(--color-accent-foreground);
    border-color: var(--color-accent);
}

.delete-btn:hover {
    background: hsl(0, 70%, 95%);
    color: hsl(0, 70%, 45%);
    border-color: hsl(0, 60%, 85%);
}

/* Pagination */
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-top: 2px solid var(--color-border);
    background: var(--color-muted);
}

.pagination-info {
    font-size: 0.8125rem;
    color: var(--color-muted-foreground);
}

.pagination-actions {
    display: flex;
    gap: 8px;
}

.page-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 32px;
    padding: 0 12px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    color: var(--color-foreground);
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.page-btn:hover:not(:disabled) {
    background: var(--color-accent);
}

.page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Empty */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 80px 20px;
    color: var(--color-muted-foreground);
}

.empty-icon {
    font-size: 2.5rem;
}

.empty-state p {
    font-size: 0.9375rem;
    margin: 0;
}

/* Dialog */
.dialog-overlay {
    position: fixed;
    inset: 0;
    z-index: 40;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.4);
}

.dialog-content {
    width: 100%;
    max-width: 420px;
    margin: 16px;
    border-radius: 14px;
    background: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
}

.dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    border-bottom: 1px solid var(--color-border);
}

.dialog-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-foreground);
    margin: 0;
}

.dialog-close {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: var(--color-muted-foreground);
    font-size: 1.25rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.1s ease;
}

.dialog-close:hover {
    background: var(--color-muted);
    color: var(--color-foreground);
}

.dialog-form {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-foreground);
}

.form-input {
    height: 38px;
    padding: 0 12px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background: var(--color-background);
    color: var(--color-foreground);
    font-size: 0.875rem;
    transition: border-color 0.15s ease;
}

.form-input::placeholder {
    color: var(--color-muted-foreground);
}

.form-input:focus {
    outline: none;
    border-color: var(--color-ring);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-ring) 25%, transparent);
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding-top: 4px;
}

.cancel-btn {
    height: 36px;
    padding: 0 16px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    color: var(--color-foreground);
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s ease;
}

.cancel-btn:hover {
    background: var(--color-muted);
}

.save-btn {
    height: 36px;
    padding: 0 20px;
    border-radius: 8px;
    border: none;
    background: var(--color-primary);
    color: var(--color-primary-foreground);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s ease;
}

.save-btn:hover {
    opacity: 0.9;
}

.save-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Dark mode delete button */
@media (prefers-color-scheme: dark) {
    .delete-btn:hover {
        background: hsl(0, 40%, 18%);
        color: hsl(0, 70%, 60%);
        border-color: hsl(0, 40%, 25%);
    }
}
</style>
