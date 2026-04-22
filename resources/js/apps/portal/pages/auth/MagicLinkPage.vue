<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';

import { useAuth } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const auth = useAuth();

const token = computed(() => (route.query.token || '').toString());

// States: 'consuming' | 'expired' | 'done'
const state = ref(token.value ? 'consuming' : 'expired');
const message = ref(token.value ? '' : 'This link is invalid.');

const form = reactive({ email: '' });
const sending = ref(false);
const errors = reactive({});

onMounted(async () => {
    if (!token.value) return;
    try {
        await auth.magicLinkConsume(token.value);
        state.value = 'done';
        toast.add({ severity: 'success', summary: 'Signed in.', life: 3000 });
        router.push({ name: 'portal.home' });
    } catch (e) {
        state.value = 'expired';
        message.value = e?.response?.data?.message || 'This link is invalid or has expired.';
    }
});

async function sendNew() {
    errors.email = null;
    sending.value = true;
    try {
        await auth.magicLinkSend(form.email);
        toast.add({
            severity: 'success',
            summary: "If an account exists, we've sent a link.",
            life: 4000,
        });
    } catch (e) {
        const data = e?.response?.data;
        if (data?.errors?.email) {
            errors.email = data.errors.email;
        } else {
            toast.add({
                severity: 'success',
                summary: "If an account exists, we've sent a link.",
                life: 4000,
            });
        }
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <div>
        <header class="text-center mb-5">
            <h2 class="text-lg font-semibold text-gray-800">Sign-in link</h2>
        </header>

        <div v-if="state === 'consuming'" class="text-center py-4">
            <i class="pi pi-spin pi-spinner text-2xl text-gray-400" aria-hidden="true" />
            <p class="text-sm text-gray-500 mt-2">Signing you in…</p>
        </div>

        <div v-else-if="state === 'expired'" class="space-y-3">
            <Message severity="warn" :closable="false">{{ message }}</Message>
            <form class="space-y-3" @submit.prevent="sendNew">
                <div>
                    <label class="block text-sm text-gray-700 mb-1" for="magic-new-email">Email</label>
                    <InputText id="magic-new-email" v-model="form.email" type="email" class="w-full" required />
                    <p v-if="errors.email" class="text-xs text-red-600 mt-1">{{ errors.email?.[0] || errors.email }}</p>
                </div>
                <Button type="submit" label="Send me a new link" class="w-full" :loading="sending" :disabled="sending" />
            </form>
        </div>

        <div class="mt-4 text-center text-sm">
            <router-link :to="{ name: 'portal.login' }" class="text-primary-600 hover:underline">
                Back to sign in
            </router-link>
        </div>
    </div>
</template>
