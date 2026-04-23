<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import FilterBar from '@/components/shared/FilterBar.vue';
import BulkActionsBar from '@/components/shared/BulkActionsBar.vue';
import StatusPill from '@/components/shared/StatusPill.vue';
import PriorityIcon from '@/components/shared/PriorityIcon.vue';
import AgentAvatar from '@/components/shared/AgentAvatar.vue';
import ScopePill from '@/components/shared/ScopePill.vue';
import AssignQueueWizard from '@/components/shared/AssignQueueWizard.vue';
import { useTickets } from '@/stores/tickets';
import { useUi } from '@/stores/ui';
import { useAuth } from '@/stores/auth';
import { useRouter } from 'vue-router';

const tickets = useTickets();
const ui = useUi();
const auth = useAuth();
const router = useRouter();

const filters = ref({});
const selected = ref([]);
const assignWizardOpen = ref(false);

const filterSchema = [
    { key: 'status',     label: 'Status',   type: 'multi', options: [
        { label: 'Open', value: 2 }, { label: 'Pending', value: 3 }, { label: 'Resolved', value: 4 }, { label: 'Closed', value: 5 },
    ] },
    { key: 'priority',   label: 'Priority', type: 'multi', options: [
        { label: 'Low', value: 1 }, { label: 'Medium', value: 2 }, { label: 'High', value: 3 }, { label: 'Urgent', value: 4 },
    ] },
    { key: 'search',     label: 'Search',   placeholder: 'subject, description, requester…' },
];

async function apply(f) {
    await tickets.fetch(f);
}

async function bulkDelete() {
    if (!selected.value.length) return;
    await tickets.bulkDelete(selected.value.map((t) => t.id));
    selected.value = [];
    ui.pushToast({ severity: 'success', summary: 'Tickets deleted.' });
}

onMounted(() => tickets.fetch());
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Tickets</h1>
            <div class="flex gap-2">
                <Button v-if="auth.can('tickets.assign')" label="Assign queue" icon="pi pi-sparkles" outlined @click="assignWizardOpen = true" />
                <Button v-if="auth.can('tickets.outbound_email')" label="Outbound email" icon="pi pi-envelope" outlined />
                <Button label="Refresh" icon="pi pi-refresh" outlined @click="tickets.refresh()" />
                <Button v-if="auth.can('tickets.create')" label="+ New ticket" icon="pi pi-plus" @click="router.push('/tickets/new')" />
            </div>
        </div>
        <ScopePill />

        <FilterBar :schema="filterSchema" v-model="filters" @apply="apply" @clear="apply({})" />

        <DataTable
            :value="tickets.items"
            v-model:selection="selected"
            dataKey="id"
            :loading="tickets.loading"
            :rowHover="true"
            stripedRows
            @row-click="(e) => router.push(`/tickets/${e.data.id}`)"
        >
            <Column selectionMode="multiple" headerStyle="width: 3rem" />
            <Column field="display_id" header="#" style="width: 5rem" />
            <Column field="subject" header="Subject" />
            <Column header="Requester">
                <template #body="{ data }">
                    {{ data.requester?.name || '—' }}
                </template>
            </Column>
            <Column header="Agent">
                <template #body="{ data }">
                    <AgentAvatar :agent="data.responder" showName size="small" />
                </template>
            </Column>
            <Column header="Status">
                <template #body="{ data }">
                    <StatusPill :status="data.status" />
                </template>
            </Column>
            <Column header="Priority">
                <template #body="{ data }">
                    <PriorityIcon :priority="data.priority" />
                </template>
            </Column>
            <Column field="updated_at" header="Updated" style="width: 10rem" />
        </DataTable>

        <div v-if="tickets.hasMore" class="flex justify-center">
            <Button label="Load more" outlined @click="tickets.fetchNextPage()" :loading="tickets.loading" />
        </div>

        <BulkActionsBar :count="selected.length" @clear="selected = []">
            <Button v-if="auth.can('tickets.bulk_update')" label="Change status" severity="secondary" outlined size="small" />
            <Button v-if="auth.can('tickets.assign')" label="Assign" severity="secondary" outlined size="small" />
            <Button v-if="auth.can('tickets.bulk_delete') || auth.can('tickets.delete')" label="Delete" severity="danger" outlined size="small" @click="bulkDelete" />
        </BulkActionsBar>

        <AssignQueueWizard v-model="assignWizardOpen" @assigned="tickets.refresh()" />
    </div>
</template>
