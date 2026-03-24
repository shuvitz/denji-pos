<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Customers</h1>
                <p class="mt-1 text-sm text-muted-foreground">Manage customer master data.</p>
            </div>
            <Button size="sm" @click="openCreate">
                Add customer
            </Button>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <Input v-model="search" type="text" placeholder="Search name / phone / email" class="sm:w-80" />
            <Button size="sm" variant="outline" :disabled="isLoading" @click="load(1)">
                Search
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
                                    Phone
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="customer in customers" :key="customer.id">
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ customer.name }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ customer.phone ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ customer.email ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-foreground">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            customer.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-muted text-muted-foreground',
                                        ]"
                                    >
                                        {{ customer.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="xs" variant="outline" @click="openEdit(customer)">
                                            Edit
                                        </Button>
                                        <Button size="xs" variant="destructive" class="text-white" @click="confirmDelete(customer)">
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!isLoading && customers.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                    No customers yet.
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
            <div class="w-full max-w-2xl rounded-lg bg-card p-6 shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">
                        {{ editingCustomer ? 'Edit customer' : 'Add customer' }}
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
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" type="text" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="phone">Phone</Label>
                            <Input id="phone" v-model="form.phone" type="text" />
                        </div>
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" />
                        </div>
                        <div class="space-y-2">
                            <Label for="gender">Gender</Label>
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="border-input data-placeholder:text-muted-foreground flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">
                                    Select gender
                                </option>
                                <option value="male">
                                    Male
                                </option>
                                <option value="female">
                                    Female
                                </option>
                                <option value="other">
                                    Other
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="birth_date">Birth date</Label>
                            <Input id="birth_date" v-model="form.birth_date" type="date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="city">City</Label>
                            <Input id="city" v-model="form.city" type="text" />
                        </div>
                        <div class="space-y-2">
                            <Label for="postal_code">Postal code</Label>
                            <Input id="postal_code" v-model="form.postal_code" type="text" />
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border border-input bg-background text-primary focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                            <Label for="is_active">Active</Label>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="address">Address</Label>
                        <Input id="address" v-model="form.address" type="text" />
                    </div>
                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <Input id="notes" v-model="form.notes" type="text" />
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
import { fetchCustomers, createCustomer, updateCustomer, deleteCustomer } from '@/api/customers.api';
import { useUiStore } from '@/stores/ui.store';

const ui = useUiStore();

const customers = ref([]);
const search = ref('');
const pagination = reactive({
    current_page: 1,
    last_page: 1,
});
const isLoading = ref(false);
const isSaving = ref(false);

const dialogOpen = ref(false);
const editingCustomer = ref(null);
const form = reactive({
    name: '',
    phone: '',
    email: '',
    gender: '',
    birth_date: '',
    address: '',
    city: '',
    postal_code: '',
    notes: '',
    is_active: true,
});

async function load(page = 1) {
    isLoading.value = true;
    try {
        const { data } = await fetchCustomers(page, {
            q: search.value || undefined,
        });
        customers.value = data.data.data;
        pagination.current_page = data.data.current_page;
        pagination.last_page = data.data.last_page;
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not load customers', 'error');
    } finally {
        isLoading.value = false;
    }
}

function changePage(page) {
    if (page < 1 || page > pagination.last_page) return;
    load(page);
}

function openCreate() {
    editingCustomer.value = null;
    form.name = '';
    form.phone = '';
    form.email = '';
    form.gender = '';
    form.birth_date = '';
    form.address = '';
    form.city = '';
    form.postal_code = '';
    form.notes = '';
    form.is_active = true;
    dialogOpen.value = true;
}

function openEdit(customer) {
    editingCustomer.value = customer;
    form.name = customer.name ?? '';
    form.phone = customer.phone ?? '';
    form.email = customer.email ?? '';
    form.gender = customer.gender ?? '';
    form.birth_date = customer.birth_date ?? '';
    form.address = customer.address ?? '';
    form.city = customer.city ?? '';
    form.postal_code = customer.postal_code ?? '';
    form.notes = customer.notes ?? '';
    form.is_active = !!customer.is_active;
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
            phone: form.phone || null,
            email: form.email || null,
            gender: form.gender || null,
            birth_date: form.birth_date || null,
            address: form.address || null,
            city: form.city || null,
            postal_code: form.postal_code || null,
            notes: form.notes || null,
            is_active: !!form.is_active,
        };

        if (editingCustomer.value) {
            await updateCustomer(editingCustomer.value.id, payload);
            ui.showToast('Customer updated', 'success');
        } else {
            await createCustomer(payload);
            ui.showToast('Customer created', 'success');
        }

        dialogOpen.value = false;
        await load(pagination.current_page);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not save customer', 'error');
    } finally {
        isSaving.value = false;
    }
}

async function confirmDelete(customer) {
    if (!window.confirm(`Delete customer "${customer.name}"?`)) return;
    try {
        await deleteCustomer(customer.id);
        ui.showToast('Customer deleted', 'success');
        const targetPage = customers.value.length === 1 && pagination.current_page > 1
            ? pagination.current_page - 1
            : pagination.current_page;
        await load(targetPage);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete customer', 'error');
    }
}

onMounted(() => {
    load();
});
</script>

