<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import FilterBar from '@/components/shared/FilterBar.vue';
import ScopePill from '@/components/shared/ScopePill.vue';
import { useCompanies } from '@/stores/companies';
import { useAuth } from '@/stores/auth';

const companies = useCompanies();
const auth = useAuth();
const router = useRouter();
const filters = ref({});
const filterSchema = [
    { key: 'industry', label: 'Industry' },
    { key: 'search', label: 'Search', placeholder: 'Name or domain' },
];

async function exportCsv() {
    const url = await companies.exportCsv(filters.value);
    if (url) window.location.assign(url);
}

onMounted(() => companies.fetch());
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Companies</h1>
            <div class="flex gap-2">
                <Button v-if="auth.can('companies.export')" label="Export" icon="pi pi-download" outlined @click="exportCsv" />
                <Button v-if="auth.can('companies.import')" label="Import" icon="pi pi-upload" outlined />
                <Button v-if="auth.can('companies.create')" label="+ New" icon="pi pi-plus" @click="router.push('/companies/new')" />
            </div>
        </div>
        <ScopePill />

        <FilterBar :schema="filterSchema" v-model="filters" @apply="(f) => companies.fetch(f)" @clear="companies.fetch({})" />

        <DataTable :value="companies.items" :loading="companies.loading" stripedRows dataKey="id"
            @row-click="(e) => router.push(`/companies/${e.data.id}`)" rowHover>
            <Column field="name" header="Name" />
            <Column header="Domains">
                <template #body="{ data }">{{ (data.domains || []).join(', ') }}</template>
            </Column>
            <Column field="industry" header="Industry" />
            <Column field="account_tier" header="Tier" />
            <Column field="health_score" header="Health" />
            <Column field="open_tickets_count" header="Open tickets" />
        </DataTable>
    </div>
</template>
