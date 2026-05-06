<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Select from 'primevue/select';
import StatusPill from '@/components/shared/StatusPill.vue';
import PriorityIcon from '@/components/shared/PriorityIcon.vue';
import AgentAvatar from '@/components/shared/AgentAvatar.vue';
import RichEditor from '@/components/shared/RichEditor.vue';
import AttachmentDropzone from '@/components/shared/AttachmentDropzone.vue';
import AssignPicker from '@/components/shared/AssignPicker.vue';
import TagInput from '@/components/shared/TagInput.vue';
import { useTickets } from '@/stores/tickets';
import { useUi } from '@/stores/ui';
import { useSystem } from '@/stores/system';

const props = defineProps({ id: { type: [String, Number], required: true } });

const tickets = useTickets();
const ui = useUi();
const system = useSystem();
const router = useRouter();

const ticket = ref(null);
const conversations = ref([]);
const activity = ref([]);
const activityLoaded = ref(false);
const activeComposer = ref('reply');
const reply = ref({ body: '', attachments: [] });
const note = ref({ body: '', private: true, attachments: [] });
const tab = ref('conversation');

const FIELD_LABELS = {
    subject: 'Subject', description: 'Description', status: 'Status', priority: 'Priority',
    source: 'Source', type: 'Type', requester_id: 'Requester', responder_id: 'Agent',
    group_id: 'Group', company_id: 'Company', product_id: 'Product', spam: 'Spam',
    tags: 'Tags', cc_emails: 'CC', due_by: 'Due', fr_due_by: 'First-response due',
    custom_fields: 'Custom fields',
};
const TRACKED = Object.keys(FIELD_LABELS);

const STATUS_LABELS = { 2: 'Open', 3: 'Pending', 4: 'Resolved', 5: 'Closed' };
const PRIORITY_LABELS = { 1: 'Low', 2: 'Medium', 3: 'High', 4: 'Urgent' };

function formatVal(v) {
    if (v === null || v === undefined || v === '') return '∅';
    if (typeof v === 'boolean') return v ? 'yes' : 'no';
    if (Array.isArray(v)) return v.length ? v.join(', ') : '∅';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
}

function formatField(key, v) {
    if (v === null || v === undefined || v === '') return '∅';
    if (key === 'status')   return STATUS_LABELS[v]   ?? formatVal(v);
    if (key === 'priority') return PRIORITY_LABELS[v] ?? formatVal(v);
    return formatVal(v);
}

function diffEntry(entry) {
    const before = entry.payload_before || {};
    const after = entry.payload_after || {};
    const changes = [];
    for (const key of TRACKED) {
        const a = before[key];
        const b = after[key];
        if (JSON.stringify(a) !== JSON.stringify(b)) {
            changes.push({ key, label: FIELD_LABELS[key], from: formatField(key, a), to: formatField(key, b) });
        }
    }
    return changes;
}

const ACTION_META = {
    'ticket.created':         { title: 'Ticket created',     badge: 'Created' },
    'ticket.updated':         { title: 'Field changed',      badge: 'Updated' },
    'ticket.deleted':         { title: 'Ticket deleted',     badge: 'Deleted' },
    'ticket.restored':        { title: 'Ticket restored',    badge: 'Restored' },
    'ticket.assigned':        { title: 'Agent reassigned',   badge: 'Updated' },
    'ticket.merged':          { title: 'Merged',             badge: 'Updated' },
    'ticket.forwarded':       { title: 'Forwarded',          badge: 'Created' },
    'ticket.outbound_email':  { title: 'Outbound email',     badge: 'Created' },
    'conversation.reply':     { title: 'Reply added',        badge: 'Created' },
    'conversation.note':      { title: 'Note added',         badge: 'Created' },
};

function entryMeta(entry) {
    return ACTION_META[entry.action] || { title: entry.action, badge: 'Updated' };
}

const conversationsById = computed(() => {
    const m = new Map();
    for (const c of conversations.value) m.set(c.id, c);
    return m;
});

function findConversation(entry) {
    const id = entry?.meta?.conversation_id;
    return id ? conversationsById.value.get(id) : null;
}

function badgeClass(badge) {
    return {
        Created:  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        Updated:  'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        Deleted:  'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        Restored: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    }[badge] || 'bg-surface-100 text-surface-700 dark:bg-surface-800 dark:text-surface-300';
}

function formatDate(s) {
    if (!s) return '';
    const d = new Date(s);
    if (isNaN(d)) return s;
    return d.toLocaleString('en-US', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: 'numeric', minute: '2-digit', hour12: true,
    });
}

async function loadActivity() {
    activity.value = await tickets.activity(props.id);
    activityLoaded.value = true;
}

watch(tab, (v) => { if (v === 'activity' && !activityLoaded.value) loadActivity(); });

const statusOptions = [
    { label: 'Open', value: 2 }, { label: 'Pending', value: 3 }, { label: 'Resolved', value: 4 }, { label: 'Closed', value: 5 },
];

async function load() {
    ticket.value = await tickets.show(props.id);
    conversations.value = await tickets.conversations(props.id);
}

watch(() => props.id, load);
onMounted(load);

async function patch(field, value) {
    try {
        ticket.value = await tickets.update(props.id, { [field]: value });
        if (activityLoaded.value) loadActivity();
    } catch {
        ui.pushToast({ severity: 'error', summary: `Could not update ${field}.` });
    }
}

async function sendReply(closeAfter = false) {
    try {
        await tickets.reply(props.id, reply.value);
        reply.value = { body: '', attachments: [] };
        if (closeAfter) await patch('status', 5);
        conversations.value = await tickets.conversations(props.id);
        ui.pushToast({ severity: 'success', summary: closeAfter ? 'Reply sent, ticket closed.' : 'Reply sent.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Reply failed.' });
    }
}

async function saveNote() {
    try {
        await tickets.note(props.id, note.value);
        note.value = { body: '', private: true, attachments: [] };
        conversations.value = await tickets.conversations(props.id);
        ui.pushToast({ severity: 'success', summary: 'Note saved.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Save note failed.' });
    }
}

function openInFreshdesk() {
    const domain = system.freshdesk?.domain;
    if (!domain || !ticket.value?.freshdesk_id) return;
    window.open(`https://${domain}/a/tickets/${ticket.value.freshdesk_id}`, '_blank');
}
</script>

<template>
    <div v-if="ticket" class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-semibold">#{{ ticket.display_id || ticket.freshdesk_id || ticket.id }} · {{ ticket.subject }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <StatusPill :status="ticket.status" />
                    <PriorityIcon :priority="ticket.priority" />
                    <span v-if="ticket.spam" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        <i class="pi pi-flag-fill text-[10px]"></i> Spam
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <Button label="Close" icon="pi pi-check" severity="secondary" outlined @click="patch('status', 5)" />
                <Button
                    :label="ticket.spam ? 'Unmark spam' : 'Mark spam'"
                    severity="warn"
                    outlined
                    @click="patch('spam', !ticket.spam)"
                />
                <Button label="Open in Freshdesk" icon="pi pi-external-link" outlined @click="openInFreshdesk" />
                <Button label="Delete" severity="danger" outlined @click="tickets.destroy(ticket.id).then(() => router.push('/tickets'))" />
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 lg:col-span-8 flex flex-col gap-4">
                <Tabs v-model:value="tab">
                    <TabList>
                        <Tab value="conversation">Conversation</Tab>
                        <Tab value="time">Time entries</Tab>
                        <Tab value="csat">Satisfaction</Tab>
                        <Tab value="activity">Activity log</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel value="conversation">
                            <div class="flex flex-col gap-3 mt-3">
                                <div v-for="c in conversations" :key="c.id" class="bg-surface-0 dark:bg-surface-900 border rounded p-3" :class="c.private ? 'border-amber-300 bg-amber-50 dark:bg-amber-900/10' : 'border-surface-200 dark:border-surface-700'">
                                    <div class="flex items-center justify-between mb-1">
                                        <AgentAvatar :agent="c.user" showName size="small" />
                                        <span class="text-xs text-surface-500">{{ c.private ? 'Internal note' : 'Reply' }} · {{ c.created_at }}</span>
                                    </div>
                                    <div v-html="c.body_html || c.body" class="text-sm"></div>
                                </div>
                                <p v-if="!conversations.length" class="text-surface-400 text-center py-6">No replies yet.</p>

                                <div class="mt-4 bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-3">
                                    <Tabs v-model:value="activeComposer">
                                        <TabList>
                                            <Tab value="reply">Reply</Tab>
                                            <Tab value="note">Note</Tab>
                                        </TabList>
                                        <TabPanels>
                                            <TabPanel value="reply">
                                                <RichEditor v-model="reply.body" placeholder="Type your reply…" />
                                                <AttachmentDropzone v-model="reply.attachments" class="mt-3" />
                                                <div class="flex justify-end gap-2 mt-3">
                                                    <Button label="Send & close" severity="secondary" outlined @click="sendReply(true)" />
                                                    <Button label="Send" @click="sendReply(false)" />
                                                </div>
                                            </TabPanel>
                                            <TabPanel value="note">
                                                <RichEditor v-model="note.body" placeholder="Type an internal note…" />
                                                <AttachmentDropzone v-model="note.attachments" class="mt-3" />
                                                <div class="flex justify-end mt-3">
                                                    <Button label="Save note" @click="saveNote" />
                                                </div>
                                            </TabPanel>
                                        </TabPanels>
                                    </Tabs>
                                </div>
                            </div>
                        </TabPanel>
                        <TabPanel value="time">
                            <p class="text-surface-500 text-sm py-6 text-center">Time entries panel — wired to /tickets/:id/time-entries.</p>
                        </TabPanel>
                        <TabPanel value="csat">
                            <p class="text-surface-500 text-sm py-6 text-center">Satisfaction ratings — wired once the backend ships.</p>
                        </TabPanel>
                        <TabPanel value="activity">
                            <div class="flex flex-col gap-2 mt-3">
                                <p v-if="activityLoaded && !activity.length" class="text-surface-400 text-center py-6 text-base">No activity yet.</p>
                                <div v-for="entry in activity" :key="entry.id"
                                    class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-4">
                                    <div class="flex items-center justify-between mb-2 gap-2 flex-wrap">
                                        <div class="flex items-center gap-2 text-base">
                                            <span class="font-medium">{{ entryMeta(entry).title }}</span>
                                            <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', badgeClass(entryMeta(entry).badge)]">
                                                {{ entryMeta(entry).badge }}
                                            </span>
                                            <span class="text-surface-500 font-normal text-sm">
                                                · {{ entry.user?.name || (entry.actor_type === 'system' ? 'System' : 'Unknown') }}
                                            </span>
                                        </div>
                                        <span class="text-sm text-surface-500">{{ formatDate(entry.created_at) }}</span>
                                    </div>
                                    <ul v-if="entry.action === 'ticket.updated'" class="text-sm text-surface-600 dark:text-surface-300 mt-1 flex flex-col gap-1">
                                        <li v-for="c in diffEntry(entry)" :key="c.key">
                                            <span class="font-medium">{{ c.label }}:</span>
                                            <span class="text-surface-500">{{ c.from }}</span>
                                            <span class="mx-1">→</span>
                                            <span>{{ c.to }}</span>
                                        </li>
                                        <li v-if="!diffEntry(entry).length" class="text-surface-400 italic">No tracked field changes</li>
                                    </ul>
                                    <div v-else-if="entry.action === 'conversation.reply'" class="mt-2 flex flex-col gap-1 text-sm">
                                        <template v-if="findConversation(entry)">
                                            <div v-if="findConversation(entry).from_email" class="text-surface-500">
                                                <span class="font-medium">From:</span> {{ findConversation(entry).from_email }}
                                            </div>
                                            <div v-if="findConversation(entry).to_emails?.length" class="text-surface-500">
                                                <span class="font-medium">To:</span> {{ findConversation(entry).to_emails.join(', ') }}
                                            </div>
                                            <div v-if="findConversation(entry).cc_emails?.length" class="text-surface-500">
                                                <span class="font-medium">Cc:</span> {{ findConversation(entry).cc_emails.join(', ') }}
                                            </div>
                                            <div v-if="findConversation(entry).bcc_emails?.length" class="text-surface-500">
                                                <span class="font-medium">Bcc:</span> {{ findConversation(entry).bcc_emails.join(', ') }}
                                            </div>
                                            <div class="mt-1 p-2 rounded bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-surface-700 dark:text-surface-200"
                                                v-html="findConversation(entry).body_html || findConversation(entry).body || findConversation(entry).body_text"></div>
                                        </template>
                                        <div v-else class="text-surface-400 italic">
                                            Reply not loaded (conversation #{{ entry.meta?.conversation_id }})
                                        </div>
                                    </div>
                                    <div v-else-if="entry.meta" class="text-sm text-surface-500 mt-1">
                                        {{ Object.entries(entry.meta).map(([k, v]) => `${k}: ${formatVal(v)}`).join(' · ') }}
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>

            <aside class="col-span-12 lg:col-span-4 flex flex-col gap-4">
                <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-3 flex flex-col gap-3">
                    <h3 class="font-medium text-sm">Properties</h3>
                    <label class="text-xs text-surface-500">Status
                        <Select :modelValue="ticket.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full mt-1"
                            @update:modelValue="(v) => patch('status', v)" />
                    </label>
                    <label class="text-xs text-surface-500">Agent
                        <AssignPicker :modelValue="ticket.responder_id" @update:modelValue="(v) => patch('responder_id', v)" />
                    </label>
                    <label class="text-xs text-surface-500">Tags
                        <TagInput :modelValue="ticket.tags || []" @update:modelValue="(v) => patch('tags', v)" />
                    </label>
                </div>

                <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded p-3">
                    <h3 class="font-medium text-sm mb-2">Requester</h3>
                    <div class="flex items-center gap-2">
                        <AgentAvatar :agent="ticket.requester" size="large" />
                        <div>
                            <div class="text-sm font-medium">{{ ticket.requester?.name }}</div>
                            <div class="text-xs text-surface-500">{{ ticket.requester?.email }}</div>
                        </div>
                    </div>
                    <router-link v-if="ticket.requester?.id" :to="`/contacts/${ticket.requester.id}`" class="text-xs text-primary mt-2 inline-block">View contact →</router-link>
                </div>
            </aside>
        </div>
    </div>
    <div v-else class="py-20 text-center text-surface-500">Loading ticket…</div>
</template>
