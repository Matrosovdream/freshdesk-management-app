<script setup>
import { reactive, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';

import { useAuth } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const toast = useToast();

const token = computed(() => (route.query.token || '').toString());
const email = computed(() => (route.query.email || '').toString());

const form = reactive({
    password: '',
    password_confirmation: '',
});
const errors = reactive({});
const generalError = ref('');
const loading = ref(false);

async function submit() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    generalError.value = '';
    loading.value = true;

    try {
        await auth.reset({
            token: token.value,
            email: email.value,
            password: form.password,
            password_confirmation: form.password_confirmation,
        });
        toast.add({
            severity: 'success',
            summary: 'Password updated.',
            life: 4000,
        });
        router.push({ name: 'portal.home' });
    } catch (e) {
        const data = e?.response?.data;
        if (e?.response?.status === 422 && data?.errors) {
            Object.assign(errors, data.errors);
        } else {
            generalError.value = data?.message || 'This link is invalid or has expired.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <header class="text-center mb-5">
            <h2 class="text-lg font-semibold text-gray-800">Set a new password</h2>
        </header>

        <Message v-if="generalError" severity="error" :closable="false" class="mb-3">
            {{ generalError }}
        </Message>

        <form class="space-y-3" @submit.prevent="submit">
            <div>
                <label class="block text-sm text-gray-700 mb-1" for="reset-email">Email</label>
                <InputText id="reset-email" :modelValue="email" class="w-full" readonly />
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1" for="reset-password">New password</label>
                <Password id="reset-password" v-model="form.password" toggleMask class="w-full" inputClass="w-full" required />
                <p v-if="errors.password" class="text-xs text-red-600 mt-1">{{ errors.password?.[0] || errors.password }}</p>
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1" for="reset-confirm">Confirm new password</label>
                <Password id="reset-confirm" v-model="form.password_confirmation" toggleMask :feedback="false" class="w-full" inputClass="w-full" required />
                <p v-if="errors.password_confirmation" class="text-xs text-red-600 mt-1">{{ errors.password_confirmation?.[0] || errors.password_confirmation }}</p>
            </div>

            <Button type="submit" label="Set new password" class="w-full" :loading="loading" :disabled="loading" />
        </form>
    </div>
</template>
