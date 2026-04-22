<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Message from 'primevue/message';
import Dialog from 'primevue/dialog';

import { http } from '../../../shared/http';
import { useAuth } from '../stores/auth';

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const auth = useAuth();

const profile = reactive({
    name: '',
    phone: '',
    mobile: '',
    job_title: '',
    address: '',
    language: '',
    time_zone: '',
    avatar_url: '',
});

const profileErrors = reactive({});
const savingProfile = ref(false);

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const passwordErrors = reactive({});
const savingPassword = ref(false);

const loggingOutOthers = ref(false);

const deleteDialogOpen = ref(false);
const deleteConfirmText = ref('');
const deleting = ref(false);

onMounted(async () => {
    try {
        const { data } = await http.get('/api/v1/portal/profile');
        const body = data?.data ?? data ?? {};
        Object.assign(profile, {
            name: body.name || '',
            phone: body.phone || '',
            mobile: body.mobile || '',
            job_title: body.job_title || '',
            address: body.address || '',
            language: body.language || '',
            time_zone: body.time_zone || '',
            avatar_url: body.avatar_url || '',
        });
    } catch {
        // fall back to what we already have in the auth store
        if (auth.user) {
            Object.assign(profile, {
                name: auth.user.name || '',
                phone: auth.user.phone || '',
            });
        }
    }
});

async function saveProfile() {
    Object.keys(profileErrors).forEach((k) => delete profileErrors[k]);
    savingProfile.value = true;
    try {
        const { data } = await http.put('/api/v1/portal/profile', profile);
        const body = data?.data ?? data ?? {};
        auth.setUser({ ...(auth.user || {}), ...body });
        toast.add({ severity: 'success', summary: 'Profile saved.', life: 3000 });
    } catch (e) {
        const data = e?.response?.data;
        if (e?.response?.status === 422 && data?.errors) {
            Object.assign(profileErrors, data.errors);
        } else {
            toast.add({ severity: 'error', summary: 'Could not save profile.', life: 3000 });
        }
    } finally {
        savingProfile.value = false;
    }
}

async function savePassword() {
    Object.keys(passwordErrors).forEach((k) => delete passwordErrors[k]);
    savingPassword.value = true;
    try {
        await http.put('/api/v1/portal/profile/password', passwordForm);
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
        toast.add({ severity: 'success', summary: 'Password updated.', life: 3000 });
    } catch (e) {
        const data = e?.response?.data;
        if (e?.response?.status === 422 && data?.errors) {
            Object.assign(passwordErrors, data.errors);
        } else {
            toast.add({ severity: 'error', summary: 'Could not update password.', life: 3000 });
        }
    } finally {
        savingPassword.value = false;
    }
}

async function logoutOthers() {
    loggingOutOthers.value = true;
    try {
        await auth.logoutOthers();
        toast.add({ severity: 'success', summary: 'Other sessions signed out.', life: 3000 });
    } catch {
        toast.add({ severity: 'error', summary: 'Could not sign out other sessions.', life: 3000 });
    } finally {
        loggingOutOthers.value = false;
    }
}

function openDeleteDialog() {
    deleteConfirmText.value = '';
    deleteDialogOpen.value = true;
}

async function confirmDelete() {
    if (deleteConfirmText.value !== 'DELETE') return;
    deleting.value = true;
    try {
        await http.delete('/api/v1/portal/profile');
        toast.add({ severity: 'success', summary: 'Account deleted.', life: 4000 });
        await auth.logout();
        router.push({ name: 'portal.login' });
    } catch {
        toast.add({ severity: 'error', summary: 'Could not delete account.', life: 3000 });
    } finally {
        deleting.value = false;
        deleteDialogOpen.value = false;
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-6">
        <header>
            <h1 class="text-2xl font-semibold text-gray-800">Profile</h1>
        </header>

        <section class="bg-white border border-gray-200 rounded-lg p-4 space-y-3">
            <h2 class="text-lg font-medium text-gray-800">Profile</h2>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="prof-name">Display name</label>
                <InputText id="prof-name" v-model="profile.name" class="w-full" />
                <p v-if="profileErrors.name" class="text-xs text-red-600 mt-1">{{ profileErrors.name?.[0] || profileErrors.name }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-gray-700 mb-1" for="prof-phone">Phone</label>
                    <InputText id="prof-phone" v-model="profile.phone" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1" for="prof-mobile">Mobile</label>
                    <InputText id="prof-mobile" v-model="profile.mobile" class="w-full" />
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="prof-job">Job title</label>
                <InputText id="prof-job" v-model="profile.job_title" class="w-full" />
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="prof-address">Address</label>
                <Textarea id="prof-address" v-model="profile.address" rows="2" autoResize class="w-full" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-gray-700 mb-1" for="prof-lang">Language</label>
                    <InputText id="prof-lang" v-model="profile.language" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1" for="prof-tz">Time zone</label>
                    <InputText id="prof-tz" v-model="profile.time_zone" class="w-full" />
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1">Avatar</label>
                <p class="text-xs text-gray-400">Avatar upload comes in a later step.</p>
            </div>

            <div class="flex justify-end">
                <Button label="Save changes" :loading="savingProfile" :disabled="savingProfile" @click="saveProfile" />
            </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-lg p-4 space-y-3">
            <h2 class="text-lg font-medium text-gray-800">Security</h2>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="pw-current">Current password</label>
                <Password id="pw-current" v-model="passwordForm.current_password" toggleMask :feedback="false" class="w-full" inputClass="w-full" />
                <p v-if="passwordErrors.current_password" class="text-xs text-red-600 mt-1">{{ passwordErrors.current_password?.[0] || passwordErrors.current_password }}</p>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="pw-new">New password</label>
                <Password id="pw-new" v-model="passwordForm.password" toggleMask class="w-full" inputClass="w-full" />
                <p v-if="passwordErrors.password" class="text-xs text-red-600 mt-1">{{ passwordErrors.password?.[0] || passwordErrors.password }}</p>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="pw-confirm">Confirm new password</label>
                <Password id="pw-confirm" v-model="passwordForm.password_confirmation" toggleMask :feedback="false" class="w-full" inputClass="w-full" />
            </div>

            <div class="flex items-center justify-between flex-wrap gap-2">
                <Button
                    severity="secondary"
                    outlined
                    icon="pi pi-sign-out"
                    label="Sign out of other sessions"
                    :loading="loggingOutOthers"
                    :disabled="loggingOutOthers"
                    @click="logoutOthers"
                />
                <Button label="Update password" :loading="savingPassword" :disabled="savingPassword" @click="savePassword" />
            </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-lg p-4 space-y-3">
            <h2 class="text-lg font-medium text-gray-800">Account</h2>

            <div>
                <label class="block text-sm text-gray-700 mb-1">Email</label>
                <InputText :modelValue="auth.user?.email || ''" readonly class="w-full" />
                <p class="text-xs text-gray-400 mt-1">Contact support to change your email.</p>
            </div>

            <div v-if="auth.user?.company">
                <label class="block text-sm text-gray-700 mb-1">Company</label>
                <InputText :modelValue="auth.user.company.name || ''" readonly class="w-full" />
            </div>

            <Divider />

            <div>
                <h3 class="text-sm font-medium text-red-700">Danger zone</h3>
                <p class="text-xs text-gray-500 mt-1">Deleting your account removes access to the portal.</p>
                <Button
                    class="mt-2"
                    severity="danger"
                    outlined
                    icon="pi pi-trash"
                    label="Delete my account"
                    @click="openDeleteDialog"
                />
            </div>
        </section>

        <Dialog
            v-model:visible="deleteDialogOpen"
            modal
            header="Delete account"
            :style="{ width: '420px' }"
        >
            <Message severity="warn" :closable="false" class="mb-3">
                This cannot be undone. Type <strong>DELETE</strong> to confirm.
            </Message>
            <InputText v-model="deleteConfirmText" class="w-full" placeholder="Type DELETE" />
            <template #footer>
                <Button label="Cancel" severity="secondary" text :disabled="deleting" @click="deleteDialogOpen = false" />
                <Button
                    label="Delete account"
                    severity="danger"
                    :loading="deleting"
                    :disabled="deleteConfirmText !== 'DELETE' || deleting"
                    @click="confirmDelete"
                />
            </template>
        </Dialog>
    </div>
</template>
