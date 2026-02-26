<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Reference types</h1>
                <p class="mt-1 text-sm text-muted-foreground">Manage movement reference types.</p>
            </div>
            <Button size="sm" @click="openCreate">
                Add reference type
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
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="type in types" :key="type.id">
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ type.name }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="xs" variant="outline" @click="openEdit(type)">
                                            Edit
                                        </Button>
                                        <Button
                                            size="xs"
                                            variant="destructive"
                                            class="text-white"
                                            @click="confirmDelete(type)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!isLoading && types.length === 0">
                                <td colspan="2" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                    No reference types yet.
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
                        {{ editingType ? 'Edit reference type' : 'Add reference type' }}
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
import { fetchReferenceTypes, createReferenceType, updateReferenceType, deleteReferenceType } from '@/api/reference-types.api';
import { useUiStore } from '@/stores/ui.store';

const ui = useUiStore();

const types = ref([]);
const pagination = reactive({
    current_page: 1,
    last_page: 1,
});
const isLoading = ref(false);
const isSaving = ref(false);

const dialogOpen = ref(false);
const editingType = ref(null);
const form = reactive({
    name: '',
});

async function load(page = 1) {
    isLoading.value = true;
    try {
        const { data } = await fetchReferenceTypes(page);
        types.value = data.data.data;
        pagination.current_page = data.data.current_page;
        pagination.last_page = data.data.last_page;
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not load reference types', 'error');
    } finally {
        isLoading.value = false;
    }
}

function changePage(page) {
    if (page < 1 || page > pagination.last_page) return;
    load(page);
}

function openCreate() {
    editingType.value = null;
    form.name = '';
    dialogOpen.value = true;
}

function openEdit(type) {
    editingType.value = type;
    form.name = type.name;
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
        };

        if (editingType.value) {
            await updateReferenceType(editingType.value.id, payload);
            ui.showToast('Reference type updated', 'success');
        } else {
            await createReferenceType(payload);
            ui.showToast('Reference type created', 'success');
        }

        dialogOpen.value = false;
        await load(pagination.current_page);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not save reference type', 'error');
    } finally {
        isSaving.value = false;
    }
}

async function confirmDelete(type) {
    if (!window.confirm('Delete this reference type?')) return;
    try {
        await deleteReferenceType(type.id);
        ui.showToast('Reference type deleted', 'success');
        const targetPage = types.value.length === 1 && pagination.current_page > 1
            ? pagination.current_page - 1
            : pagination.current_page;
        await load(targetPage);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete reference type', 'error');
    }
}

onMounted(() => {
    load();
});
</script>
