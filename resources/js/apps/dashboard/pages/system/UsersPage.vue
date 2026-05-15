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
import Tag from 'primevue/tag';
import { useUsers } from '@/stores/users';
import { useRoles } from '@/stores/roles';
import { useGroups } from '@/stores/groups';
import { useUi } from '@/stores/ui';

const users = useUsers();
const roles = useRoles();
const groups = useGroups();
const ui = useUi();

const drawerOpen = ref(false);
const editing = ref(null);
const saving = ref(false);
const errors = reactive({});

const blankForm = () => ({
    name: '',
    email: '',
    password: '',
    pin: '',
    phone: '',
    is_active: true,
    role_ids: [],
    group_ids: [],
});

const form = reactive(blankForm());

function clearErrors() {
    for (const k of Object.keys(errors)) delete errors[k];
}

function fieldError(name) {
    const v = errors[name];
    return Array.isArray(v) ? v[0] : v || null;
}

function openNew() {
    editing.value = null;
    clearErrors();
    Object.assign(form, blankForm());
    drawerOpen.value = true;
}

function openEdit(u) {
    editing.value = u;
    clearErrors();
    Object.assign(form, {
        name: u.name || '',
        email: u.email || '',
        password: '',
        pin: '',
        phone: u.phone || '',
        is_active: u.is_active !== false,
        role_ids: (u.roles || []).map((r) => r.id ?? r),
        group_ids: (u.assigned_groups || []).map((g) => g.id ?? g),
    });
    drawerOpen.value = true;
}

async function save() {
    clearErrors();
    saving.value = true;
    try {
        const payload = { ...form };
        if (!payload.pin) delete payload.pin;
        if (!payload.password) delete payload.password;

        if (editing.value) {
            await users.update(editing.value.id, payload);
            ui.pushToast({ severity: 'success', summary: 'User updated.' });
        } else {
            await users.create(payload);
            ui.pushToast({ severity: 'success', summary: 'User created.' });
        }
        drawerOpen.value = false;
    } catch (e) {
        if (e?.validation) {
            Object.assign(errors, e.validation);
            const pinMsg = fieldError('pin');
            ui.pushToast({
                severity: 'error',
                summary: pinMsg || 'Please fix the highlighted fields.',
            });
        } else {
            ui.pushToast({ severity: 'error', summary: 'Save failed.' });
        }
    } finally {
        saving.value = false;
    }
}

async function destroy(id) {
    if (!confirm('Delete this user? This cannot be undone here.')) return;
    try {
        await users.destroy(id);
        ui.pushToast({ severity: 'success', summary: 'User deleted.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Delete failed.' });
    }
}

onMounted(() => Promise.allSettled([users.fetch(), roles.fetch(), groups.fetch()]));
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Users</h1>
            <Button label="+ New user" icon="pi pi-plus" @click="openNew" />
        </div>

        <DataTable :value="users.items" :loading="users.loading" stripedRows dataKey="id">
            <Column field="name" header="Name" />
            <Column field="email" header="Email" />
            <Column header="Roles">
                <template #body="{ data }">
                    <div class="flex flex-wrap gap-1">
                        <Tag v-for="r in (data.roles || [])" :key="r.id ?? r.slug" :value="r.name || r.slug" severity="info" />
                        <span v-if="!(data.roles || []).length" class="text-surface-400 text-xs">—</span>
                    </div>
                </template>
            </Column>
            <Column header="Groups">
                <template #body="{ data }">
                    <div class="flex flex-wrap gap-1">
                        <Tag v-for="g in (data.assigned_groups || [])" :key="g.id ?? g" :value="g.name ?? g" severity="secondary" />
                        <span v-if="!(data.assigned_groups || []).length" class="text-surface-400 text-xs">—</span>
                    </div>
                </template>
            </Column>
            <Column header="PIN">
                <template #body="{ data }">
                    <Tag :value="data.has_pin ? 'Set' : 'Not set'" :severity="data.has_pin ? 'success' : 'secondary'" />
                </template>
            </Column>
            <Column field="last_login_at" header="Last login">
                <template #body="{ data }">{{ $formatDate(data.last_login_at) }}</template>
            </Column>
            <Column header="Active">
                <template #body="{ data }">{{ data.is_active ? 'Yes' : 'No' }}</template>
            </Column>
            <Column header="Actions" style="width: 10rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="destroy(data.id)" />
                </template>
            </Column>
        </DataTable>

        <Drawer v-model:visible="drawerOpen" position="right" :style="{ width: '30rem' }" :header="editing ? 'Edit user' : 'New user'">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="text-sm font-medium">Name</label>
                    <InputText v-model="form.name" class="w-full" :invalid="!!fieldError('name')" />
                    <small v-if="fieldError('name')" class="text-red-500">{{ fieldError('name') }}</small>
                </div>
                <div>
                    <label class="text-sm font-medium">Email</label>
                    <InputText v-model="form.email" type="email" class="w-full" required :invalid="!!fieldError('email')" />
                    <small v-if="fieldError('email')" class="text-red-500">{{ fieldError('email') }}</small>
                </div>
                <div>
                    <label class="text-sm font-medium">Phone</label>
                    <InputText v-model="form.phone" class="w-full" :invalid="!!fieldError('phone')" />
                    <small v-if="fieldError('phone')" class="text-red-500">{{ fieldError('phone') }}</small>
                </div>
                <div>
                    <label class="text-sm font-medium">Password <span v-if="editing" class="text-xs text-surface-400">(leave blank to keep current)</span></label>
                    <Password v-model="form.password" :toggleMask="true" :feedback="false" fluid :invalid="!!fieldError('password')" />
                    <small v-if="fieldError('password')" class="text-red-500">{{ fieldError('password') }}</small>
                </div>
                <div>
                    <label class="text-sm font-medium">PIN (4 digits) <span v-if="editing" class="text-xs text-surface-400">(leave blank to keep current)</span></label>
                    <InputText v-model="form.pin" maxlength="4" inputmode="numeric" pattern="[0-9]*" class="w-full" :invalid="!!fieldError('pin')" />
                    <small v-if="fieldError('pin')" class="text-red-500">{{ fieldError('pin') }}</small>
                </div>
                <div>
                    <label class="text-sm font-medium">Roles</label>
                    <MultiSelect v-model="form.role_ids" :options="roles.items" optionLabel="name" optionValue="id" class="w-full" filter :invalid="!!fieldError('role_ids')" />
                    <small v-if="fieldError('role_ids')" class="text-red-500">{{ fieldError('role_ids') }}</small>
                </div>
                <div>
                    <label class="text-sm font-medium">Assigned groups</label>
                    <MultiSelect v-model="form.group_ids" :options="groups.items" optionLabel="name" optionValue="id" class="w-full" filter />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox v-model="form.is_active" binary /> Active
                </label>
                <div class="flex justify-end gap-2 mt-2">
                    <Button label="Cancel" severity="secondary" outlined @click="drawerOpen = false" :disabled="saving" />
                    <Button label="Save" @click="save" :loading="saving" />
                </div>
            </div>
        </Drawer>
    </div>
</template>
