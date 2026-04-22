<script setup>
import { reactive, ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Message from 'primevue/message';

import { useRequests } from '../../stores/requests';
import { useDrafts } from '../../stores/drafts';
import { useConfig } from '../../stores/config';
import AttachmentDropzone from '../../components/AttachmentDropzone.vue';

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const requests = useRequests();
const drafts = useDrafts();
const config = useConfig();

const form = reactive({
    subject: '',
    description: '',
    type: null,
    priority: null,
    product_id: null,
    custom_fields: {},
});

const attachments = ref([]);
const errors = reactive({});
const generalError = ref('');
const draftLoaded = ref(false);
const savingDraft = ref(false);
const submitting = ref(false);
const initialSnapshot = ref('');

const customerEditableTypes = computed(() => {
    const field = config.ticketFields.find((f) => f.name === 'type');
    return field?.customers_can_edit ? field.choices || [] : [];
});

const priorityVisible = computed(() => {
    const field = config.ticketFields.find((f) => f.name === 'priority');
    return !!field?.customers_can_edit;
});

const priorityOptions = computed(() => {
    const field = config.ticketFields.find((f) => f.name === 'priority');
    return field?.choices || [];
});

const productOptions = computed(() => config.products || []);

const customFields = computed(() => {
    return (config.ticketFields || []).filter((f) => f.displayed_to_customers && !['subject', 'description', 'type', 'priority', 'product_id'].includes(f.name));
});

function snapshot() {
    return JSON.stringify({
        subject: form.subject,
        description: form.description,
        type: form.type,
        priority: form.priority,
        product_id: form.product_id,
        custom_fields: form.custom_fields,
    });
}

function scheduleAutosave() {
    if (!draftLoaded.value) return;
    drafts.save({
        subject: form.subject,
        description: form.description,
        type: form.type,
        priority: form.priority,
        product_id: form.product_id,
        custom_fields: form.custom_fields,
    });
}

watch(
    () => [form.subject, form.description, form.type, form.priority, form.product_id, form.custom_fields],
    () => scheduleAutosave(),
    { deep: true },
);

// Fallback autosave every 30s as per spec.
let autosaveInterval = null;

onMounted(async () => {
    try {
        const existing = await drafts.load();
        if (existing && (existing.subject || existing.description)) {
            form.subject = existing.subject || '';
            form.description = existing.description || '';
            form.type = existing.type ?? null;
            form.priority = existing.priority ?? null;
            form.product_id = existing.product_id ?? null;
            form.custom_fields = existing.custom_fields || {};
            toast.add({
                severity: 'info',
                summary: 'Continuing your saved draft.',
                life: 4000,
            });
        }
    } catch {
        // no draft, fine
    }
    initialSnapshot.value = snapshot();
    draftLoaded.value = true;

    autosaveInterval = setInterval(scheduleAutosave, 30000);
});

onBeforeUnmount(() => {
    if (autosaveInterval) clearInterval(autosaveInterval);
});

function isDirty() {
    return snapshot() !== initialSnapshot.value || attachments.value.length > 0;
}

async function submit() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    generalError.value = '';
    submitting.value = true;

    try {
        let payload;
        if (attachments.value.length) {
            payload = new FormData();
            payload.append('subject', form.subject || '');
            payload.append('description', form.description || '');
            if (form.type) payload.append('type', form.type);
            if (form.priority) payload.append('priority', form.priority);
            if (form.product_id) payload.append('product_id', form.product_id);
            for (const [key, value] of Object.entries(form.custom_fields || {})) {
                if (value !== null && value !== undefined && value !== '') {
                    payload.append(`custom_fields[${key}]`, value);
                }
            }
            for (const file of attachments.value) {
                payload.append('attachments[]', file);
            }
        } else {
            payload = {
                subject: form.subject,
                description: form.description,
                type: form.type,
                priority: form.priority,
                product_id: form.product_id,
                custom_fields: form.custom_fields,
            };
        }

        const result = await requests.submit(payload);
        await drafts.clear();
        const id = result?.id;
        toast.add({
            severity: 'success',
            summary: id ? `Request #${id} submitted` : 'Request submitted',
            life: 4000,
        });
        if (id) {
            router.push({ name: 'portal.requests.show', params: { id } });
        } else {
            router.push({ name: 'portal.requests' });
        }
    } catch (e) {
        const data = e?.response?.data;
        if (e?.response?.status === 422 && data?.errors) {
            Object.assign(errors, data.errors);
        } else {
            generalError.value = data?.message || 'Something went wrong. Please try again.';
        }
    } finally {
        submitting.value = false;
    }
}

async function saveDraftAndExit() {
    savingDraft.value = true;
    try {
        await drafts.saveNow({
            subject: form.subject,
            description: form.description,
            type: form.type,
            priority: form.priority,
            product_id: form.product_id,
            custom_fields: form.custom_fields,
        });
        toast.add({ severity: 'success', summary: 'Draft saved.', life: 3000 });
        router.push({ name: 'portal.requests' });
    } catch {
        toast.add({ severity: 'error', summary: 'Could not save draft.', life: 4000 });
    } finally {
        savingDraft.value = false;
    }
}

function cancel() {
    if (!isDirty()) {
        router.push({ name: 'portal.requests' });
        return;
    }
    confirm.require({
        message: 'Discard your changes?',
        header: 'Discard draft',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Discard',
        rejectLabel: 'Keep editing',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await drafts.clear();
            router.push({ name: 'portal.requests' });
        },
    });
}

async function discardDraftBanner() {
    await drafts.clear();
    form.subject = '';
    form.description = '';
    form.type = null;
    form.priority = null;
    form.product_id = null;
    form.custom_fields = {};
    initialSnapshot.value = snapshot();
}
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-4">
        <header class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-800">New request</h1>
        </header>

        <Message v-if="drafts.current" severity="info" :closable="false">
            <div class="flex items-center justify-between gap-3">
                <span>Continuing your saved draft.</span>
                <button type="button" class="text-sm underline" @click="discardDraftBanner">Discard</button>
            </div>
        </Message>

        <Message v-if="generalError" severity="error" :closable="false">
            {{ generalError }}
        </Message>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm text-gray-700 mb-1" for="new-subject">Subject <span class="text-red-500">*</span></label>
                <InputText id="new-subject" v-model="form.subject" class="w-full" required />
                <p v-if="errors.subject" class="text-xs text-red-600 mt-1">{{ errors.subject?.[0] || errors.subject }}</p>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="new-description">Description <span class="text-red-500">*</span></label>
                <Textarea id="new-description" v-model="form.description" rows="6" autoResize class="w-full" required />
                <p class="text-xs text-gray-400 mt-1">Basic formatting: **bold**, *italic*, links, lists.</p>
                <p v-if="errors.description" class="text-xs text-red-600 mt-1">{{ errors.description?.[0] || errors.description }}</p>
            </div>

            <div v-if="customerEditableTypes.length">
                <label class="block text-sm text-gray-700 mb-1" for="new-type">Type</label>
                <Select
                    id="new-type"
                    v-model="form.type"
                    :options="customerEditableTypes"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full"
                    placeholder="Pick a type"
                    showClear
                />
            </div>

            <div v-if="priorityVisible">
                <label class="block text-sm text-gray-700 mb-1" for="new-priority">Priority</label>
                <Select
                    id="new-priority"
                    v-model="form.priority"
                    :options="priorityOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full"
                    showClear
                />
            </div>

            <div v-if="productOptions.length">
                <label class="block text-sm text-gray-700 mb-1" for="new-product">Product</label>
                <Select
                    id="new-product"
                    v-model="form.product_id"
                    :options="productOptions"
                    optionLabel="name"
                    optionValue="id"
                    class="w-full"
                    placeholder="Pick a product"
                    showClear
                />
            </div>

            <div v-for="field in customFields" :key="field.name">
                <label class="block text-sm text-gray-700 mb-1" :for="`cf-${field.name}`">
                    {{ field.label || field.name }}
                </label>
                <InputText :id="`cf-${field.name}`" v-model="form.custom_fields[field.name]" class="w-full" />
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1">Attachments</label>
                <AttachmentDropzone v-model="attachments" />
                <p v-if="errors.attachments" class="text-xs text-red-600 mt-1">{{ errors.attachments?.[0] || errors.attachments }}</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    text
                    :disabled="submitting"
                    @click="cancel"
                />
                <Button
                    type="button"
                    label="Save draft"
                    severity="secondary"
                    outlined
                    :loading="savingDraft"
                    :disabled="submitting || savingDraft"
                    @click="saveDraftAndExit"
                />
                <Button
                    type="submit"
                    label="Submit"
                    :loading="submitting"
                    :disabled="submitting"
                />
            </div>
        </form>
    </div>
</template>
