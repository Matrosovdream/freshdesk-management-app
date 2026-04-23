<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Message from 'primevue/message';
import RichEditor from '@/components/shared/RichEditor.vue';
import { useAgents } from '@/stores/agents';
import { useUi } from '@/stores/ui';

const agents = useAgents();
const ui = useUi();
const router = useRouter();

const form = reactive({
    email: '', ticket_scope: 1, occasional: false, signature: '',
    skill_ids: [], group_ids: [], role_ids: [],
});
const loading = ref(false);
const errors = reactive({});
const generalError = ref('');

const scopeOptions = [
    { label: 'Global', value: 1 }, { label: 'Group', value: 2 }, { label: 'Restricted', value: 3 },
];

async function submit() {
    loading.value = true;
    generalError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    try {
        await agents.create(form);
        ui.pushToast({ severity: 'success', summary: 'Agent created.' });
        router.push('/agents');
    } catch (e) {
        if (e.validation) Object.assign(errors, e.validation);
        else generalError.value = e?.response?.data?.message || 'Could not create agent.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-4 max-w-3xl">
        <h1 class="text-2xl font-semibold">New agent</h1>
        <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
        <form @submit.prevent="submit" class="flex flex-col gap-3 bg-surface-0 dark:bg-surface-900 p-5 rounded-lg border border-surface-200 dark:border-surface-700">
            <div>
                <label class="text-sm font-medium">Email</label>
                <InputText v-model="form.email" type="email" class="w-full" required />
                <p v-if="errors.email" class="text-xs text-red-500">{{ errors.email[0] }}</p>
            </div>
            <div>
                <label class="text-sm font-medium">Ticket scope</label>
                <Select v-model="form.ticket_scope" :options="scopeOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div>
                <label class="text-sm font-medium">Signature</label>
                <RichEditor v-model="form.signature" />
            </div>
            <div class="flex gap-2 justify-end">
                <Button label="Cancel" severity="secondary" outlined @click="router.push('/agents')" />
                <Button label="Create" type="submit" :loading="loading" />
            </div>
        </form>
    </div>
</template>
