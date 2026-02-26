<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Items</h1>
                <p class="mt-1 text-sm text-muted-foreground">Manage items. Stock is per variant.</p>
            </div>
            <Button size="sm" @click="openCreate">
                Add item
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Name
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Category
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Total Stock
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Description
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="item in items" :key="item.id">
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ item.name }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ item.category?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ item.variants_sum_stock ?? 0 }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ item.description ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="xs" variant="outline" @click="openEdit(item)">
                                            Edit
                                        </Button>
                                        <Button size="xs" variant="destructive" class="text-white" @click="confirmDelete(item)">
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!isLoading && items.length === 0">
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                    No items yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between px-4 py-3 border-t border-border">
                    <p class="text-xs text-muted-foreground">
                        Page {{ pagination.current_page }} of {{ pagination.last_page }}
                    </p>
                    <div class="flex items-center gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="pagination.current_page <= 1 || isLoading"
                            @click="changePage(pagination.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="pagination.current_page >= pagination.last_page || isLoading"
                            @click="changePage(pagination.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div
            v-if="dialogOpen"
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/40"
        >
            <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-lg relative">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">
                        {{ editingItem ? 'Edit item' : 'Add item' }}
                    </h2>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        @click="closeDialog"
                    >
                        ×
                    </button>
                </div>
                <form @submit.prevent="save" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" type="text" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="category_id">Category</Label>
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="none">No category</option>
                            <option
                                v-for="category in allCategories"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Input id="description" v-model="form.description" type="text" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <Button type="button" variant="outline" @click="closeDialog">
                            Close
                        </Button>
                        <Button type="submit">
                            <span v-if="isSaving">Saving…</span>
                            <span v-else>Save</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fetchItems, createItem, updateItem, deleteItem } from '@/api/items.api';
import { fetchCategories } from '@/api/categories.api';
import { useUiStore } from '@/stores/ui.store';

const ui = useUiStore();

const items = ref([]);
const allCategories = ref([]);
const pagination = reactive({
    current_page: 1,
    last_page: 1,
});
const isLoading = ref(false);
const isSaving = ref(false);

const dialogOpen = ref(false);
const editingItem = ref(null);
const form = reactive({
    name: '',
    description: '',
    category_id: 'none',
});

async function load(page = 1) {
    isLoading.value = true;
    try {
        const [{ data: itemRes }, { data: catRes }] = await Promise.all([
            fetchItems(page),
            fetchCategories(1),
        ]);
        items.value = itemRes.data.data;
        pagination.current_page = itemRes.data.current_page;
        pagination.last_page = itemRes.data.last_page;
        allCategories.value = catRes.data.data;
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not load items', 'error');
    } finally {
        isLoading.value = false;
    }
}

function changePage(page) {
    if (page < 1 || page > pagination.last_page) return;
    load(page);
}

function openCreate() {
    editingItem.value = null;
    form.name = '';
    form.description = '';
    form.category_id = 'none';
    dialogOpen.value = true;
}

function openEdit(item) {
    editingItem.value = item;
    form.name = item.name;
    form.description = item.description ?? '';
    form.category_id = item.category_id ? String(item.category_id) : 'none';
    dialogOpen.value = true;
}

function closeDialog() {
    dialogOpen.value = false;
}

async function save() {
    isSaving.value = true;
    try {
        const payload = {
            name: form.name,
            description: form.description || null,
            category_id: form.category_id === 'none' ? null : Number(form.category_id),
        };

        if (editingItem.value) {
            await updateItem(editingItem.value.id, payload);
            ui.showToast('Item updated', 'success');
        } else {
            await createItem(payload);
            ui.showToast('Item created', 'success');
        }

        dialogOpen.value = false;
        await load(pagination.current_page);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not save item', 'error');
    } finally {
        isSaving.value = false;
    }
}

async function confirmDelete(item) {
    if (!window.confirm(`Delete item "${item.name}"?`)) return;
    try {
        await deleteItem(item.id);
        ui.showToast('Item deleted', 'success');
        const targetPage = items.value.length === 1 && pagination.current_page > 1
            ? pagination.current_page - 1
            : pagination.current_page;
        await load(targetPage);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete item', 'error');
    }
}

onMounted(() => {
    load();
});
</script>
