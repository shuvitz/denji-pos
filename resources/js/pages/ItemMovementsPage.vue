<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Stock movements</h1>
                <p class="mt-1 text-sm text-muted-foreground">Track stock in and out.</p>
            </div>
            <Button size="sm" @click="openCreate">
                Add movement
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Item
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Cost / unit
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Reference
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Note
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="movement in movements" :key="movement.id">
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ formatDate(movement.movement_at) }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ movement.item?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            movement.type === 'in'
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{ movement.type === 'in' ? 'IN' : 'OUT' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    {{ movement.qty }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    {{ movement.cost_per_unit ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ movement.reference_type?.name ?? movement.reference_type ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ movement.note }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="xs" variant="outline" @click="openEdit(movement)">
                                            Edit
                                        </Button>
                                        <Button size="xs" variant="destructive" class="text-white" @click="confirmDelete(movement)">
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!isLoading && movements.length === 0">
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                    No movements yet.
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
                        {{ editingMovement ? 'Edit movement' : 'Add movement' }}
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
                            :disabled="!!editingMovement || allItems.length === 0"
                            class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="" disabled>
                                {{ allItems.length ? 'Select item' : 'No items available' }}
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
                        <Label for="variant_id">Item variant</Label>
                        <select
                            id="variant_id"
                            v-model="form.variant_id"
                            :disabled="!form.item_id || variantLoading || itemVariants.length === 0"
                            class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">
                                {{
                                    !form.item_id
                                        ? 'Select item first'
                                        : variantLoading
                                            ? 'Loading variants...'
                                            : itemVariants.length
                                                ? 'Select variant'
                                                : 'No variants for this item'
                                }}
                            </option>
                            <option
                                v-for="variant in itemVariants"
                                :key="variant.id"
                                :value="String(variant.id)"
                            >
                                {{ variant.name || variant.sku }}
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="type">Type</Label>
                            <select
                                id="type"
                                v-model="form.type"
                                :disabled="!!editingMovement"
                                class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="in">IN</option>
                                <option value="out">OUT</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="quantity">Quantity</Label>
                            <Input
                                id="quantity"
                                v-model.number="form.qty"
                                type="number"
                                min="1"
                                required
                                :disabled="!!editingMovement"
                            />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="cost_per_unit">Cost per unit</Label>
                        <Input
                            id="cost_per_unit"
                            v-model.number="form.cost_per_unit"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="movement_at">Date</Label>
                        <Input id="movement_at" v-model="form.movement_at" type="datetime-local" />
                    </div>
                    <div class="space-y-2">
                        <Label for="reference_id">Reference</Label>
                        <select
                            id="reference_id"
                            v-model="form.reference_id"
                            class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">
                                Select reference
                            </option>
                            <option
                                v-for="type in referenceTypes"
                                :key="type.id"
                                :value="String(type.id)"
                            >
                                {{ type.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="note">Note</Label>
                        <Input id="note" v-model="form.note" type="text" />
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
import { onMounted, reactive, ref, watch } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fetchItemMovements, createItemMovement, updateItemMovement, deleteItemMovement } from '@/api/item-movements.api';
import { fetchItems } from '@/api/items.api';
import { fetchItemVariantsByItem } from '@/api/item-variants.api';
import { fetchReferenceTypes } from '@/api/reference-types.api';
import { useUiStore } from '@/stores/ui.store';

const ui = useUiStore();

const movements = ref([]);
const allItems = ref([]);
const itemVariants = ref([]);
const referenceTypes = ref([]);
const pagination = reactive({
    current_page: 1,
    last_page: 1,
});
const isLoading = ref(false);
const isSaving = ref(false);
const variantLoading = ref(false);

const dialogOpen = ref(false);
const editingMovement = ref(null);
const form = reactive({
    variant_id: '',
    item_id: '',
    type: 'in',
    qty: 1,
    cost_per_unit: 0,
    movement_at: '',
    reference_id: '',
    note: '',
});

async function load(page = 1) {
    isLoading.value = true;
    try {
        const [{ data: moveRes }, { data: itemRes }, { data: refTypeRes }] = await Promise.all([
            fetchItemMovements(page),
            fetchItems(1),
            fetchReferenceTypes(1),
        ]);
        movements.value = moveRes.data.data;
        pagination.current_page = moveRes.data.current_page;
        pagination.last_page = moveRes.data.last_page;
        allItems.value = itemRes.data.data;
        referenceTypes.value = refTypeRes.data.data;
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not load movements', 'error');
    } finally {
        isLoading.value = false;
    }
}

function changePage(page) {
    if (page < 1 || page > pagination.last_page) return;
    load(page);
}

function openCreate() {
    editingMovement.value = null;
    form.variant_id = '';
    form.item_id = '';
    form.type = 'in';
    form.qty = 1;
    form.cost_per_unit = 0;
    form.movement_at = '';
    form.reference = '';
    form.note = '';
    dialogOpen.value = true;
}

function openEdit(movement) {
    editingMovement.value = movement;
    form.variant_id = movement.variant_id ? String(movement.variant_id) : '';
    form.item_id = String(movement.item_id);
    form.type = movement.type;
    form.qty = movement.qty;
    form.cost_per_unit = movement.cost_per_unit ?? 0;
    form.movement_at = movement.movement_at ? movement.movement_at.slice(0, 16) : '';
    form.reference_id = movement.reference_id ? String(movement.reference_id) : '';
    form.note = movement.note ?? '';
    dialogOpen.value = true;
}

function closeDialog() {
    dialogOpen.value = false;
}

function toIsoLocal(value) {
    if (!value) return null;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return null;
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

async function save() {
    isSaving.value = true;
    try {
        const payload = {
            variant_id: form.variant_id ? Number(form.variant_id) : null,
            item_id: form.item_id ? Number(form.item_id) : null,
            type: form.type,
            qty: Number(form.qty),
            cost_per_unit: form.cost_per_unit ? Number(form.cost_per_unit) : null,
            movement_at: form.movement_at || null,
            reference_id: form.reference_id ? Number(form.reference_id) : null,
            note: form.note,
        };

        if (editingMovement.value) {
            await updateItemMovement(editingMovement.value.id, {
                movement_at: payload.movement_at,
                reference_id: payload.reference_id,
                note: payload.note,
            });
            ui.showToast('Movement updated', 'success');
        } else {
            await createItemMovement(payload);
            ui.showToast('Movement created', 'success');
        }

        dialogOpen.value = false;
        await load(pagination.current_page);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not save movement', 'error');
    } finally {
        isSaving.value = false;
    }
}

async function confirmDelete(movement) {
    if (!window.confirm('Delete this movement?')) return;
    try {
        await deleteItemMovement(movement.id);
        ui.showToast('Movement deleted', 'success');
        const targetPage = movements.value.length === 1 && pagination.current_page > 1
            ? pagination.current_page - 1
            : pagination.current_page;
        await load(targetPage);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete movement', 'error');
    }
}

watch(
    () => form.variant_id,
    (variantId) => {
        if (!variantId || editingMovement.value) return;

        const variant = itemVariants.value.find((v) => String(v.id) === String(variantId));
        if (variant) {
            if (form.type === 'in') {
                form.cost_per_unit = variant.purchase_price || 0;
            } else if (form.type === 'out') {
                form.cost_per_unit = variant.selling_price || 0;
            }
        }
    },
);

watch(
    () => form.type,
    (type) => {
        if (!form.variant_id || editingMovement.value) return;

        const variant = itemVariants.value.find((v) => String(v.id) === String(form.variant_id));
        if (variant) {
            if (type === 'in') {
                form.cost_per_unit = variant.purchase_price || 0;
            } else if (type === 'out') {
                form.cost_per_unit = variant.selling_price || 0;
            }
        }
    },
);

watch(
    () => form.item_id,
    async (itemId) => {
        itemVariants.value = [];

        if (!itemId) {
            form.variant_id = '';
            return;
        }

        variantLoading.value = true;
        try {
            const { data } = await fetchItemVariantsByItem(Number(itemId));
            itemVariants.value = data.data;

            if (!editingMovement.value || itemId !== String(editingMovement.value.item_id)) {
                form.variant_id = '';
            }
        } catch (error) {
            ui.showToast(error.response?.data?.message ?? 'Could not load item variants', 'error');
        } finally {
            variantLoading.value = false;
        }
    },
);

function formatDate(value) {
    const iso = toIsoLocal(value);
    if (!iso) return '';
    return iso.replace('T', ' ');
}

onMounted(() => {
    load();
});
</script>
