<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import FilterBar from '@/components/shared/FilterBar.vue';
import ScopePill from '@/components/shared/ScopePill.vue';
import { useAgents } from '@/stores/agents';
import { useAuth } from '@/stores/auth';

const agents = useAgents();
const auth = useAuth();
const router = useRouter();
const filters = ref({});

const filterSchema = [
    {
        key: 'type',
        label: 'Type',
        type: 'select',
        options: [
            { label: 'Support agent', value: 'support_agent' },
            { label: 'Field agent',   value: 'field_agent' },
            { label: 'Collaborator',  value: 'collaborator' },
        ],
    },
    { key: 'search', label: 'Search', placeholder: 'Name or email' },
];

onMounted(() => agents.fetch());
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Agents</h1>
            <div class="flex gap-2">
                <Button v-if="auth.can('agents.create')" label="+ New agent" icon="pi pi-plus" @click="router.push('/agents/new')" />
            </div>
        </div>
        <ScopePill />

        <FilterBar :schema="filterSchema" v-model="filters" @apply="(f) => agents.fetch(f)" @clear="agents.fetch({})" />

        <DataTable :value="agents.items" :loading="agents.loading" stripedRows dataKey="id"
            @row-click="(e) => router.push(`/agents/${e.data.id}`)" rowHover>
            <Column field="name" header="Name" />
            <Column field="email" header="Email" />
            <Column field="type" header="Type" />
            <Column field="ticket_scope" header="Scope" />
            <Column field="last_login_at" header="Last login">
                <template #body="{ data }">{{ $formatDate(data.last_login_at) }}</template>
            </Column>
        </DataTable>
    </div>
</template>
