<script setup>
import { reactive, ref } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';

import { useAuth } from '../../stores/auth';

const auth = useAuth();

const form = reactive({ email: '' });
const errors = reactive({});
const loading = ref(false);
const sent = ref(false);

async function submit() {
    errors.email = null;
    loading.value = true;
    try {
        await auth.forgot(form.email);
        sent.value = true;
    } catch (e) {
        const data = e?.response?.data;
        if (data?.errors?.email) {
            errors.email = data.errors.email;
        } else {
            // Generic message either way to prevent enumeration.
            sent.value = true;
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <header class="text-center mb-5">
            <h2 class="text-lg font-semibold text-gray-800">Reset your password</h2>
            <p class="text-sm text-gray-500 mt-1">We'll email you a link to set a new one.</p>
        </header>

        <Message v-if="sent" severity="success" :closable="false">
            If an account exists for that email, a reset link is on its way.
        </Message>

        <form v-else class="space-y-3" @submit.prevent="submit">
            <div>
                <label class="block text-sm text-gray-700 mb-1" for="forgot-email">Email</label>
                <InputText id="forgot-email" v-model="form.email" type="email" class="w-full" required />
                <p v-if="errors.email" class="text-xs text-red-600 mt-1">{{ errors.email?.[0] || errors.email }}</p>
            </div>
            <Button type="submit" label="Email me a reset link" class="w-full" :loading="loading" :disabled="loading" />
        </form>

        <div class="mt-4 text-center text-sm">
            <router-link :to="{ name: 'portal.login' }" class="text-primary-600 hover:underline">
                Back to sign in
            </router-link>
        </div>
    </div>
</template>
