<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Message from 'primevue/message';

import { useRequests } from '../../stores/requests';
import { useAuth } from '../../stores/auth';
import StatusChip from '../../components/StatusChip.vue';
import Composer from '../../components/Composer.vue';
import CsatPrompt from '../../components/CsatPrompt.vue';

const props = defineProps({
    id: { type: [String, Number], required: true },
});

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const store = useRequests();
const auth = useAuth();

const request = ref(null);
const loading = ref(true);
const error = ref(null);
const replying = ref(false);
const composer = ref(null);
const csatDismissed = ref(false);

const status = computed(() => (request.value?.status || '').toLowerCase());
const isClosed = computed(() => status.value === 'closed');
const isResolved = computed(() => status.value === 'resolved');
const canResolve = computed(() => ['open', 'pending', 'pending_reply'].includes(status.value));
const canReopen = computed(() => isResolved.value);

const showCsat = computed(() => {
    return isResolved.value && !request.value?.rating && !csatDismissed.value;
});

function submittedOnDisplay() {
    const ts = request.value?.created_at;
    if (!ts) return '';
    const date = new Date(ts);
    const abs = date.toLocaleString();
    return abs;
}

async function load() {
    loading.value = true;
    error.value = null;
    try {
        request.value = await store.fetch(props.id);
    } catch (e) {
        error.value = e?.response?.status || 'error';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    load();

    // TODO: subscribe to Reverb channel `portal.user.{user.id}` and handle
    // `ConversationCreated` events for this ticket. Prepend to thread, flash
    // a "New reply" indicator, and auto-scroll if the user is at the bottom.
    // Reverb client bootstrap is out of scope for this step.
});

async function sendReply({ body, attachments }) {
    replying.value = true;
    try {
        let payload;
        if (attachments?.length) {
            payload = new FormData();
            payload.append('body', body);
            for (const file of attachments) {
                payload.append('attachments[]', file);
            }
        } else {
            payload = { body };
        }
        await store.reply(props.id, payload);
        toast.add({ severity: 'success', summary: 'Reply sent.', life: 3000 });
        composer.value?.reset();
        await load();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: e?.response?.data?.message || 'Could not send reply.',
            life: 4000,
        });
    } finally {
        replying.value = false;
    }
}

async function reopenWithReply(payload) {
    try {
        await sendReply(payload);
        await store.reopen(props.id);
        await load();
    } catch {
        // handled above
    }
}

function confirmResolve() {
    confirm.require({
        message: 'Mark this request as resolved?',
        header: 'Resolve request',
        icon: 'pi pi-check-circle',
        acceptLabel: 'Mark resolved',
        accept: async () => {
            try {
                await store.resolve(props.id);
                toast.add({ severity: 'success', summary: 'Request marked resolved.', life: 3000 });
                await load();
            } catch {
                toast.add({ severity: 'error', summary: 'Could not update status.', life: 3000 });
            }
        },
    });
}

function confirmReopen() {
    confirm.require({
        message: 'Reopen this request?',
        header: 'Reopen request',
        icon: 'pi pi-refresh',
        acceptLabel: 'Reopen',
        accept: async () => {
            try {
                await store.reopen(props.id);
                toast.add({ severity: 'success', summary: 'Request reopened.', life: 3000 });
                await load();
            } catch {
                toast.add({ severity: 'error', summary: 'Could not update status.', life: 3000 });
            }
        },
    });
}

async function submitRating(payload) {
    try {
        await store.rate(props.id, payload);
        toast.add({ severity: 'success', summary: 'Thanks for the feedback!', life: 3000 });
        csatDismissed.value = true;
        await load();
    } catch {
        toast.add({ severity: 'error', summary: 'Could not submit rating.', life: 3000 });
    }
}

function isOwnMessage(message) {
    const userId = auth.user?.id;
    return userId && (message.user_id === userId || message.from_customer);
}
</script>

<template>
    <div v-if="loading" class="text-sm text-gray-400 text-center py-6">Loading request…</div>

    <div v-else-if="error === 403" class="text-center py-10">
        <h2 class="text-lg font-semibold text-gray-800">You don't have access to this request.</h2>
        <Button
            class="mt-4"
            label="Back to my requests"
            severity="secondary"
            outlined
            @click="router.push({ name: 'portal.requests' })"
        />
    </div>

    <div v-else-if="error === 404 || !request" class="text-center py-10">
        <h2 class="text-lg font-semibold text-gray-800">This request doesn't exist or was deleted.</h2>
        <Button
            class="mt-4"
            label="Back to my requests"
            severity="secondary"
            outlined
            @click="router.push({ name: 'portal.requests' })"
        />
    </div>

    <div v-else class="space-y-5">
        <header class="space-y-2">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">{{ request.subject }}</h1>
                    <p class="text-xs text-gray-400 mt-1">Submitted on {{ submittedOnDisplay() }}</p>
                </div>
                <StatusChip :status="request.status" />
            </div>
            <div v-if="request.assigned_agent" class="flex items-center gap-2 text-sm text-gray-600">
                <img
                    v-if="request.assigned_agent.avatar_url"
                    :src="request.assigned_agent.avatar_url"
                    alt=""
                    class="h-6 w-6 rounded-full"
                />
                <span>Agent: {{ request.assigned_agent.name }}</span>
            </div>
        </header>

        <CsatPrompt
            v-if="showCsat"
            @submit="submitRating"
            @dismiss="csatDismissed = true"
        />

        <div class="flex items-center gap-2 flex-wrap">
            <Button
                v-if="canResolve"
                icon="pi pi-check"
                label="Mark resolved"
                severity="secondary"
                outlined
                @click="confirmResolve"
            />
            <Button
                v-if="canReopen"
                icon="pi pi-refresh"
                label="Reopen"
                severity="secondary"
                outlined
                @click="confirmReopen"
            />
        </div>

        <Divider />

        <section class="space-y-3">
            <article class="bg-white border border-gray-200 rounded-lg p-4">
                <header class="flex items-center gap-2 text-sm text-gray-600">
                    <strong>You</strong>
                    <span class="text-xs text-gray-400">{{ submittedOnDisplay() }}</span>
                </header>
                <div class="prose prose-sm max-w-none mt-2" v-html="request.description || request.description_text || ''" />
            </article>

            <article
                v-for="msg in request.conversations || []"
                :key="msg.id"
                class="border rounded-lg p-4"
                :class="isOwnMessage(msg)
                    ? 'bg-primary-50 border-primary-200 ml-6'
                    : 'bg-white border-gray-200 mr-6'"
            >
                <header class="flex items-center gap-2 text-sm">
                    <img
                        v-if="msg.author?.avatar_url"
                        :src="msg.author.avatar_url"
                        alt=""
                        class="h-6 w-6 rounded-full"
                    />
                    <strong>{{ msg.author?.name || (isOwnMessage(msg) ? 'You' : 'Agent') }}</strong>
                    <span class="text-xs text-gray-400">
                        {{ msg.created_at ? new Date(msg.created_at).toLocaleString() : '' }}
                    </span>
                </header>
                <div class="prose prose-sm max-w-none mt-2" v-html="msg.body_html || msg.body || ''" />
                <ul v-if="msg.attachments?.length" class="mt-2 text-sm text-primary-600 space-y-1">
                    <li v-for="att in msg.attachments" :key="att.id">
                        <a :href="att.url" target="_blank" rel="noopener">
                            <i class="pi pi-paperclip mr-1" />{{ att.name }}
                        </a>
                    </li>
                </ul>
            </article>
        </section>

        <Message
            v-if="isClosed"
            severity="secondary"
            :closable="false"
        >
            This request is closed. Submit a new request if you need more help.
        </Message>

        <section v-else>
            <h2 class="text-lg font-medium text-gray-800 mb-2">Your reply</h2>
            <Composer
                ref="composer"
                :loading="replying"
                submit-label="Send reply"
                :show-secondary="isResolved"
                secondary-label="Reopen with this reply"
                @submit="sendReply"
                @secondary="reopenWithReply"
            />
        </section>
    </div>
</template>
