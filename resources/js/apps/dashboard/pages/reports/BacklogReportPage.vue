<script setup>
import { computed, onMounted, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import DateRangePicker from '@/components/shared/DateRangePicker.vue';
import { useReports } from '@/stores/reports';

const reports = useReports();
const range = ref([null, null]);

const bucketLabels = {
    '0_1d': '0–1 day',
    '1_3d': '1–3 days',
    '3_7d': '3–7 days',
    '7d+':  '7+ days',
};

const rows = computed(() => {
    const raw = reports.backlog?.rows ?? [];
    const total = raw.reduce((sum, r) => sum + (Number(r.count) || 0), 0);
    return raw.map((r) => ({
        ...r,
        label: bucketLabels[r.bucket] ?? r.bucket,
        share: total > 0 ? Math.round(((Number(r.count) || 0) / total) * 100) : 0,
    }));
});

function toDateString(d) {
    if (!(d instanceof Date) || Number.isNaN(d.getTime())) return null;
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function rangeParams() {
    const [from, to] = range.value || [];
    const params = {};
    const f = toDateString(from);
    const t = toDateString(to);
    if (f) params.from = f;
    if (t) params.to = t;
    return params;
}

async function load() {
    await reports.fetch('backlog', rangeParams());
}
async function exportCsv() {
    const url = await reports.export('backlog', rangeParams());
    if (url) window.location.assign(url);
}
onMounted(load);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Backlog report</h1>
            <div class="flex gap-2 items-center">
                <DateRangePicker v-model="range" />
                <Button label="Apply" @click="load" />
                <Button label="Export CSV" icon="pi pi-download" outlined @click="exportCsv" />
            </div>
        </div>

        <DataTable
            :value="rows"
            :loading="reports.loading['backlog']"
            stripedRows
            dataKey="bucket"
            paginator
            :rows="10"
            :rowsPerPageOptions="[10, 25, 50]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            currentPageReportTemplate="{first}–{last} of {totalRecords}">
            <Column field="label" header="Age bucket" />
            <Column field="count" header="Tickets" style="width: 10rem" />
            <Column header="Share" style="width: 14rem">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 bg-surface-200 dark:bg-surface-700 rounded overflow-hidden">
                            <div class="h-full bg-primary" :style="{ width: data.share + '%' }" />
                        </div>
                        <span class="text-xs text-surface-500 w-10 text-right">{{ data.share }}%</span>
                    </div>
                </template>
            </Column>
            <template #empty>
                <div class="text-surface-400 text-center py-6">No backlog data for this range.</div>
            </template>
        </DataTable>
    </div>
</template>
