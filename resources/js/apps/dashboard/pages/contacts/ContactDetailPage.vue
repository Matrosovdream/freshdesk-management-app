<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import AgentAvatar from '@/components/shared/AgentAvatar.vue';
import { useContacts } from '@/stores/contacts';
import { useUi } from '@/stores/ui';

const props = defineProps({ id: { type: [String, Number], required: true } });
const contacts = useContacts();
const ui = useUi();

const contact = ref(null);
const tab = ref('profile');

async function load() {
    contact.value = await contacts.show(props.id);
}
onMounted(load);
watch(() => props.id, load);

async function sendInvite() {
    try {
        await contacts.sendInvite(contact.value.id);
        ui.pushToast({ severity: 'success', summary: 'Invite sent.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Send invite failed.' });
    }
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString();
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
                        <Tag v-if="contact.active" value="Verified" severity="success" />
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
                <Button label="Send invite" icon="pi pi-send" outlined @click="sendInvite" />
                <Button label="Make agent" icon="pi pi-user-plus" outlined />
                <Button label="Merge" icon="pi pi-link" outlined />
                <Button label="Delete" severity="danger" outlined />
            </div>
        </div>

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
