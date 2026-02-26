<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Item variants</h1>
                <p class="mt-1 text-sm text-muted-foreground">Manage item variants.</p>
            </div>
            <Button size="sm" @click="openCreate">
                Add variant
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Item
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Variant name
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    SKU
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Size
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Color
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Purchase price
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Selling price
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Stock
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="variant in variants" :key="variant.id">
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ variant.item?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ variant.name ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ variant.sku }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ variant.size ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ variant.color ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    {{ variant.purchase_price ?? 0 }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    {{ variant.selling_price ?? 0 }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    {{ variant.stock ?? 0 }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="xs" variant="outline" @click="openEdit(variant)">
                                            Edit
                                        </Button>
                                        <Button
                                            size="xs"
                                            variant="destructive"
                                            class="text-white"
                                            @click="confirmDelete(variant)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!isLoading && variants.length === 0">
                                <td colspan="9" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                    No variants yet.
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
                        {{ editingVariant ? 'Edit variant' : 'Add variant' }}
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
                        <Label for="item_id">Item</Label>
                        <select
                            id="item_id"
                            v-model="form.item_id"
                            class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">
                                Select item
                            </option>
                            <option
                                v-for="item in allItems"
                                :key="item.id"
                                :value="String(item.id)"
                            >
                                {{ item.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="name">Variant name</Label>
                        <Input id="name" v-model="form.name" type="text" />
                    </div>
                    <div class="space-y-2">
                        <Label for="sku">SKU</Label>
                        <Input id="sku" v-model="form.sku" type="text" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="size">Size</Label>
                            <Input id="size" v-model="form.size" type="text" />
                        </div>
                        <div class="space-y-2">
                            <Label for="color">Color</Label>
                            <Input id="color" v-model="form.color" type="text" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="stock">Stock</Label>
                            <Input
                                id="stock"
                                v-model.number="form.stock"
                                type="number"
                                min="0"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="minimum_stock">Minimum stock</Label>
                            <Input
                                id="minimum_stock"
                                v-model.number="form.minimum_stock"
                                type="number"
                                min="0"
                                required
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="purchase_price">Purchase price</Label>
                            <Input
                                id="purchase_price"
                                v-model.number="form.purchase_price"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="selling_price">Selling price</Label>
                            <Input
                                id="selling_price"
                                v-model.number="form.selling_price"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                        </div>
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
import { fetchItemVariants, createItemVariant, updateItemVariant, deleteItemVariant } from '@/api/item-variants.api';
import { fetchItems } from '@/api/items.api';
import { useUiStore } from '@/stores/ui.store';

const ui = useUiStore();

const variants = ref([]);
const allItems = ref([]);
const pagination = reactive({
    current_page: 1,
    last_page: 1,
});
const isLoading = ref(false);
const isSaving = ref(false);

const dialogOpen = ref(false);
const editingVariant = ref(null);
const form = reactive({
    item_id: '',
    name: '',
    sku: '',
    size: '',
    color: '',
    stock: 0,
    minimum_stock: 0,
    purchase_price: 0,
    selling_price: 0,
});

async function load(page = 1) {
    isLoading.value = true;
    try {
        const [{ data: variantRes }, { data: itemRes }] = await Promise.all([
            fetchItemVariants(page),
            fetchItems(1),
        ]);
        variants.value = variantRes.data.data;
        pagination.current_page = variantRes.data.current_page;
        pagination.last_page = variantRes.data.last_page;
        allItems.value = itemRes.data.data;
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not load variants', 'error');
    } finally {
        isLoading.value = false;
    }
}

function changePage(page) {
    if (page < 1 || page > pagination.last_page) return;
    load(page);
}

function openCreate() {
    editingVariant.value = null;
    form.item_id = allItems.value[0] ? String(allItems.value[0].id) : '';
    form.name = '';
    form.sku = '';
    form.size = '';
    form.color = '';
    form.stock = 0;
    form.minimum_stock = 0;
    form.purchase_price = 0;
    form.selling_price = 0;
    dialogOpen.value = true;
}

function openEdit(variant) {
    editingVariant.value = variant;
    form.item_id = String(variant.item_id);
    form.name = variant.name ?? '';
    form.sku = variant.sku;
    form.size = variant.size ?? '';
    form.color = variant.color ?? '';
    form.stock = variant.stock ?? 0;
    form.minimum_stock = variant.minimum_stock ?? 0;
    form.purchase_price = variant.purchase_price ?? 0;
    form.selling_price = variant.selling_price ?? 0;
    dialogOpen.value = true;
}

function closeDialog() {
    dialogOpen.value = false;
}

async function save() {
    isSaving.value = true;
    try {
        const payload = {
            item_id: Number(form.item_id),
            name: form.name || null,
            sku: form.sku,
            size: form.size || null,
            color: form.color || null,
            stock: Number(form.stock),
            minimum_stock: Number(form.minimum_stock),
            purchase_price: Number(form.purchase_price),
            selling_price: Number(form.selling_price),
        };

        if (editingVariant.value) {
            await updateItemVariant(editingVariant.value.id, payload);
            ui.showToast('Variant updated', 'success');
        } else {
            await createItemVariant(payload);
            ui.showToast('Variant created', 'success');
        }

        dialogOpen.value = false;
        await load(pagination.current_page);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not save variant', 'error');
    } finally {
        isSaving.value = false;
    }
}

async function confirmDelete(variant) {
    if (!window.confirm('Delete this variant?')) return;
    try {
        await deleteItemVariant(variant.id);
        ui.showToast('Variant deleted', 'success');
        const targetPage = variants.value.length === 1 && pagination.current_page > 1
            ? pagination.current_page - 1
            : pagination.current_page;
        await load(targetPage);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete variant', 'error');
    }
}

onMounted(() => {
    load();
});
</script>
