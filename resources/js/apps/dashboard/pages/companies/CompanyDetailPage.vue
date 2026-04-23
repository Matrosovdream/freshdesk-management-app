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
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import TagInput from '@/components/shared/TagInput.vue';
import { useCompanies } from '@/stores/companies';
import { useUi } from '@/stores/ui';

const props = defineProps({ id: { type: [String, Number], required: true } });
const companies = useCompanies();
const ui = useUi();
const router = useRouter();
const company = ref(null);
const tab = ref('profile');

const editOpen = ref(false);
const saving = ref(false);
const deleting = ref(false);
const form = reactive({
    name: '', description: '', note: '',
    industry: '', account_tier: '', health_score: '',
    renewal_date: null, domains: [],
});
const errors = reactive({});

const tierOptions = ['Free', 'Core', 'Growth', 'Enterprise'].map((v) => ({ label: v, value: v }));
const healthOptions = ['Happy', 'Neutral', 'At Risk'].map((v) => ({ label: v, value: v }));

async function load() {
    company.value = await companies.show(props.id);
}
onMounted(load);
watch(() => props.id, load);

function openEdit() {
    if (!company.value) return;
    Object.keys(errors).forEach((k) => delete errors[k]);
    form.name         = company.value.name         ?? '';
    form.description  = company.value.description  ?? '';
    form.note         = company.value.note         ?? '';
    form.industry     = company.value.industry     ?? '';
    form.account_tier = company.value.account_tier ?? '';
    form.health_score = company.value.health_score ?? '';
    form.renewal_date = company.value.renewal_date ? new Date(company.value.renewal_date) : null;
    form.domains      = Array.isArray(company.value.domains) ? [...company.value.domains] : [];
    editOpen.value = true;
}

async function save() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    saving.value = true;
    try {
        const patch = {
            name:         form.name,
            description:  form.description || null,
            note:         form.note || null,
            industry:     form.industry || null,
            account_tier: form.account_tier || null,
            health_score: form.health_score || null,
            renewal_date: form.renewal_date
                ? form.renewal_date.toISOString().slice(0, 10)
                : null,
            domains:      Array.isArray(form.domains) ? form.domains : [],
        };
        const updated = await companies.update(company.value.id, patch);
        company.value = updated ?? company.value;
        editOpen.value = false;
        ui.pushToast({ severity: 'success', summary: 'Company updated.' });
    } catch (e) {
        if (e.validation) Object.assign(errors, e.validation);
        ui.pushToast({ severity: 'error', summary: 'Update failed.' });
    } finally {
        saving.value = false;
    }
}

async function destroy() {
    if (!company.value) return;
    if (!confirm(`Delete ${company.value.name}? This cannot be undone.`)) return;
    deleting.value = true;
    try {
        await companies.destroy(company.value.id);
        ui.pushToast({ severity: 'success', summary: 'Company deleted.' });
        router.push('/companies');
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Delete failed.' });
    } finally {
        deleting.value = false;
    }
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString();
}

function healthSeverity(health) {
    switch ((health || '').toLowerCase()) {
        case 'happy':   return 'success';
        case 'at risk': return 'danger';
        case 'neutral': return 'warn';
        default:        return 'info';
    }
}

const domains = computed(() => Array.isArray(company.value?.domains) ? company.value.domains : []);
const customFields = computed(() => {
    const cf = company.value?.custom_fields;
    if (!cf || typeof cf !== 'object') return [];
    return Object.entries(cf);
});

const identityRows = computed(() => company.value ? [
    { label: 'Name',         value: company.value.name },
    { label: 'Industry',     value: company.value.industry },
    { label: 'Freshdesk ID', value: company.value.freshdesk_id },
] : []);

const accountRows = computed(() => company.value ? [
    { label: 'Tier',          value: company.value.account_tier },
    { label: 'Health',        value: company.value.health_score, tag: true, severity: healthSeverity(company.value.health_score) },
    { label: 'Renewal date',  value: company.value.renewal_date ? formatDate(company.value.renewal_date) : '—' },
    { label: 'Open tickets',  value: company.value.open_tickets_count ?? 0 },
] : []);

const metaRows = computed(() => company.value ? [
    { label: 'Created (Freshdesk)', value: formatDate(company.value.fd_created_at) },
    { label: 'Updated (Freshdesk)', value: formatDate(company.value.fd_updated_at) },
    { label: 'Last synced',         value: formatDate(company.value.synced_at) },
    { label: 'Created locally',     value: formatDate(company.value.created_at) },
] : []);
</script>

<template>
    <div v-if="company" class="flex flex-col gap-5">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-semibold flex items-center gap-2">
                    {{ company.name }}
                    <Tag v-if="company.account_tier" :value="company.account_tier" severity="info" />
                    <Tag v-if="company.health_score" :value="company.health_score" :severity="healthSeverity(company.health_score)" />
                </h1>
                <p v-if="domains.length" class="text-sm text-surface-500 mt-1">
                    <span v-for="(d, i) in domains" :key="d">
                        <a :href="`https://${d}`" target="_blank" class="text-primary hover:underline">{{ d }}</a><span v-if="i < domains.length - 1">, </span>
                    </span>
                </p>
            </div>
            <div class="flex gap-2">
                <Button label="Edit" icon="pi pi-pencil" outlined @click="openEdit" />
                <Button label="Delete" severity="danger" outlined :loading="deleting" @click="destroy" />
            </div>
        </div>

        <!-- Edit dialog -->
        <Dialog v-model:visible="editOpen" modal header="Edit company" :style="{ width: '40rem' }">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="text-sm font-medium">Name</label>
                    <InputText v-model="form.name" class="w-full" required />
                    <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name[0] || errors.name }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Industry</label>
                        <InputText v-model="form.industry" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Tier</label>
                        <Select v-model="form.account_tier" :options="tierOptions" optionLabel="label" optionValue="value" class="w-full" showClear />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Health</label>
                        <Select v-model="form.health_score" :options="healthOptions" optionLabel="label" optionValue="value" class="w-full" showClear />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Renewal date</label>
                        <DatePicker v-model="form.renewal_date" dateFormat="yy-mm-dd" showIcon class="w-full" />
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium">Domains</label>
                    <TagInput v-model="form.domains" placeholder="Add domain…" />
                </div>
                <div>
                    <label class="text-sm font-medium">Description</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" />
                </div>
                <div>
                    <label class="text-sm font-medium">Internal note</label>
                    <Textarea v-model="form.note" rows="2" class="w-full" />
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
                <Tab value="contacts">Contacts</Tab>
                <Tab value="tickets">Tickets</Tab>
                <Tab value="stats">Stats</Tab>
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
                                    <dd class="text-surface-900 dark:text-surface-0">{{ row.value ?? '—' }}</dd>
                                </template>
                            </dl>
                        </section>

                        <!-- Account -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Account</h3>
                            <dl class="grid grid-cols-[140px_1fr] gap-y-2 gap-x-3 text-sm">
                                <template v-for="row in accountRows" :key="row.label">
                                    <dt class="text-surface-500">{{ row.label }}</dt>
                                    <dd class="text-surface-900 dark:text-surface-0">
                                        <Tag v-if="row.tag && row.value" :value="row.value" :severity="row.severity" />
                                        <span v-else>{{ row.value ?? '—' }}</span>
                                    </dd>
                                </template>
                            </dl>
                        </section>

                        <!-- Domains -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Domains</h3>
                            <div v-if="domains.length" class="flex flex-wrap gap-2">
                                <Tag v-for="d in domains" :key="d" :value="d" />
                            </div>
                            <p v-else class="text-sm text-surface-500">No domains configured.</p>
                        </section>

                        <!-- Custom fields -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Custom fields</h3>
                            <dl v-if="customFields.length" class="grid grid-cols-[140px_1fr] gap-y-2 gap-x-3 text-sm">
                                <template v-for="[k, v] in customFields" :key="k">
                                    <dt class="text-surface-500">{{ k }}</dt>
                                    <dd class="text-surface-900 dark:text-surface-0">{{ v ?? '—' }}</dd>
                                </template>
                            </dl>
                            <p v-else class="text-sm text-surface-500">No custom fields.</p>
                        </section>

                        <!-- Description + notes, full width -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4 lg:col-span-2">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Description &amp; notes</h3>
                            <div class="text-sm">
                                <p class="text-surface-500 mb-1">Description</p>
                                <p class="text-surface-900 dark:text-surface-0 mb-4 whitespace-pre-wrap">{{ company.description || '—' }}</p>
                                <p class="text-surface-500 mb-1">Note</p>
                                <p class="text-surface-900 dark:text-surface-0 whitespace-pre-wrap">{{ company.note || '—' }}</p>
                            </div>
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
                <TabPanel value="contacts">
                    <p class="text-surface-500 text-sm py-6 text-center">Contacts filtered by company_id={{ company.id }}.</p>
                </TabPanel>
                <TabPanel value="tickets">
                    <p class="text-surface-500 text-sm py-6 text-center">Tickets filtered by company_id={{ company.id }}.</p>
                </TabPanel>
                <TabPanel value="stats">
                    <p class="text-surface-500 text-sm py-6 text-center">Monthly volume, avg resolution, CSAT trend.</p>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
    <div v-else class="py-20 text-center text-surface-500">Loading company…</div>
</template>
