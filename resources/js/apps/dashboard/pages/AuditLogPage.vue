<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Drawer from 'primevue/drawer';
import FilterBar from '@/components/shared/FilterBar.vue';
import { useAudit } from '@/stores/audit';

const audit = useAudit();
const filters = ref({});
const selected = ref(null);
const drawerOpen = ref(false);

const filterSchema = [
    { key: 'search',      label: 'Search',      placeholder: 'user, action, target…' },
    { key: 'action_type', label: 'Action',      placeholder: 'created, updated, …' },
    { key: 'target_type', label: 'Target type', placeholder: 'Ticket, Contact…' },
];

onMounted(() => audit.fetch());

function openRow(row) {
    selected.value = row;
    drawerOpen.value = true;
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Audit log</h1>
            <Button label="Export CSV" icon="pi pi-download" outlined />
        </div>
        <FilterBar :schema="filterSchema" v-model="filters" @apply="(f) => audit.fetch(f)" @clear="audit.fetch({})" />

        <DataTable :value="audit.items" :loading="audit.loading" stripedRows dataKey="id" rowHover @row-click="(e) => openRow(e.data)">
            <Column field="created_at" header="When" style="width: 12rem" />
            <Column header="Who">
                <template #body="{ data }">{{ data.user?.name || data.user?.email || 'system' }}</template>
            </Column>
            <Column field="action" header="Action" />
            <Column field="target_type" header="Target" />
            <Column field="source" header="Source" />
            <Column field="summary" header="Summary" />
        </DataTable>

        <div v-if="audit.hasMore" class="flex justify-center">
            <Button label="Load more" outlined @click="audit.fetchNextPage()" :loading="audit.loading" />
        </div>

        <Drawer v-model:visible="drawerOpen" position="right" :style="{ width: '30rem' }" header="Audit entry">
            <div v-if="selected" class="flex flex-col gap-3 text-sm">
                <div><b>When:</b> {{ selected.created_at }}</div>
                <div><b>Who:</b> {{ selected.user?.name || 'system' }}</div>
                <div><b>Action:</b> {{ selected.action }}</div>
                <div><b>Target:</b> {{ selected.target_type }} #{{ selected.target_id }}</div>
                <div>
                    <b>Before:</b>
                    <pre class="text-xs bg-surface-100 dark:bg-surface-800 p-2 rounded overflow-auto mt-1">{{ selected.payload_before }}</pre>
                </div>
                <div>
                    <b>After:</b>
                    <pre class="text-xs bg-surface-100 dark:bg-surface-800 p-2 rounded overflow-auto mt-1">{{ selected.payload_after }}</pre>
                </div>
            </div>
        </Drawer>
    </div>
</template>
