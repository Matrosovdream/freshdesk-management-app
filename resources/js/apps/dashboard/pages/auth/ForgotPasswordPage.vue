<script setup>
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import { useAuth } from '@/stores/auth';

const auth = useAuth();
const email = ref('');
const sent = ref(false);
const loading = ref(false);
const error = ref('');

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await auth.forgot(email.value);
        sent.value = true;
    } catch (e) {
        error.value = e?.response?.data?.message || 'Could not send reset link.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="bg-surface-0 dark:bg-surface-900 rounded-2xl p-8 shadow">
        <h2 class="text-xl font-medium mb-2">Forgot your password?</h2>
        <p class="text-sm text-muted-color mb-6">We'll email you a reset link.</p>

        <Message v-if="sent" severity="success" :closable="false" class="mb-4">
            If an account with that email exists, a reset link is on its way.
        </Message>
        <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

        <form @submit.prevent="submit" class="flex flex-col gap-3">
            <label class="text-sm font-medium">Email</label>
            <InputText v-model="email" type="email" class="w-full" required />
            <Button type="submit" label="Send reset link" :loading="loading" class="w-full mt-2" />
        </form>

        <div class="mt-4 text-sm">
            <router-link to="/login" class="text-primary hover:underline">Back to sign in</router-link>
        </div>
    </div>
</template>
