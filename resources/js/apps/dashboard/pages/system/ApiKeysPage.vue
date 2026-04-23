<script setup>
import { onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Drawer from 'primevue/drawer';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import DatePicker from 'primevue/datepicker';
import Message from 'primevue/message';
import { useSystem } from '@/stores/system';
import { useUi } from '@/stores/ui';

const system = useSystem();
const ui = useUi();

const drawerOpen = ref(false);
const form = reactive({ name: '', scopes: [], expires_at: null });
const plaintextKey = ref(null);

// Minimal scope list — real list comes from rights catalog on the backend.
const SCOPE_OPTIONS = [
    'tickets.view', 'tickets.create', 'tickets.update',
    'contacts.view', 'contacts.create',
    'reports.view',
];

async function createKey() {
    try {
        const res = await system.createApiKey(form);
        plaintextKey.value = res?.plaintext ?? res?.token ?? res;
        drawerOpen.value = false;
        Object.assign(form, { name: '', scopes: [], expires_at: null });
        await system.fetchApiKeys();
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Create failed.' });
    }
}

async function rotate(id) {
    try {
        const res = await system.rotateApiKey(id);
        plaintextKey.value = res?.plaintext ?? res?.token ?? res;
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Rotate failed.' });
    }
}

async function revoke(id) {
    try {
        await system.revokeApiKey(id);
        ui.pushToast({ severity: 'warn', summary: 'Key revoked.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Revoke failed.' });
    }
}

function copyKey() {
    if (!plaintextKey.value) return;
    navigator.clipboard?.writeText(plaintextKey.value);
    ui.pushToast({ severity: 'success', summary: 'Copied.' });
}

onMounted(() => system.fetchApiKeys());
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">API keys</h1>
            <Button label="+ New key" icon="pi pi-plus" @click="drawerOpen = true" />
        </div>

        <DataTable :value="system.apiKeys" stripedRows dataKey="id">
            <Column field="name" header="Name" />
            <Column field="prefix" header="Prefix" />
            <Column header="Scopes">
                <template #body="{ data }">{{ (data.scopes || []).join(', ') }}</template>
            </Column>
            <Column field="created_by.name" header="Created by" />
            <Column field="last_used_at" header="Last used" />
            <Column field="status" header="Status" />
            <Column header="Actions" style="width: 12rem">
                <template #body="{ data }">
                    <Button icon="pi pi-refresh" text rounded @click="rotate(data.id)" aria-label="Rotate" />
                    <Button icon="pi pi-ban" text rounded severity="danger" @click="revoke(data.id)" aria-label="Revoke" />
                </template>
            </Column>
        </DataTable>

        <Drawer v-model:visible="drawerOpen" position="right" :style="{ width: '28rem' }" header="New API key">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="text-sm font-medium">Name</label>
                    <InputText v-model="form.name" class="w-full" required />
                </div>
                <div>
                    <label class="text-sm font-medium">Scopes</label>
                    <MultiSelect v-model="form.scopes" :options="SCOPE_OPTIONS" class="w-full" filter />
                </div>
                <div>
                    <label class="text-sm font-medium">Expires at</label>
                    <DatePicker v-model="form.expires_at" showIcon class="w-full" />
                </div>
                <div class="flex justify-end gap-2 mt-2">
                    <Button label="Cancel" severity="secondary" outlined @click="drawerOpen = false" />
                    <Button label="Create" @click="createKey" />
                </div>
            </div>
        </Drawer>

        <Dialog :visible="!!plaintextKey" modal header="Your new key" :closable="false" :style="{ width: '32rem' }" @update:visible="(v) => !v && (plaintextKey = null)">
            <Message severity="warn" :closable="false">
                This is the only time you'll see this key. Store it securely now.
            </Message>
            <pre class="mt-3 p-3 bg-surface-100 dark:bg-surface-800 rounded break-all text-xs">{{ plaintextKey }}</pre>
            <template #footer>
                <Button label="Copy" icon="pi pi-copy" outlined @click="copyKey" />
                <Button label="I've saved it" @click="plaintextKey = null" />
            </template>
        </Dialog>
    </div>
</template>
