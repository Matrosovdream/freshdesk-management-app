<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import RichEditor from '@/components/shared/RichEditor.vue';
import { useAgents } from '@/stores/agents';
import { useAuth } from '@/stores/auth';
import { useUi } from '@/stores/ui';
import { formatDate } from '@shared/datetime';

const props = defineProps({ id: { type: [String, Number], required: true } });
const agents = useAgents();
const auth = useAuth();
const ui = useUi();
const router = useRouter();
const agent = ref(null);
const tab = ref('profile');

const editOpen = ref(false);
const saving = ref(false);
const deleting = ref(false);

const form = reactive({
    name: '', email: '', job_title: '', type: '',
    ticket_scope: 1, available: false, occasional: false,
    language: '', time_zone: '', signature: '',
});
const errors = reactive({});

const typeOptions = [
    { label: 'Support agent', value: 'support_agent' },
    { label: 'Field agent',   value: 'field_agent' },
    { label: 'Collaborator',  value: 'collaborator' },
];
const scopeOptions = [
    { label: 'Global',     value: 1 },
    { label: 'Group',      value: 2 },
    { label: 'Restricted', value: 3 },
];

const scopeLabel = (v) => scopeOptions.find((o) => o.value === v)?.label ?? '—';
const typeLabel  = (v) => typeOptions.find((o) => o.value === v)?.label  ?? (v || '—');

async function load() {
    agent.value = await agents.show(props.id);
}
onMounted(load);
watch(() => props.id, load);

function openEdit() {
    if (!agent.value) return;
    Object.keys(errors).forEach((k) => delete errors[k]);
    form.name         = agent.value.name         ?? '';
    form.email        = agent.value.email        ?? '';
    form.job_title    = agent.value.job_title    ?? '';
    form.type         = agent.value.type         ?? '';
    form.ticket_scope = agent.value.ticket_scope ?? 1;
    form.available    = !!agent.value.available;
    form.occasional   = !!agent.value.occasional;
    form.language     = agent.value.language     ?? '';
    form.time_zone    = agent.value.time_zone    ?? '';
    form.signature    = agent.value.signature    ?? '';
    editOpen.value = true;
}

async function save() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    saving.value = true;
    try {
        const patch = {
            name:         form.name || null,
            email:        form.email,
            job_title:    form.job_title || null,
            type:         form.type || null,
            ticket_scope: form.ticket_scope,
            available:    form.available,
            occasional:   form.occasional,
            language:     form.language || null,
            time_zone:    form.time_zone || null,
            signature:    form.signature || null,
        };
        const updated = await agents.update(agent.value.id, patch);
        agent.value = updated ?? agent.value;
        editOpen.value = false;
        ui.pushToast({ severity: 'success', summary: 'Agent updated.' });
    } catch (e) {
        if (e.validation) Object.assign(errors, e.validation);
        ui.pushToast({ severity: 'error', summary: 'Update failed.' });
    } finally {
        saving.value = false;
    }
}

async function destroy() {
    if (!agent.value) return;
    const label = agent.value.name || agent.value.email;
    if (!confirm(`Delete ${label}? This cannot be undone.`)) return;
    deleting.value = true;
    try {
        await agents.destroy(agent.value.id);
        ui.pushToast({ severity: 'success', summary: 'Agent deleted.' });
        router.push('/agents');
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Delete failed.' });
    } finally {
        deleting.value = false;
    }
}

const groupIds = computed(() => Array.isArray(agent.value?.group_ids) ? agent.value.group_ids : []);
const roleIds  = computed(() => Array.isArray(agent.value?.role_ids)  ? agent.value.role_ids  : []);
const skillIds = computed(() => Array.isArray(agent.value?.skill_ids) ? agent.value.skill_ids : []);

const identityRows = computed(() => agent.value ? [
    { label: 'Name',         value: agent.value.name },
    { label: 'Email',        value: agent.value.email },
    { label: 'Job title',    value: agent.value.job_title },
    { label: 'Type',         value: typeLabel(agent.value.type) },
    { label: 'Freshdesk ID', value: agent.value.freshdesk_id },
] : []);

const accessRows = computed(() => agent.value ? [
    { label: 'Ticket scope', value: scopeLabel(agent.value.ticket_scope) },
    { label: 'Available',    value: agent.value.available ? 'Yes' : 'No', tag: true, severity: agent.value.available ? 'success' : 'secondary' },
    { label: 'Occasional',   value: agent.value.occasional ? 'Yes' : 'No' },
    { label: 'Language',     value: agent.value.language },
    { label: 'Time zone',    value: agent.value.time_zone },
] : []);

const metaRows = computed(() => agent.value ? [
    { label: 'Last login',          value: formatDate(agent.value.last_login_at) },
    { label: 'Created (Freshdesk)', value: formatDate(agent.value.fd_created_at) },
    { label: 'Updated (Freshdesk)', value: formatDate(agent.value.fd_updated_at) },
    { label: 'Last synced',         value: formatDate(agent.value.synced_at) },
    { label: 'Created locally',     value: formatDate(agent.value.created_at) },
] : []);
</script>

<template>
    <div v-if="agent" class="flex flex-col gap-5">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <img v-if="agent.avatar_url" :src="agent.avatar_url" alt="" class="w-12 h-12 rounded-full" />
                <div>
                    <h1 class="text-2xl font-semibold flex items-center gap-2">
                        {{ agent.name || agent.email }}
                        <Tag v-if="agent.type" :value="typeLabel(agent.type)" severity="info" />
                        <Tag v-if="agent.available" value="Available" severity="success" />
                    </h1>
                    <p v-if="agent.job_title" class="text-sm text-surface-500 mt-1">{{ agent.job_title }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button v-if="auth.can('agents.update')" label="Edit" icon="pi pi-pencil" outlined @click="openEdit" />
                <Button v-if="auth.can('agents.delete')" label="Delete" severity="danger" outlined :loading="deleting" @click="destroy" />
            </div>
        </div>

        <!-- Edit dialog -->
        <Dialog v-model:visible="editOpen" modal header="Edit agent" :style="{ width: '40rem' }">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Name</label>
                        <InputText v-model="form.name" class="w-full" />
                        <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name[0] || errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Email</label>
                        <InputText v-model="form.email" type="email" class="w-full" required />
                        <p v-if="errors.email" class="text-xs text-red-500">{{ errors.email[0] || errors.email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Job title</label>
                        <InputText v-model="form.job_title" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Type</label>
                        <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" showClear />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Ticket scope</label>
                        <Select v-model="form.ticket_scope" :options="scopeOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Language</label>
                        <InputText v-model="form.language" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Time zone</label>
                        <InputText v-model="form.time_zone" class="w-full" />
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <ToggleSwitch v-model="form.available" />
                        Available
                    </label>
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <ToggleSwitch v-model="form.occasional" />
                        Occasional
                    </label>
                </div>
                <div>
                    <label class="text-sm font-medium">Signature</label>
                    <RichEditor v-model="form.signature" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="editOpen = false" />
                <Button label="Save" :loading="saving" @click="save" />
            </template>
        </Dialog>

        <Tabs v-model:value="tab">
            <TabList>
                <Tab value="profile">Profile</Tab>
                <Tab value="tickets">Assigned tickets</Tab>
                <Tab value="time">Time entries</Tab>
                <Tab value="perf">Performance</Tab>
            </TabList>
            <TabPanels>
                <TabPanel value="profile">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-3">
                        <!-- Identity -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Identity</h3>
                            <dl class="grid grid-cols-[140px_1fr] gap-y-2 gap-x-3 text-sm">
                                <template v-for="row in identityRows" :key="row.label">
                                    <dt class="text-surface-500">{{ row.label }}</dt>
                                    <dd class="text-surface-900 dark:text-surface-0">{{ row.value || '—' }}</dd>
                                </template>
                            </dl>
                        </section>

                        <!-- Access & status -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Access &amp; status</h3>
                            <dl class="grid grid-cols-[140px_1fr] gap-y-2 gap-x-3 text-sm">
                                <template v-for="row in accessRows" :key="row.label">
                                    <dt class="text-surface-500">{{ row.label }}</dt>
                                    <dd class="text-surface-900 dark:text-surface-0">
                                        <Tag v-if="row.tag" :value="row.value" :severity="row.severity" />
                                        <span v-else>{{ row.value || '—' }}</span>
                                    </dd>
                                </template>
                            </dl>
                        </section>

                        <!-- Assignments -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Groups</h3>
                            <div v-if="groupIds.length" class="flex flex-wrap gap-2">
                                <Tag v-for="g in groupIds" :key="g" :value="`#${g}`" />
                            </div>
                            <p v-else class="text-sm text-surface-500">No groups assigned.</p>
                        </section>

                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Roles &amp; skills</h3>
                            <div class="text-sm">
                                <p class="text-surface-500 mb-1">Roles</p>
                                <div v-if="roleIds.length" class="flex flex-wrap gap-2 mb-3">
                                    <Tag v-for="r in roleIds" :key="r" :value="`#${r}`" />
                                </div>
                                <p v-else class="text-surface-500 mb-3">No roles.</p>
                                <p class="text-surface-500 mb-1">Skills</p>
                                <div v-if="skillIds.length" class="flex flex-wrap gap-2">
                                    <Tag v-for="s in skillIds" :key="s" :value="`#${s}`" />
                                </div>
                                <p v-else class="text-surface-500">No skills.</p>
                            </div>
                        </section>

                        <!-- Signature -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4 lg:col-span-2">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Signature</h3>
                            <div v-if="agent.signature" class="text-sm text-surface-900 dark:text-surface-0 prose prose-sm max-w-none" v-html="agent.signature" />
                            <p v-else class="text-sm text-surface-500">No signature.</p>
                        </section>

                        <!-- Metadata -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4 lg:col-span-2">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Metadata</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-6 text-sm">
                                <template v-for="row in metaRows" :key="row.label">
                                    <div class="grid grid-cols-[140px_1fr] gap-x-3">
                                        <dt class="text-surface-500">{{ row.label }}</dt>
                                        <dd class="text-surface-900 dark:text-surface-0">{{ row.value }}</dd>
                                    </div>
                                </template>
                            </dl>
                        </section>
                    </div>
                </TabPanel>
                <TabPanel value="tickets"><p class="text-surface-500 text-sm py-6 text-center">Assigned tickets table.</p></TabPanel>
                <TabPanel value="time"><p class="text-surface-500 text-sm py-6 text-center">Time entries table.</p></TabPanel>
                <TabPanel value="perf"><p class="text-surface-500 text-sm py-6 text-center">30d performance: resolved, avg FRT, avg resolution, CSAT.</p></TabPanel>
            </TabPanels>
        </Tabs>
    </div>
    <div v-else class="py-20 text-center text-surface-500">Loading agent…</div>
</template>
