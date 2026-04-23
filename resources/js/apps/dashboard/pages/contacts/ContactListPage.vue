<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import FilterBar from '@/components/shared/FilterBar.vue';
import BulkActionsBar from '@/components/shared/BulkActionsBar.vue';
import ScopePill from '@/components/shared/ScopePill.vue';
import { useContacts } from '@/stores/contacts';
import { useUi } from '@/stores/ui';
import { useAuth } from '@/stores/auth';

const contacts = useContacts();
const ui = useUi();
const auth = useAuth();
const router = useRouter();

const filters = ref({});
const selected = ref([]);
const filterSchema = [
    { key: 'state',   label: 'State',   type: 'select', options: [
        { label: 'Verified', value: 'verified' }, { label: 'Unverified', value: 'unverified' }, { label: 'Blocked', value: 'blocked' }, { label: 'Deleted', value: 'deleted' },
    ] },
    { key: 'search',  label: 'Search', placeholder: 'Name, email, phone…' },
];

async function exportCsv() {
    const url = await contacts.exportCsv(filters.value);
    if (url) window.location.assign(url);
}

onMounted(() => contacts.fetch());
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Contacts</h1>
            <div class="flex gap-2">
                <Button v-if="auth.can('contacts.export')" label="Export" icon="pi pi-download" outlined @click="exportCsv" />
                <Button v-if="auth.can('contacts.import')" label="Import" icon="pi pi-upload" outlined />
                <Button v-if="auth.can('contacts.create')" label="+ New contact" icon="pi pi-plus" @click="router.push('/contacts/new')" />
            </div>
        </div>
        <ScopePill />

        <FilterBar :schema="filterSchema" v-model="filters" @apply="(f) => contacts.fetch(f)" @clear="contacts.fetch({})" />

        <DataTable
            :value="contacts.items"
            v-model:selection="selected"
            dataKey="id"
            :loading="contacts.loading"
            stripedRows
            @row-click="(e) => router.push(`/contacts/${e.data.id}`)"
        >
            <Column selectionMode="multiple" headerStyle="width: 3rem" />
            <Column field="name" header="Name" />
            <Column field="email" header="Email" />
            <Column field="phone" header="Phone" />
            <Column header="Company">
                <template #body="{ data }">{{ data.company?.name || '—' }}</template>
            </Column>
            <Column field="updated_at" header="Updated" />
        </DataTable>

        <BulkActionsBar :count="selected.length" @clear="selected = []">
            <Button label="Add tag" severity="secondary" outlined size="small" />
            <Button label="Send invite" severity="secondary" outlined size="small" />
            <Button label="Delete" severity="danger" outlined size="small" />
        </BulkActionsBar>
    </div>
</template>
