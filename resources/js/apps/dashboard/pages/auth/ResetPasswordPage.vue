<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';
import { useAuth } from '@/stores/auth';

const auth = useAuth();
const route = useRoute();
const router = useRouter();

const form = reactive({
    token: route.query.token || '',
    email: route.query.email || '',
    password: '',
    password_confirmation: '',
});
const error = ref('');
const loading = ref(false);

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await auth.reset({ ...form });
        router.push('/');
    } catch (e) {
        error.value = e?.response?.data?.message || 'Could not reset password.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="bg-surface-0 dark:bg-surface-900 rounded-2xl p-8 shadow">
        <h2 class="text-xl font-medium mb-6">Set a new password</h2>
        <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

        <form @submit.prevent="submit" class="flex flex-col gap-3">
            <InputText v-model="form.email" type="email" placeholder="Email" class="w-full" required />
            <Password v-model="form.password" :toggleMask="true" fluid placeholder="New password" required />
            <Password v-model="form.password_confirmation" :toggleMask="true" :feedback="false" fluid placeholder="Confirm password" required />
            <Button type="submit" label="Reset password" :loading="loading" class="w-full mt-2" />
        </form>
    </div>
</template>
