<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import KpiCard from '@/components/shared/KpiCard.vue';
import ScopePill from '@/components/shared/ScopePill.vue';
import AssignQueueWizard from '@/components/shared/AssignQueueWizard.vue';
import { http, ensureCsrf } from '@shared/http';
import { useAuth } from '@/stores/auth';
import { useSystem } from '@/stores/system';
import { useUi } from '@/stores/ui';

const auth = useAuth();
const system = useSystem();
const ui = useUi();

const overview = ref(null);
const loading = ref(false);
const refreshing = ref(false);
const assignWizardOpen = ref(false);

async function load() {
    loading.value = true;
    try {
        const { data } = await http.get('/api/v1/admin/overview');
        overview.value = data?.data ?? data ?? null;
    } catch {
        overview.value = null;
    } finally {
        loading.value = false;
    }
}

async function refresh() {
    refreshing.value = true;
    try {
        await ensureCsrf();
        await http.post('/api/v1/admin/overview/refresh');
        ui.pushToast({ severity: 'info', summary: 'Sync queued' });
        setTimeout(() => (refreshing.value = false), 10_000);
    } catch {
        refreshing.value = false;
        ui.pushToast({ severity: 'error', summary: 'Could not queue sync.' });
    }
}

async function exportSnapshot() {
    try {
        await ensureCsrf();
        const { data } = await http.post('/api/v1/admin/reports/volume/export');
        const url = data?.data?.download_url ?? data?.download_url;
        if (url) window.location.assign(url);
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Export failed.' });
    }
}

onMounted(async () => {
    await Promise.allSettled([load(), system.fetchFreshdesk()]);
});
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Overview</h1>
                <p class="text-sm text-surface-500">Hello{{ auth.user?.name ? `, ${auth.user.name}` : '' }} — here's your helpdesk health.</p>
            </div>
            <div class="flex gap-2">
                <Button v-if="auth.isManagerOnly && auth.can('tickets.assign')" label="Assign queue" icon="pi pi-sparkles" @click="assignWizardOpen = true" />
                <Button label="Refresh" icon="pi pi-refresh" outlined :loading="refreshing" @click="refresh" />
                <Button label="Export snapshot" icon="pi pi-download" outlined @click="exportSnapshot" />
            </div>
        </div>

        <ScopePill />

        <AssignQueueWizard v-model="assignWizardOpen" @assigned="load" />

        <div
            v-if="system.freshdesk && !system.freshdesk.test_ok"
            class="p-5 border border-amber-300 bg-amber-50 dark:bg-amber-900/20 rounded-lg flex items-center justify-between gap-3"
        >
            <span>Connect your Freshdesk account to start.</span>
            <router-link to="/system/freshdesk"><Button label="Configure now" size="small" /></router-link>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <KpiCard label="Open"         :value="overview?.kpis?.open" icon="pi pi-inbox" />
            <KpiCard label="Pending"      :value="overview?.kpis?.pending" icon="pi pi-clock" tone="warn" />
            <KpiCard label="Overdue"      :value="overview?.kpis?.overdue" icon="pi pi-exclamation-triangle" tone="danger" />
            <KpiCard label="Unassigned"   :value="overview?.kpis?.unassigned" icon="pi pi-user" />
            <KpiCard label="SLA breach (today)" :value="overview?.kpis?.sla_breaches_today" icon="pi pi-flag" tone="danger" />
            <KpiCard label="Avg FRT (7d)" :value="overview?.kpis?.avg_frt_7d" icon="pi pi-stopwatch" />
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 xl:col-span-8 bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                <h3 class="font-medium mb-2">Ticket volume (30d)</h3>
                <p v-if="!overview" class="text-sm text-surface-500">Loading…</p>
                <div v-else class="h-64 flex items-center justify-center text-surface-400">
                    Chart placeholder — wired to /api/v1/admin/reports/volume
                </div>
            </div>
            <div class="col-span-12 xl:col-span-4 flex flex-col gap-6">
                <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                    <h3 class="font-medium mb-2">Top agents (7d)</h3>
                    <ul class="space-y-1 text-sm">
                        <li v-for="a in (overview?.top_agents || [])" :key="a.id" class="flex justify-between">
                            <span>{{ a.name }}</span><span class="text-surface-500">{{ a.resolved }}</span>
                        </li>
                        <li v-if="!overview?.top_agents?.length" class="text-surface-400">No data yet.</li>
                    </ul>
                </div>
                <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                    <h3 class="font-medium mb-2">Top companies (open)</h3>
                    <ul class="space-y-1 text-sm">
                        <li v-for="c in (overview?.top_companies || [])" :key="c.id" class="flex justify-between">
                            <span>{{ c.name }}</span><span class="text-surface-500">{{ c.open }}</span>
                        </li>
                        <li v-if="!overview?.top_companies?.length" class="text-surface-400">No data yet.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
            <h3 class="font-medium mb-2">Recent activity</h3>
            <ul class="divide-y divide-surface-200 dark:divide-surface-700 text-sm">
                <li v-for="a in (overview?.activity || [])" :key="a.id" class="py-2">
                    <span class="text-surface-500">{{ a.when }}</span> · {{ a.summary }}
                </li>
                <li v-if="!overview?.activity?.length" class="py-4 text-surface-400">No recent activity.</li>
            </ul>
        </div>
    </div>
</template>
