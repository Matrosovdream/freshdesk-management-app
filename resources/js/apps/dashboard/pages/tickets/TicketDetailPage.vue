<script setup>
import { onMounted, ref, watch } from 'vue';
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
const activeComposer = ref('reply');
const reply = ref({ body: '', attachments: [] });
const note = ref({ body: '', private: true, attachments: [] });
const tab = ref('conversation');

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
                </div>
            </div>
            <div class="flex gap-2">
                <Button label="Close" icon="pi pi-check" severity="secondary" outlined @click="patch('status', 5)" />
                <Button label="Mark spam" severity="warn" outlined @click="patch('spam', true)" />
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
                            <p class="text-surface-500 text-sm py-6 text-center">Activity log — local audit entries for this ticket.</p>
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
