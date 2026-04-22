<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Message from 'primevue/message';

import { http, ensureCsrf } from '../../../../shared/http';
import { useAuth } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const auth = useAuth();

const token = computed(() => (route.query.token || '').toString());
const email = computed(() => (route.query.email || '').toString());

// States: 'waiting' | 'consuming' | 'expired' | 'done'
const state = ref(token.value ? 'consuming' : 'waiting');
const message = ref('');
const resending = ref(false);

onMounted(async () => {
    if (!token.value) return;
    try {
        await auth.verify(token.value);
        state.value = 'done';
        toast.add({ severity: 'success', summary: 'Email verified.', life: 4000 });
        router.push({ name: 'portal.home' });
    } catch (e) {
        state.value = 'expired';
        message.value = e?.response?.data?.message || 'This link is invalid or has expired.';
    }
});

async function resend() {
    if (!email.value) {
        toast.add({ severity: 'warn', summary: 'Enter your email to receive a new link.', life: 4000 });
        return;
    }
    resending.value = true;
    try {
        await ensureCsrf();
        await http.post('/api/v1/portal/auth/verify/resend', { email: email.value });
        toast.add({
            severity: 'success',
            summary: "If an account exists, we've sent a new link.",
            life: 4000,
        });
    } catch {
        // Swallow — same generic success toast either way.
        toast.add({
            severity: 'success',
            summary: "If an account exists, we've sent a new link.",
            life: 4000,
        });
    } finally {
        resending.value = false;
    }
}
</script>

<template>
    <div>
        <header class="text-center mb-5">
            <h2 class="text-lg font-semibold text-gray-800">Verify your email</h2>
        </header>

        <div v-if="state === 'waiting'" class="space-y-3">
            <p class="text-sm text-gray-600 text-center">
                Check your inbox at <strong>{{ email || 'your email address' }}</strong>.
                We've sent a verification link.
            </p>
            <Button type="button" label="Resend email" class="w-full" :loading="resending" :disabled="resending" @click="resend" />
        </div>

        <div v-else-if="state === 'consuming'" class="text-center py-4">
            <i class="pi pi-spin pi-spinner text-2xl text-gray-400" aria-hidden="true" />
            <p class="text-sm text-gray-500 mt-2">Verifying…</p>
        </div>

        <div v-else-if="state === 'expired'" class="space-y-3">
            <Message severity="warn" :closable="false">{{ message }}</Message>
            <Button type="button" label="Resend email" class="w-full" :loading="resending" :disabled="resending" @click="resend" />
        </div>

        <div class="mt-4 text-center text-sm">
            <router-link :to="{ name: 'portal.login' }" class="text-primary-600 hover:underline">
                Back to sign in
            </router-link>
        </div>
    </div>
</template>
