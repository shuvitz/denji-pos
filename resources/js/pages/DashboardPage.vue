<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div>
            <h1 class="text-2xl font-bold text-foreground">Dashboard</h1>
            <p class="mt-1 text-sm text-muted-foreground">Welcome back, {{ auth.user?.name }}!</p>
        </div>

        <!-- Stats cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="stat in statsCards" :key="stat.label">
                <CardHeader>
                    <CardDescription>{{ stat.label }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <!-- Profile card -->
        <Card>
            <CardHeader>
                <CardTitle>Your Profile</CardTitle>
                <CardDescription>Account information</CardDescription>
            </CardHeader>
            <CardContent>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-muted-foreground">Name</dt>
                        <dd class="mt-1 text-sm text-foreground">{{ auth.user?.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-muted-foreground">Email</dt>
                        <dd class="mt-1 text-sm text-foreground">{{ auth.user?.email }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth.store';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';

const auth = useAuthStore();

const statsCards = [
    { label: 'Total Projects', value: '—' },
    { label: 'Active Tasks', value: '—' },
    { label: 'Completed', value: '—' },
];
</script>
