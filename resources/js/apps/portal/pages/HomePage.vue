<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Divider from 'primevue/divider';

import { http } from '../../../shared/http';
import { useAuth } from '../stores/auth';
import { useRequests } from '../stores/requests';
import RequestCard from '../components/RequestCard.vue';
import CsatPrompt from '../components/CsatPrompt.vue';
import EmptyState from '../components/EmptyState.vue';

const auth = useAuth();
const requests = useRequests();
const router = useRouter();

const openRequests = ref([]);
const awaitingYou = ref([]);
const recentResolved = ref([]);
const loading = ref(true);
const ratedIds = ref(new Set());

async function fetchBucket(status, extraParams = {}) {
    try {
        const { data } = await http.get('/api/v1/portal/requests', {
            params: { status, limit: 5, ...extraParams },
        });
        return data?.data ?? data?.items ?? [];
    } catch {
        return [];
    }
}

onMounted(async () => {
    loading.value = true;
    try {
        const [opens, pendings, resolveds] = await Promise.all([
            fetchBucket('open', { limit: 5 }),
            fetchBucket('pending_reply', { limit: 5 }),
            fetchBucket('resolved', { limit: 3, unrated: 1 }),
        ]);
        openRequests.value = opens;
        awaitingYou.value = pendings;
        recentResolved.value = resolveds;
    } finally {
        loading.value = false;
    }
});

async function submitRating(request, payload) {
    try {
        await requests.rate(request.id, payload);
        ratedIds.value.add(request.id);
    } catch {
        // silently ignore for the scaffold
    }
}

function dismissRating(request) {
    ratedIds.value.add(request.id);
}
</script>

<template>
    <div class="space-y-6">
        <section class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">
                    Hi, {{ auth.firstName || 'there' }} 👋
                </h1>
                <p class="text-sm text-gray-500 mt-1">Here's a snapshot of your requests.</p>
            </div>
            <Button
                icon="pi pi-plus"
                label="Submit a new request"
                @click="router.push({ name: 'portal.requests.new' })"
            />
        </section>

        <section>
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-lg font-medium text-gray-800">Your open requests</h2>
                <router-link :to="{ name: 'portal.requests' }" class="text-sm text-primary-600 hover:underline">
                    View all
                </router-link>
            </div>
            <div v-if="loading" class="text-sm text-gray-400">Loading…</div>
            <div v-else-if="openRequests.length" class="space-y-2">
                <RequestCard v-for="r in openRequests" :key="r.id" :request="r" />
            </div>
            <EmptyState
                v-else
                title="You haven't submitted anything yet."
                description="When you do, you'll see it here."
            >
                <template #cta>
                    <Button
                        icon="pi pi-plus"
                        label="Submit a new request"
                        @click="router.push({ name: 'portal.requests.new' })"
                    />
                </template>
            </EmptyState>
        </section>

        <section v-if="awaitingYou.length">
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <h2 class="text-lg font-medium text-amber-900 mb-2">Awaiting your reply</h2>
                <div class="space-y-2">
                    <RequestCard v-for="r in awaitingYou" :key="r.id" :request="r" />
                </div>
            </div>
        </section>

        <section v-if="recentResolved.length">
            <h2 class="text-lg font-medium text-gray-800 mb-2">Resolved recently</h2>
            <div class="space-y-2">
                <template v-for="r in recentResolved" :key="r.id">
                    <RequestCard :request="r" />
                    <CsatPrompt
                        v-if="!ratedIds.has(r.id)"
                        @submit="(payload) => submitRating(r, payload)"
                        @dismiss="dismissRating(r)"
                    />
                </template>
            </div>
        </section>

        <Divider />

        <section>
            <h2 class="text-lg font-medium text-gray-800 mb-2">Quick actions</h2>
            <div class="flex flex-wrap gap-2">
                <Button
                    icon="pi pi-plus"
                    label="Submit new request"
                    severity="secondary"
                    outlined
                    @click="router.push({ name: 'portal.requests.new' })"
                />
                <Button
                    icon="pi pi-list"
                    label="View all requests"
                    severity="secondary"
                    outlined
                    @click="router.push({ name: 'portal.requests' })"
                />
                <Button
                    icon="pi pi-user-edit"
                    label="Edit profile"
                    severity="secondary"
                    outlined
                    @click="router.push({ name: 'portal.profile' })"
                />
            </div>
        </section>
    </div>
</template>
