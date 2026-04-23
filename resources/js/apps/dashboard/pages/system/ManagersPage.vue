<script setup>
import { onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Drawer from 'primevue/drawer';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import MultiSelect from 'primevue/multiselect';
import { useManagers } from '@/stores/managers';
import { useGroups } from '@/stores/groups';
import { useUi } from '@/stores/ui';

const managers = useManagers();
const groups = useGroups();
const ui = useUi();

const drawerOpen = ref(false);
const editing = ref(null);
const form = reactive({ name: '', email: '', password: '', is_active: true, group_ids: [] });

function openNew() {
    editing.value = null;
    Object.assign(form, { name: '', email: '', password: '', is_active: true, group_ids: [] });
    drawerOpen.value = true;
}

function openEdit(m) {
    editing.value = m;
    Object.assign(form, {
        name: m.name || '',
        email: m.email || '',
        password: '',
        is_active: m.is_active !== false,
        group_ids: (m.assigned_groups || []).map((g) => g.id ?? g),
    });
    drawerOpen.value = true;
}

async function save() {
    try {
        const saved = editing.value
            ? await managers.update(editing.value.id, form)
            : await managers.create(form);
        if (saved?.id) await managers.setScope(saved.id, form.group_ids || []);
        ui.pushToast({ severity: 'success', summary: editing.value ? 'Manager updated.' : 'Manager created.' });
        drawerOpen.value = false;
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Save failed.' });
    }
}

onMounted(() => Promise.allSettled([managers.fetch(), groups.fetch()]));
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Managers</h1>
            <Button label="+ New manager" icon="pi pi-plus" @click="openNew" />
        </div>

        <DataTable :value="managers.items" :loading="managers.loading" stripedRows dataKey="id">
            <Column field="name" header="Name" />
            <Column field="email" header="Email" />
            <Column header="Groups">
                <template #body="{ data }">{{ (data.assigned_groups || []).length }}</template>
            </Column>
            <Column field="last_login_at" header="Last login" />
            <Column header="Active">
                <template #body="{ data }">{{ data.is_active ? 'Yes' : 'No' }}</template>
            </Column>
            <Column header="Actions" style="width: 10rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="managers.destroy(data.id)" />
                </template>
            </Column>
        </DataTable>

        <Drawer v-model:visible="drawerOpen" position="right" :style="{ width: '28rem' }" :header="editing ? 'Edit manager' : 'New manager'">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="text-sm font-medium">Name</label>
                    <InputText v-model="form.name" class="w-full" />
                </div>
                <div>
                    <label class="text-sm font-medium">Email</label>
                    <InputText v-model="form.email" type="email" class="w-full" required />
                </div>
                <div v-if="!editing">
                    <label class="text-sm font-medium">Password</label>
                    <Password v-model="form.password" :toggleMask="true" :feedback="false" fluid />
                </div>
                <div>
                    <label class="text-sm font-medium">Assigned groups</label>
                    <MultiSelect v-model="form.group_ids" :options="groups.items" optionLabel="name" optionValue="id" class="w-full" filter />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox v-model="form.is_active" binary /> Active
                </label>
                <div class="flex justify-end gap-2 mt-2">
                    <Button label="Cancel" severity="secondary" outlined @click="drawerOpen = false" />
                    <Button label="Save" @click="save" />
                </div>
            </div>
        </Drawer>
    </div>
</template>
