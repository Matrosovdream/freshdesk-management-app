<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import TagInput from '@/components/shared/TagInput.vue';
import { useContacts } from '@/stores/contacts';
import { useUi } from '@/stores/ui';

const contacts = useContacts();
const ui = useUi();
const router = useRouter();

const form = reactive({
    name: '', email: '', phone: '', mobile: '', twitter_id: '', unique_external_id: '',
    job_title: '', language: '', time_zone: '', tags: [],
});
const errors = reactive({});
const loading = ref(false);
const generalError = ref('');

async function submit(view = false) {
    Object.keys(errors).forEach((k) => delete errors[k]);
    generalError.value = '';
    loading.value = true;
    try {
        const created = await contacts.create(form);
        ui.pushToast({ severity: 'success', summary: 'Contact created.' });
        router.push(view && created?.id ? `/contacts/${created.id}` : '/contacts');
    } catch (e) {
        if (e.validation) Object.assign(errors, e.validation);
        else generalError.value = e?.response?.data?.message || 'Could not create contact.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-4 max-w-3xl">
        <h1 class="text-2xl font-semibold">New contact</h1>
        <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
        <form @submit.prevent="submit(false)" class="flex flex-col gap-3 bg-surface-0 dark:bg-surface-900 p-5 rounded-lg border border-surface-200 dark:border-surface-700">
            <div>
                <label class="text-sm font-medium">Name</label>
                <InputText v-model="form.name" class="w-full" required />
                <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name[0] }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-medium">Email</label>
                    <InputText v-model="form.email" type="email" class="w-full" />
                    <p v-if="errors.email" class="text-xs text-red-500">{{ errors.email[0] }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium">Phone</label>
                    <InputText v-model="form.phone" class="w-full" />
                </div>
                <div>
                    <label class="text-sm font-medium">Mobile</label>
                    <InputText v-model="form.mobile" class="w-full" />
                </div>
                <div>
                    <label class="text-sm font-medium">Job title</label>
                    <InputText v-model="form.job_title" class="w-full" />
                </div>
            </div>
            <div>
                <label class="text-sm font-medium">Tags</label>
                <TagInput v-model="form.tags" />
            </div>
            <div class="flex gap-2 justify-end">
                <Button label="Cancel" severity="secondary" outlined @click="router.push('/contacts')" />
                <Button label="Create" type="submit" :loading="loading" />
                <Button label="Create & view" severity="primary" :loading="loading" @click="submit(true)" />
            </div>
        </form>
    </div>
</template>
