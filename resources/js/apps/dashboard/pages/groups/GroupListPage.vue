<script setup>
import { onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import { useGroups } from '@/stores/groups';
import { useUi } from '@/stores/ui';

const groups = useGroups();
const ui = useUi();

const unassignedForOptions = [
    { label: '30 minutes', value: '30m' },
    { label: '1 hour',     value: '1h' },
    { label: '2 hours',    value: '2h' },
    { label: '4 hours',    value: '4h' },
    { label: '8 hours',    value: '8h' },
    { label: '12 hours',   value: '12h' },
    { label: '1 day',      value: '1d' },
    { label: '2 days',     value: '2d' },
    { label: '3 days',     value: '3d' },
];

function unassignedForLabel(v) {
    if (!v) return '—';
    return unassignedForOptions.find((o) => o.value === v)?.label ?? v;
}

const showModal = ref(false);
const editing = ref(null);
const form = reactive({ name: '', description: '', unassigned_for: '30m' });

const confirmOpen = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

function askDelete(g) {
    deleteTarget.value = g;
    confirmOpen.value = true;
}

async function confirmDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await groups.destroy(deleteTarget.value.id);
        ui.pushToast({ severity: 'success', summary: 'Group deleted.' });
        confirmOpen.value = false;
        deleteTarget.value = null;
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Delete failed.' });
    } finally {
        deleting.value = false;
    }
}

function openNew() {
    editing.value = null;
    form.name = ''; form.description = ''; form.unassigned_for = '30m';
    showModal.value = true;
}

function openEdit(g) {
    editing.value = g;
    form.name = g.name || '';
    form.description = g.description || '';
    form.unassigned_for = g.unassigned_for || '30m';
    showModal.value = true;
}

async function submit() {
    try {
        if (editing.value) await groups.update(editing.value.id, form);
        else await groups.create(form);
        showModal.value = false;
        ui.pushToast({ severity: 'success', summary: editing.value ? 'Group updated.' : 'Group created.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Save failed.' });
    }
}

onMounted(() => groups.fetch());
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Groups</h1>
            <Button label="+ New group" icon="pi pi-plus" @click="openNew" />
        </div>
        <DataTable :value="groups.items" :loading="groups.loading" stripedRows dataKey="id">
            <Column field="name" header="Name" />
            <Column field="description" header="Description" />
            <Column field="agent_count" header="Agents" />
            <Column field="unassigned_for" header="Unassigned for">
                <template #body="{ data }">{{ unassignedForLabel(data.unassigned_for) }}</template>
            </Column>
            <Column header="Actions" style="width: 10rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="askDelete(data)" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="showModal" modal :header="editing ? 'Edit group' : 'New group'" :style="{ width: '28rem' }">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="text-sm font-medium">Name</label>
                    <InputText v-model="form.name" class="w-full" required />
                </div>
                <div>
                    <label class="text-sm font-medium">Description</label>
                    <Textarea v-model="form.description" class="w-full" rows="2" />
                </div>
                <div>
                    <label class="text-sm font-medium">Unassigned for</label>
                    <Select v-model="form.unassigned_for" :options="unassignedForOptions"
                        optionLabel="label" optionValue="value" class="w-full" showClear placeholder="Select…" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showModal = false" />
                <Button label="Save" @click="submit" />
            </template>
        </Dialog>

        <Dialog v-model:visible="confirmOpen" modal header="Delete group" :style="{ width: '24rem' }">
            <p class="text-sm">
                Are you sure you want to delete
                <span class="font-semibold">{{ deleteTarget?.name }}</span>?
                This cannot be undone.
            </p>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="confirmOpen = false" :disabled="deleting" />
                <Button label="Delete" severity="danger" :loading="deleting" @click="confirmDelete" />
            </template>
        </Dialog>
    </div>
</template>
