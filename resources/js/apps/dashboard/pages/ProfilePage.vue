<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import { http, ensureCsrf } from '@shared/http';
import { useAuth } from '@/stores/auth';
import { useUi } from '@/stores/ui';

const auth = useAuth();
const ui = useUi();
const router = useRouter();

const form = reactive({
    name: auth.user?.name || '',
    phone: auth.user?.phone || '',
    timezone: auth.user?.timezone || '',
    password: '',
    password_confirmation: '',
    browser_notifications: auth.user?.preferences?.browser_notifications ?? true,
    email_digest: auth.user?.preferences?.email_digest ?? false,
});
const loading = ref(false);
const error = ref('');

async function save() {
    loading.value = true;
    error.value = '';
    try {
        await ensureCsrf();
        await http.put('/api/v1/admin/profile', form);
        await auth.bootstrap();
        ui.pushToast({ severity: 'success', summary: 'Profile updated.' });
    } catch (e) {
        error.value = e?.response?.data?.message || 'Save failed.';
    } finally {
        loading.value = false;
    }
}

async function logoutOthers() {
    try {
        await ensureCsrf();
        await http.post('/api/v1/admin/auth/logout-others');
        ui.pushToast({ severity: 'success', summary: 'Other sessions signed out.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Could not sign out other sessions.' });
    }
}

async function logout() {
    await auth.logout();
    router.push('/login');
}
</script>

<template>
    <div class="flex flex-col gap-4 max-w-2xl">
        <h1 class="text-2xl font-semibold">Profile</h1>
        <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

        <form @submit.prevent="save" class="flex flex-col gap-3 bg-surface-0 dark:bg-surface-900 p-5 rounded-lg border border-surface-200 dark:border-surface-700">
            <div>
                <label class="text-sm font-medium">Name</label>
                <InputText v-model="form.name" class="w-full" />
            </div>
            <div>
                <label class="text-sm font-medium">Email</label>
                <InputText :value="auth.user?.email" disabled class="w-full" />
            </div>
            <div>
                <label class="text-sm font-medium">Phone</label>
                <InputText v-model="form.phone" class="w-full" />
            </div>
            <div>
                <label class="text-sm font-medium">Timezone</label>
                <InputText v-model="form.timezone" class="w-full" />
            </div>

            <h3 class="mt-3 text-sm font-medium">Change password</h3>
            <Password v-model="form.password" :toggleMask="true" fluid placeholder="New password" />
            <Password v-model="form.password_confirmation" :toggleMask="true" :feedback="false" fluid placeholder="Confirm new password" />

            <h3 class="mt-3 text-sm font-medium">Preferences</h3>
            <label class="flex items-center gap-2 text-sm"><Checkbox v-model="form.browser_notifications" binary /> Browser notifications</label>
            <label class="flex items-center gap-2 text-sm"><Checkbox v-model="form.email_digest" binary /> Daily email digest</label>

            <div class="flex gap-2 justify-end mt-3">
                <Button type="button" label="Sign out" severity="secondary" outlined @click="logout" />
                <Button type="button" label="Sign out of other sessions" severity="warn" outlined @click="logoutOthers" />
                <Button type="submit" label="Save changes" :loading="loading" />
            </div>
        </form>
    </div>
</template>
