<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
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
import Checkbox from 'primevue/checkbox';
import TagInput from '@/components/shared/TagInput.vue';
import AgentAvatar from '@/components/shared/AgentAvatar.vue';
import { useContacts } from '@/stores/contacts';
import { useUi } from '@/stores/ui';
import { useAuth } from '@/stores/auth';
import { formatDate } from '@shared/datetime';

const props = defineProps({ id: { type: [String, Number], required: true } });
const contacts = useContacts();
const ui = useUi();
const auth = useAuth();

const contact = ref(null);
const tab = ref('profile');

const editOpen = ref(false);
const saving = ref(false);
const form = reactive({
    name: '', email: '', phone: '', mobile: '', twitter_id: '', unique_external_id: '',
    job_title: '', language: '', time_zone: '', address: '', tags: [], active: false, blocked: false,
});
const errors = reactive({});

async function load() {
    contact.value = await contacts.show(props.id);
}
onMounted(load);
watch(() => props.id, load);

function openEdit() {
    if (!contact.value) return;
    Object.keys(errors).forEach((k) => delete errors[k]);
    form.name               = contact.value.name               ?? '';
    form.email              = contact.value.email              ?? '';
    form.phone              = contact.value.phone              ?? '';
    form.mobile             = contact.value.mobile             ?? '';
    form.twitter_id         = contact.value.twitter_id         ?? '';
    form.unique_external_id = contact.value.unique_external_id ?? '';
    form.job_title          = contact.value.job_title          ?? '';
    form.language           = contact.value.language           ?? '';
    form.time_zone          = contact.value.time_zone          ?? '';
    form.address            = contact.value.address            ?? '';
    form.tags               = Array.isArray(contact.value.tags) ? [...contact.value.tags] : [];
    form.active             = !!contact.value.active;
    form.blocked            = !!contact.value.blocked_at;
    editOpen.value = true;
}

async function save() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    saving.value = true;
    try {
        const patch = {
            name:               form.name,
            email:              form.email || null,
            phone:              form.phone || null,
            mobile:             form.mobile || null,
            twitter_id:         form.twitter_id || null,
            unique_external_id: form.unique_external_id || null,
            job_title:          form.job_title || null,
            language:           form.language || null,
            time_zone:          form.time_zone || null,
            address:            form.address || null,
            tags:               Array.isArray(form.tags) ? form.tags : [],
            active:             !!form.active,
            blocked:            !!form.blocked,
        };
        const updated = await contacts.update(contact.value.id, patch);
        contact.value = updated ?? contact.value;
        editOpen.value = false;
        ui.pushToast({ severity: 'success', summary: 'Contact updated.' });
    } catch (e) {
        if (e.validation) Object.assign(errors, e.validation);
        ui.pushToast({ severity: 'error', summary: 'Update failed.' });
    } finally {
        saving.value = false;
    }
}

async function sendInvite() {
    try {
        await contacts.sendInvite(contact.value.id);
        ui.pushToast({ severity: 'success', summary: 'Invite sent.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Send invite failed.' });
    }
}


const tags = computed(() => Array.isArray(contact.value?.tags) ? contact.value.tags : []);
const otherEmails = computed(() => Array.isArray(contact.value?.other_emails) ? contact.value.other_emails : []);
const customFields = computed(() => {
    const cf = contact.value?.custom_fields;
    if (!cf || typeof cf !== 'object') return [];
    return Object.entries(cf);
});

const identityRows = computed(() => contact.value ? [
    { label: 'Name',        value: contact.value.name },
    { label: 'Job title',   value: contact.value.job_title },
    { label: 'External ID', value: contact.value.unique_external_id },
    { label: 'Freshdesk ID', value: contact.value.freshdesk_id },
    { label: 'Language',    value: contact.value.language },
    { label: 'Time zone',   value: contact.value.time_zone },
] : []);

const contactRows = computed(() => contact.value ? [
    { label: 'Email',   value: contact.value.email, href: contact.value.email ? `mailto:${contact.value.email}` : null },
    { label: 'Phone',   value: contact.value.phone, href: contact.value.phone ? `tel:${contact.value.phone}` : null },
    { label: 'Mobile',  value: contact.value.mobile, href: contact.value.mobile ? `tel:${contact.value.mobile}` : null },
    { label: 'Twitter', value: contact.value.twitter_id, href: contact.value.twitter_id ? `https://twitter.com/${contact.value.twitter_id}` : null },
    { label: 'Address', value: contact.value.address },
] : []);

const companyRows = computed(() => {
    const c = contact.value?.company;
    return c ? [
        { label: 'Name',         value: c.name },
        { label: 'Industry',     value: c.industry },
        { label: 'Account tier', value: c.account_tier },
        { label: 'Health',       value: c.health_score },
        { label: 'Renewal',      value: c.renewal_date ? formatDate(c.renewal_date) : '—' },
    ] : [];
});

const metaRows = computed(() => contact.value ? [
    { label: 'Created (Freshdesk)', value: formatDate(contact.value.fd_created_at) },
    { label: 'Updated (Freshdesk)', value: formatDate(contact.value.fd_updated_at) },
    { label: 'Last synced',         value: formatDate(contact.value.synced_at) },
    { label: 'Created locally',     value: formatDate(contact.value.created_at) },
] : []);
</script>

<template>
    <div v-if="contact" class="flex flex-col gap-5">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <AgentAvatar :agent="contact" size="xlarge" />
                <div>
                    <h1 class="text-2xl font-semibold flex items-center gap-2">
                        {{ contact.name }}
                        <Tag v-if="contact.deleted_at" value="Deleted" severity="danger" />
                        <Tag v-else-if="contact.blocked_at" value="Blocked" severity="danger" />
                        <Tag v-else-if="contact.active" value="Verified" severity="success" />
                        <Tag v-else value="Unverified" severity="warn" />
                        <Tag v-if="contact.view_all_tickets" value="Company view" severity="info" />
                    </h1>
                    <p class="text-sm text-surface-500">
                        {{ contact.email || '—' }}
                        <span v-if="contact.phone"> · {{ contact.phone }}</span>
                        <span v-if="contact.company?.name"> · {{ contact.company.name }}</span>
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button v-if="auth.can('contacts.update')" label="Edit" icon="pi pi-pencil" outlined @click="openEdit" />
                <Button label="Send invite" icon="pi pi-send" outlined @click="sendInvite" />
                <Button label="Make agent" icon="pi pi-user-plus" outlined />
                <Button label="Merge" icon="pi pi-link" outlined />
                <Button label="Delete" severity="danger" outlined />
            </div>
        </div>

        <Dialog v-model:visible="editOpen" modal header="Edit contact" :style="{ width: '40rem' }">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="text-sm font-medium">Name</label>
                    <InputText v-model="form.name" class="w-full" required />
                    <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name[0] || errors.name }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Email</label>
                        <InputText v-model="form.email" type="email" class="w-full" />
                        <p v-if="errors.email" class="text-xs text-red-500">{{ errors.email[0] || errors.email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Phone</label>
                        <InputText v-model="form.phone" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Mobile</label>
                        <InputText v-model="form.mobile" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Job title</label>
                        <InputText v-model="form.job_title" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Twitter</label>
                        <InputText v-model="form.twitter_id" class="w-full" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">External ID</label>
                        <InputText v-model="form.unique_external_id" class="w-full" />
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
                <div>
                    <label class="text-sm font-medium">Address</label>
                    <Textarea v-model="form.address" rows="2" class="w-full" />
                </div>
                <div>
                    <label class="text-sm font-medium">Tags</label>
                    <TagInput v-model="form.tags" />
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.active" inputId="contact-active" binary />
                        <label for="contact-active" class="text-sm">Verified</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.blocked" inputId="contact-blocked" binary />
                        <label for="contact-blocked" class="text-sm">Blocked</label>
                    </div>
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
                <Tab value="tickets">Tickets</Tab>
                <Tab value="activity">Activity</Tab>
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

                        <!-- Contact info -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Contact info</h3>
                            <dl class="grid grid-cols-[140px_1fr] gap-y-2 gap-x-3 text-sm">
                                <template v-for="row in contactRows" :key="row.label">
                                    <dt class="text-surface-500">{{ row.label }}</dt>
                                    <dd class="text-surface-900 dark:text-surface-0">
                                        <a v-if="row.href && row.value" :href="row.href" class="text-primary hover:underline">{{ row.value }}</a>
                                        <span v-else>{{ row.value ?? '—' }}</span>
                                    </dd>
                                </template>
                                <dt v-if="otherEmails.length" class="text-surface-500">Other emails</dt>
                                <dd v-if="otherEmails.length" class="text-surface-900 dark:text-surface-0">
                                    <div class="flex flex-col gap-1">
                                        <a v-for="e in otherEmails" :key="e" :href="`mailto:${e}`" class="text-primary hover:underline">{{ e }}</a>
                                    </div>
                                </dd>
                            </dl>
                        </section>

                        <!-- Company -->
                        <section v-if="contact.company" class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Company</h3>
                            <dl class="grid grid-cols-[140px_1fr] gap-y-2 gap-x-3 text-sm">
                                <template v-for="row in companyRows" :key="row.label">
                                    <dt class="text-surface-500">{{ row.label }}</dt>
                                    <dd class="text-surface-900 dark:text-surface-0">{{ row.value ?? '—' }}</dd>
                                </template>
                            </dl>
                            <router-link
                                :to="`/companies/${contact.company.id}`"
                                class="text-xs text-primary mt-3 inline-block"
                            >
                                View company →
                            </router-link>
                        </section>
                        <section v-else class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-1 text-surface-700 dark:text-surface-300">Company</h3>
                            <p class="text-sm text-surface-500">Not linked to a company.</p>
                        </section>

                        <!-- Tags + custom fields -->
                        <section class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                            <h3 class="font-medium text-sm mb-3 text-surface-700 dark:text-surface-300">Tags &amp; custom fields</h3>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <Tag v-for="t in tags" :key="t" :value="t" />
                                <span v-if="!tags.length" class="text-sm text-surface-500">No tags.</span>
                            </div>
                            <dl v-if="customFields.length" class="grid grid-cols-[140px_1fr] gap-y-2 gap-x-3 text-sm">
                                <template v-for="[k, v] in customFields" :key="k">
                                    <dt class="text-surface-500">{{ k }}</dt>
                                    <dd class="text-surface-900 dark:text-surface-0">{{ v ?? '—' }}</dd>
                                </template>
                            </dl>
                            <p v-else class="text-sm text-surface-500">No custom fields.</p>
                        </section>

                        <!-- Meta -->
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
                <TabPanel value="tickets">
                    <p class="text-surface-500 text-sm py-6 text-center">Tickets filtered by requester_id={{ contact.id }}.</p>
                </TabPanel>
                <TabPanel value="activity">
                    <p class="text-surface-500 text-sm py-6 text-center">Activity log — local audit entries.</p>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
    <div v-else class="py-20 text-center text-surface-500">Loading contact…</div>
</template>
