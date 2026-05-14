<script setup>
import { computed, onMounted, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import DateRangePicker from '@/components/shared/DateRangePicker.vue';
import { useReports } from '@/stores/reports';

const reports = useReports();
const range = ref([null, null]);

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

const rows = computed(() => {
    const created  = reports.volume?.created  ?? [];
    const resolved = reports.volume?.resolved ?? [];
    const map = new Map();
    for (const r of created)  map.set(r.day, { day: r.day, created: r.n, resolved: 0 });
    for (const r of resolved) {
        const existing = map.get(r.day) ?? { day: r.day, created: 0, resolved: 0 };
        existing.resolved = r.n;
        map.set(r.day, existing);
    }
    return [...map.values()]
        .sort((a, b) => a.day.localeCompare(b.day))
        .map((r) => ({ ...r, net: r.created - r.resolved }));
});

const totals = computed(() => rows.value.reduce(
    (acc, r) => ({
        created:  acc.created  + r.created,
        resolved: acc.resolved + r.resolved,
        net:      acc.net      + r.net,
    }),
    { created: 0, resolved: 0, net: 0 },
));

async function load() {
    await reports.fetch('volume', rangeParams());
}
async function exportCsv() {
    const url = await reports.export('volume', rangeParams());
    if (url) window.location.assign(url);
}
onMounted(load);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Volume</h1>
            <div class="flex gap-2 items-center">
                <DateRangePicker v-model="range" />
                <Button label="Apply" @click="load" />
                <Button label="Export CSV" icon="pi pi-download" outlined @click="exportCsv" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                <p class="text-xs text-surface-500">Total created</p>
                <p class="text-2xl font-semibold mt-1">{{ totals.created }}</p>
            </div>
            <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                <p class="text-xs text-surface-500">Total resolved</p>
                <p class="text-2xl font-semibold mt-1">{{ totals.resolved }}</p>
            </div>
            <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                <p class="text-xs text-surface-500">Net</p>
                <p class="text-2xl font-semibold mt-1" :class="totals.net > 0 ? 'text-red-500' : totals.net < 0 ? 'text-green-500' : ''">
                    {{ totals.net > 0 ? '+' : '' }}{{ totals.net }}
                </p>
            </div>
        </div>

        <DataTable
            :value="rows"
            :loading="reports.loading['volume']"
            stripedRows
            dataKey="day"
            paginator
            :rows="14"
            :rowsPerPageOptions="[14, 30, 60]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            currentPageReportTemplate="{first}–{last} of {totalRecords}">
            <Column field="day" header="Day" sortable />
            <Column field="created" header="Created" sortable style="width: 10rem" />
            <Column field="resolved" header="Resolved" sortable style="width: 10rem" />
            <Column field="net" header="Net" sortable style="width: 10rem">
                <template #body="{ data }">
                    <span :class="data.net > 0 ? 'text-red-500' : data.net < 0 ? 'text-green-500' : ''">
                        {{ data.net > 0 ? '+' : '' }}{{ data.net }}
                    </span>
                </template>
            </Column>
            <template #empty>
                <div class="text-surface-400 text-center py-6">No tickets in this range.</div>
            </template>
        </DataTable>
    </div>
</template>
