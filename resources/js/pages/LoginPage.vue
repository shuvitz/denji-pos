<template>
    <div class="min-h-screen flex items-center justify-center bg-background">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <CardTitle class="text-2xl">
                    {{ isLogin ? 'Sign In' : 'Create Account' }}
                </CardTitle>
                <CardDescription>
                    {{ isLogin ? 'Welcome back! Enter your credentials.' : 'Fill in the details to get started.' }}
                </CardDescription>
            </CardHeader>

            <CardContent>
                <!-- Error alert -->
                <div v-if="errorMessage" class="mb-4 rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
                    {{ errorMessage }}
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <!-- Name (register only) -->
                    <div v-if="!isLogin" class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" type="text" placeholder="John Doe" required />
                        <p v-if="auth.errors?.name" class="text-xs text-destructive">{{ auth.errors.name[0] }}</p>
                    </div>

                    <!-- Email -->
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" placeholder="you@example.com" required />
                        <p v-if="auth.errors?.email" class="text-xs text-destructive">{{ auth.errors.email[0] }}</p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-2">
                        <Label for="password">Password</Label>
                        <Input id="password" v-model="form.password" type="password" placeholder="••••••••" required />
                        <p v-if="auth.errors?.password" class="text-xs text-destructive">{{ auth.errors.password[0] }}</p>
                    </div>

                    <!-- Confirm Password (register only) -->
                    <div v-if="!isLogin" class="space-y-2">
                        <Label for="password_confirmation">Confirm Password</Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" placeholder="••••••••" required />
                    </div>

                    <!-- Submit -->
                    <Button type="submit" class="w-full">
                        <span v-if="auth.isLoading">Please wait…</span>
                        <span v-else>{{ isLogin ? 'Sign In' : 'Create Account' }}</span>
                    </Button>
                </form>
            </CardContent>

            <CardFooter class="justify-center">
                <p class="text-sm text-muted-foreground">
                    {{ isLogin ? "Don't have an account?" : 'Already have an account?' }}
                    <button @click="toggleMode" class="font-medium text-primary hover:underline ml-1">
                        {{ isLogin ? 'Sign up' : 'Sign in' }}
                    </button>
                </p>
            </CardFooter>
        </Card>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.store';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const router = useRouter();
const auth = useAuthStore();

const isLogin = ref(true);
const errorMessage = ref('');

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function toggleMode() {
    isLogin.value = !isLogin.value;
    errorMessage.value = '';
    auth.errors = {};
}

async function handleSubmit() {
    errorMessage.value = '';

    try {
        if (isLogin.value) {
            await auth.login({
                email: form.email,
                password: form.password,
            });
        } else {
            await auth.register({ ...form });
        }

        router.push({ name: 'dashboard' });
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ?? 'Something went wrong. Please try again.';
    }
}
</script>
