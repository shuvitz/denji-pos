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
                                    Customer
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
                                    {{ movement.customer?.name ?? '—' }}
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
                                                : (movement.type === 'out'
                                                    ? 'bg-red-100 text-red-800'
                                                    : 'bg-muted text-muted-foreground'),
                                        ]"
                                    >
                                        {{ movement.type === 'in' ? 'IN' : (movement.type === 'out' ? 'OUT' : 'ADJ') }}
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
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-muted-foreground">
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
            <div class="w-full max-w-6xl rounded-lg bg-card p-6 shadow-lg">
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
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                                <option value="adjustment">ADJUSTMENT</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="customer_id">Customer</Label>
                            <select
                                id="customer_id"
                                v-model="form.customer_id"
                                :disabled="customers.length === 0"
                                class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">
                                    {{ customers.length ? 'Select customer (optional)' : 'No customers yet' }}
                                </option>
                                <option
                                    v-for="c in customers"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    {{ c.name }}
                                </option>
                            </select>
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
                    </div>

                    <div class="space-y-2">
                        <Label for="note">Note</Label>
                        <Input id="note" v-model="form.note" type="text" />
                    </div>

                    <div v-if="editingMovement" class="space-y-2">
                        <Label for="item_id">Item</Label>
                        <select
                            id="item_id"
                            v-model="form.item_id"
                            disabled
                            class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option :value="String(form.item_id)">
                                {{ editingMovement?.item?.name ?? '—' }}
                            </option>
                        </select>
                    </div>

                    <div v-if="editingMovement" class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="variant_id">Item variant</Label>
                            <select
                                id="variant_id"
                                v-model="form.variant_id"
                                disabled
                                class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option :value="String(form.variant_id)">
                                    {{ editingMovement?.variant?.name ?? editingMovement?.variant?.sku ?? '—' }}
                                </option>
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
                                disabled
                            />
                        </div>
                    </div>

                    <div v-if="!editingMovement" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-foreground">Cart items</div>
                            <Button type="button" size="sm" variant="outline" @click="addCartLine">
                                Add item
                            </Button>
                        </div>

                        <div class="overflow-x-auto rounded-md border border-border">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted/40">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                            Item
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                            Variant
                                        </th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                            Qty
                                        </th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                            Cost / unit
                                        </th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                            Total
                                        </th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="(line, idx) in cart" :key="line.key">
                                        <td class="px-3 py-2">
                                            <select
                                                v-model="line.item_id"
                                                class="border-input data-placeholder:text-muted-foreground flex h-9 w-56 items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                                @change="onCartItemChange(line)"
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
                                        </td>
                                        <td class="px-3 py-2">
                                            <select
                                                v-model="line.variant_id"
                                                class="border-input data-placeholder:text-muted-foreground flex h-9 w-56 items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="!line.item_id || isVariantsLoading(line.item_id)"
                                                @change="onCartVariantChange(line)"
                                            >
                                                <option value="">
                                                    {{
                                                        !line.item_id
                                                            ? 'Select item first'
                                                            : (isVariantsLoading(line.item_id)
                                                                ? 'Loading variants...'
                                                                : ((variantsByItem[line.item_id] || []).length ? 'Select variant' : 'No variants'))
                                                    }}
                                                </option>
                                                <option
                                                    v-for="variant in (variantsByItem[line.item_id] || [])"
                                                    :key="variant.id"
                                                    :value="String(variant.id)"
                                                >
                                                    {{ variant.name || variant.sku }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input v-model.number="line.qty" type="number" min="1" class="w-24 text-right" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input v-model.number="line.cost_per_unit" type="number" min="0" step="0.01" class="w-32 text-right" />
                                        </td>
                                        <td class="px-3 py-2 text-right text-sm text-foreground">
                                            {{ formatMoney(line.qty * line.cost_per_unit) }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <Button
                                                type="button"
                                                size="xs"
                                                variant="destructive"
                                                class="text-white"
                                                :disabled="cart.length <= 1"
                                                @click="removeCartLine(idx)"
                                            >
                                                Remove
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-end text-sm text-foreground">
                            <span class="text-muted-foreground mr-3">Total</span>
                            <span class="font-medium">{{ formatMoney(cartTotal) }}</span>
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fetchItemMovements, createItemMovement, createItemMovementsBulk, updateItemMovement, deleteItemMovement } from '@/api/item-movements.api';
import { fetchCustomers } from '@/api/customers.api';
import { fetchItems } from '@/api/items.api';
import { fetchItemVariantsByItem } from '@/api/item-variants.api';
import { fetchReferenceTypes } from '@/api/reference-types.api';
import { useUiStore } from '@/stores/ui.store';

const ui = useUiStore();

const movements = ref([]);
const allItems = ref([]);
const customers = ref([]);
const referenceTypes = ref([]);
const pagination = reactive({
    current_page: 1,
    last_page: 1,
});
const isLoading = ref(false);
const isSaving = ref(false);
const variantsByItem = reactive({});
const variantsLoadingByItem = reactive({});

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
    customer_id: '',
    note: '',
});

const cart = ref([
    { key: crypto.randomUUID?.() ?? String(Date.now()), item_id: '', variant_id: '', qty: 1, cost_per_unit: 0 },
]);

const cartTotal = computed(() => cart.value.reduce((sum, l) => sum + (Number(l.qty || 0) * Number(l.cost_per_unit || 0)), 0));

async function load(page = 1) {
    isLoading.value = true;
    try {
        const [{ data: moveRes }, { data: itemRes }, { data: refTypeRes }, { data: custRes }] = await Promise.all([
            fetchItemMovements(page),
            fetchItems(1),
            fetchReferenceTypes(1),
            fetchCustomers(1, { per_page: 200 }),
        ]);
        movements.value = moveRes.data.data;
        pagination.current_page = moveRes.data.current_page;
        pagination.last_page = moveRes.data.last_page;
        allItems.value = itemRes.data.data;
        referenceTypes.value = refTypeRes.data.data;
        customers.value = custRes.data.data;
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
    form.type = 'out';
    form.qty = 1;
    form.cost_per_unit = 0;
    form.movement_at = '';
    form.reference_id = '';
    form.customer_id = '';
    form.note = '';
    cart.value = [
        { key: crypto.randomUUID?.() ?? String(Date.now()), item_id: '', variant_id: '', qty: 1, cost_per_unit: 0 },
    ];
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
    form.customer_id = movement.customer_id ? String(movement.customer_id) : '';
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
        if (editingMovement.value) {
            await updateItemMovement(editingMovement.value.id, {
                movement_at: form.movement_at || null,
                reference_id: form.reference_id ? Number(form.reference_id) : null,
                customer_id: form.customer_id ? Number(form.customer_id) : null,
                note: form.note,
            });
            ui.showToast('Movement updated', 'success');
        } else {
            if (form.type === 'out' && !form.customer_id) {
                ui.showToast('Please select a customer for OUT movements', 'error');
                return;
            }

            const items = cart.value
                .filter((l) => l.variant_id && Number(l.qty) > 0)
                .map((l) => ({
                    variant_id: Number(l.variant_id),
                    qty: Number(l.qty),
                    cost_per_unit: l.cost_per_unit ? Number(l.cost_per_unit) : null,
                }));

            if (items.length === 0) {
                ui.showToast('Cart is empty', 'error');
                return;
            }

            await createItemMovementsBulk({
                type: form.type,
                customer_id: form.customer_id ? Number(form.customer_id) : null,
                movement_at: form.movement_at || null,
                reference_id: form.reference_id ? Number(form.reference_id) : null,
                note: form.note || null,
                items,
            });
            ui.showToast('Movements created', 'success');
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
    () => form.type,
    (type) => {
        if (editingMovement.value) return;
        for (const line of cart.value) {
            applyDefaultCost(line, type);
        }
    },
);

async function loadVariantsForItem(itemId) {
    if (!itemId) return;
    if (variantsByItem[itemId]) return;
    if (variantsLoadingByItem[itemId]) return;

    variantsLoadingByItem[itemId] = true;
    try {
        const { data } = await fetchItemVariantsByItem(Number(itemId));
        variantsByItem[itemId] = data.data;
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not load item variants', 'error');
        variantsByItem[itemId] = [];
    } finally {
        variantsLoadingByItem[itemId] = false;
    }
}

function isVariantsLoading(itemId) {
    return !!variantsLoadingByItem[itemId];
}

function applyDefaultCost(line, type) {
    if (!line?.item_id || !line?.variant_id) return;
    const variants = variantsByItem[line.item_id] || [];
    const v = variants.find((x) => String(x.id) === String(line.variant_id));
    if (!v) return;

    if (type === 'in') {
        line.cost_per_unit = Number(v.purchase_price || 0);
    } else if (type === 'out') {
        line.cost_per_unit = Number(v.selling_price || 0);
    }
}

function onCartItemChange(line) {
    if (!line?.item_id) {
        line.variant_id = '';
        return;
    }
    loadVariantsForItem(line.item_id);
    line.variant_id = '';
    line.cost_per_unit = 0;
}

function onCartVariantChange(line) {
    applyDefaultCost(line, form.type);
}

function addCartLine() {
    cart.value.push({ key: crypto.randomUUID?.() ?? String(Date.now() + Math.random()), item_id: '', variant_id: '', qty: 1, cost_per_unit: 0 });
}

function removeCartLine(index) {
    if (cart.value.length <= 1) return;
    cart.value.splice(index, 1);
}

function formatMoney(value) {
    const v = Number(value || 0);
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
}

function formatDate(value) {
    const iso = toIsoLocal(value);
    if (!iso) return '';
    return iso.replace('T', ' ');
}

onMounted(() => {
    load();
});
</script>
