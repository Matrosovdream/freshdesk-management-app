<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';

import { useRequests } from '../../stores/requests';
import { useAuth } from '../../stores/auth';
import RequestCard from '../../components/RequestCard.vue';
import EmptyState from '../../components/EmptyState.vue';

const router = useRouter();
const requests = useRequests();
const auth = useAuth();

const FILTERS = [
    { value: 'all',           label: 'All' },
    { value: 'open',          label: 'Open' },
    { value: 'pending_reply', label: 'Waiting on us' },
    { value: 'pending',       label: 'Waiting on you' },
    { value: 'resolved',      label: 'Resolved' },
    { value: 'closed',        label: 'Closed' },
];

const activeStatus = ref('all');
const searchRaw = ref('');
const companyScope = ref(false);

let searchDebounce = null;

function runSearch() {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        requests.load({
            status: activeStatus.value,
            search: searchRaw.value,
            scope: companyScope.value ? 'company' : 'own',
        });
    }, 300);
}

function selectStatus(value) {
    activeStatus.value = value;
    requests.load({
        status: value,
        search: searchRaw.value,
        scope: companyScope.value ? 'company' : 'own',
    });
}

watch(companyScope, () => {
    requests.load({
        status: activeStatus.value,
        search: searchRaw.value,
        scope: companyScope.value ? 'company' : 'own',
    });
});

onMounted(() => {
    requests.load({ status: 'all', search: '', scope: 'own' });
    setupInfiniteScroll();
});

function setupInfiniteScroll() {
    if (typeof window === 'undefined') return;
    window.addEventListener('scroll', onScroll, { passive: true });
}

function onScroll() {
    const threshold = 200;
    const scrolled = window.innerHeight + window.scrollY;
    if (scrolled >= document.body.offsetHeight - threshold) {
        if (requests.hasMore && !requests.loadingList) {
            requests.loadNextPage();
        }
    }
}

const hasAny = computed(() => requests.list.length > 0);
</script>

<template>
    <div class="space-y-4">
        <header class="flex items-center justify-between gap-3 flex-wrap">
            <h1 class="text-2xl font-semibold text-gray-800">My requests</h1>
            <Button
                icon="pi pi-plus"
                label="New request"
                @click="router.push({ name: 'portal.requests.new' })"
            />
        </header>

        <div class="flex items-center gap-2 flex-wrap">
            <button
                v-for="f in FILTERS"
                :key="f.value"
                type="button"
                class="text-sm px-3 py-1 rounded-full border transition-colors"
                :class="activeStatus === f.value
                    ? 'bg-primary-500 border-primary-500 text-white'
                    : 'bg-white border-gray-200 text-gray-700 hover:border-primary-400'"
                @click="selectStatus(f.value)"
            >
                {{ f.label }}
            </button>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <InputText
                    v-model="searchRaw"
                    placeholder="Search your requests…"
                    class="w-full"
                    @update:modelValue="runSearch"
                />
            </div>
            <label v-if="auth.canViewCompanyTickets" class="flex items-center gap-2 text-sm text-gray-700">
                <ToggleSwitch v-model="companyScope" />
                Show all company requests
            </label>
        </div>

        <div v-if="requests.loadingList && !hasAny" class="text-sm text-gray-400 py-4 text-center">
            Loading your requests…
        </div>

        <div v-else-if="hasAny" class="space-y-2">
            <RequestCard v-for="r in requests.list" :key="r.id" :request="r" />
            <div v-if="requests.hasMore" class="flex justify-center pt-2">
                <Button
                    label="Load more"
                    severity="secondary"
                    outlined
                    :loading="requests.loadingList"
                    @click="requests.loadNextPage()"
                />
            </div>
        </div>

        <EmptyState
            v-else
            title="You haven't submitted anything yet."
            description="Need help? Submit a new request."
        >
            <template #cta>
                <Button
                    icon="pi pi-plus"
                    label="Submit a new request"
                    @click="router.push({ name: 'portal.requests.new' })"
                />
            </template>
        </EmptyState>
    </div>
</template>
