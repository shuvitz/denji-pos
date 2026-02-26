<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Categories</h1>
                <p class="mt-1 text-sm text-muted-foreground">Manage item categories.</p>
            </div>
            <Button size="sm" @click="openCreate">
                Add category
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
                                    Code
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
                            <tr v-for="category in categories" :key="category.id">
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ category.name }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ category.code }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ category.description }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="xs" variant="outline" @click="openEdit(category)">
                                            Edit
                                        </Button>
                                        <Button size="xs" variant="destructive" class="text-white" @click="confirmDelete(category)">
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!isLoading && categories.length === 0">
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                    No categories yet.
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
            <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">
                        {{ editingCategory ? 'Edit category' : 'Add category' }}
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
                        <Label for="code">Code</Label>
                        <Input id="code" v-model="form.code" type="text" required />
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
import { fetchCategories, createCategory, updateCategory, deleteCategory } from '@/api/categories.api';
import { useUiStore } from '@/stores/ui.store';

const ui = useUiStore();

const categories = ref([]);
const pagination = reactive({
    current_page: 1,
    last_page: 1,
});
const isLoading = ref(false);
const isSaving = ref(false);

const dialogOpen = ref(false);
const editingCategory = ref(null);
const form = reactive({
    name: '',
    code: '',
    description: '',
});

async function load(page = 1) {
    isLoading.value = true;
    try {
        const { data } = await fetchCategories(page);
        categories.value = data.data.data;
        pagination.current_page = data.data.current_page;
        pagination.last_page = data.data.last_page;
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
            ui.showToast('Category updated', 'success');
        } else {
            await createCategory({
                name: form.name,
                code: form.code,
                description: form.description,
            });
            ui.showToast('Category created', 'success');
        }
        dialogOpen.value = false;
        await load(pagination.current_page);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not save category', 'error');
    } finally {
        isSaving.value = false;
    }
}

async function confirmDelete(category) {
    if (!window.confirm(`Delete category "${category.name}"?`)) return;
    try {
        await deleteCategory(category.id);
        ui.showToast('Category deleted', 'success');
        const targetPage = categories.value.length === 1 && pagination.current_page > 1
            ? pagination.current_page - 1
            : pagination.current_page;
        await load(targetPage);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete category', 'error');
    }
}

onMounted(() => {
    load();
});
</script>
