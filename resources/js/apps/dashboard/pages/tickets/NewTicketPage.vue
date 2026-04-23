<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Message from 'primevue/message';
import RichEditor from '@/components/shared/RichEditor.vue';
import AttachmentDropzone from '@/components/shared/AttachmentDropzone.vue';
import AssignPicker from '@/components/shared/AssignPicker.vue';
import TagInput from '@/components/shared/TagInput.vue';
import { useTickets } from '@/stores/tickets';
import { useUi } from '@/stores/ui';

const tickets = useTickets();
const ui = useUi();
const router = useRouter();

const form = reactive({
    requester_id: null,
    subject: '',
    description: '',
    status: 2,
    priority: 1,
    source: 2,
    responder_id: null,
    group_id: null,
    company_id: null,
    product_id: null,
    tags: [],
    cc_emails: [],
    attachments: [],
});
const errors = reactive({});
const generalError = ref('');
const loading = ref(false);

const statusOptions = [
    { label: 'Open', value: 2 }, { label: 'Pending', value: 3 }, { label: 'Resolved', value: 4 }, { label: 'Closed', value: 5 },
];
const priorityOptions = [
    { label: 'Low', value: 1 }, { label: 'Medium', value: 2 }, { label: 'High', value: 3 }, { label: 'Urgent', value: 4 },
];
const sourceOptions = [
    { label: 'Email', value: 1 }, { label: 'Portal', value: 2 }, { label: 'Phone', value: 3 }, { label: 'Chat', value: 7 },
];

async function submit(openAfter = false) {
    Object.keys(errors).forEach((k) => delete errors[k]);
    generalError.value = '';
    loading.value = true;
    try {
        const payload = new FormData();
        for (const [k, v] of Object.entries(form)) {
            if (v == null || v === '') continue;
            if (Array.isArray(v)) {
                if (k === 'attachments') v.forEach((f) => payload.append('attachments[]', f));
                else v.forEach((item) => payload.append(`${k}[]`, item));
            } else {
                payload.append(k, v);
            }
        }
        const created = await tickets.create(payload, { multipart: true });
        ui.pushToast({ severity: 'success', summary: 'Ticket created.' });
        if (openAfter && created?.id) router.push(`/tickets/${created.id}`);
        else router.push('/tickets');
    } catch (e) {
        if (e.validation) Object.assign(errors, e.validation);
        else generalError.value = e?.response?.data?.message || 'Could not create ticket.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-4 max-w-4xl">
        <h1 class="text-2xl font-semibold">New ticket</h1>
        <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>

        <form @submit.prevent="submit(false)" class="flex flex-col gap-4 bg-surface-0 dark:bg-surface-900 p-5 rounded-lg border border-surface-200 dark:border-surface-700">
            <div>
                <label class="text-sm font-medium">Requester</label>
                <AssignPicker v-model="form.requester_id" kind="contacts" />
            </div>
            <div>
                <label class="text-sm font-medium">Subject</label>
                <InputText v-model="form.subject" class="w-full" required />
                <p v-if="errors.subject" class="text-xs text-red-500">{{ errors.subject[0] }}</p>
            </div>
            <div>
                <label class="text-sm font-medium">Description</label>
                <RichEditor v-model="form.description" />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="text-sm font-medium">Status</label>
                    <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="text-sm font-medium">Priority</label>
                    <Select v-model="form.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="text-sm font-medium">Source</label>
                    <Select v-model="form.source" :options="sourceOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-medium">Agent</label>
                    <AssignPicker v-model="form.responder_id" kind="agents" />
                </div>
                <div>
                    <label class="text-sm font-medium">Tags</label>
                    <TagInput v-model="form.tags" />
                </div>
            </div>
            <div>
                <label class="text-sm font-medium">Attachments</label>
                <AttachmentDropzone v-model="form.attachments" />
            </div>

            <div class="flex gap-2 justify-end">
                <Button label="Cancel" severity="secondary" outlined @click="router.push('/tickets')" />
                <Button label="Create" type="submit" :loading="loading" />
                <Button label="Create & open" severity="primary" :loading="loading" @click="submit(true)" />
            </div>
        </form>
    </div>
</template>
