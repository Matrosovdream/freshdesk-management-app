<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import { useSystem } from '@/stores/system';
import { useUi } from '@/stores/ui';

const system = useSystem();
const ui = useUi();

const RESOURCES = ['tickets', 'contacts', 'companies', 'agents', 'groups', 'conversations', 'time_entries'];
const intervals = ref({});
const showResyncModal = ref(false);

async function load() {
    await system.fetchSyncJobs();
    intervals.value = { ...(system.syncIntervals || {}) };
}

async function runNow(resource) {
    try {
        await system.runSync(resource);
        ui.pushToast({ severity: 'success', summary: `${resource} sync queued.` });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Queue failed.' });
    }
}

async function fullResync() {
    try {
        await system.fullResync();
        ui.pushToast({ severity: 'warn', summary: 'Full resync queued.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Queue failed.' });
    }
}

onMounted(load);
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Sync jobs</h1>
            <Button label="Full resync" severity="danger" outlined @click="showResyncModal = true" />
        </div>

        <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-5">
            <h3 class="font-medium mb-3">Schedule</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-for="r in RESOURCES" :key="r" class="flex items-center gap-2">
                    <label class="text-sm font-medium w-32 capitalize">{{ r.replace('_', ' ') }}</label>
                    <InputText v-model="intervals[r]" placeholder="e.g. 5m" class="flex-1" />
                    <Button icon="pi pi-play" size="small" outlined @click="runNow(r)" aria-label="Run now" />
                </div>
            </div>
        </div>

        <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-5">
            <h3 class="font-medium mb-3">Run history</h3>
            <DataTable :value="system.syncJobs" stripedRows dataKey="id">
                <Column field="resource" header="Resource" />
                <Column field="mode" header="Mode" />
                <Column field="status" header="Status" />
                <Column field="started_at" header="Started" />
                <Column field="duration_ms" header="Duration" />
                <Column field="items_upserted" header="Upserted" />
                <Column field="items_failed" header="Failed" />
                <Column field="error" header="Error" />
            </DataTable>
        </div>

        <ConfirmModal
            v-model="showResyncModal"
            title="Full resync?"
            message="This will re-sync every resource from Freshdesk. It can take a while and will consume rate-limit budget."
            confirmLabel="Run resync"
            confirmSeverity="danger"
            requireTyped="RESYNC"
            @confirm="fullResync"
        />
    </div>
</template>
