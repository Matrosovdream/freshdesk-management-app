<script setup>
import { onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { useGroups } from '@/stores/groups';
import { useUi } from '@/stores/ui';

const groups = useGroups();
const ui = useUi();

const showModal = ref(false);
const editing = ref(null);
const form = reactive({ name: '', description: '', unassigned_for: '30m' });

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
            <Column field="unassigned_for" header="Unassigned for" />
            <Column header="Actions" style="width: 10rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="groups.destroy(data.id)" />
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
                    <InputText v-model="form.unassigned_for" class="w-full" placeholder="30m, 1h, 1d…" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showModal = false" />
                <Button label="Save" @click="submit" />
            </template>
        </Dialog>
    </div>
</template>
