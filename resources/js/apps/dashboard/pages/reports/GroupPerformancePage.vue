<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import DateRangePicker from '@/components/shared/DateRangePicker.vue';
import { useReports } from '@/stores/reports';

const reports = useReports();
const range = ref([null, null]);

async function load() {
    await reports.fetch('group-performance', { from: range.value[0], to: range.value[1] });
}
async function exportCsv() {
    const url = await reports.export('group-performance', { from: range.value[0], to: range.value[1] });
    if (url) window.location.assign(url);
}
onMounted(load);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Group performance</h1>
            <div class="flex gap-2 items-center">
                <DateRangePicker v-model="range" />
                <Button label="Apply" @click="load" />
                <Button label="Export CSV" icon="pi pi-download" outlined @click="exportCsv" />
            </div>
        </div>
        <DataTable :value="reports.groupPerformance?.rows || []" :loading="reports.loading['group-performance']" stripedRows>
            <Column field="group.name" header="Group" />
            <Column field="assigned" header="Assigned" />
            <Column field="resolved" header="Resolved" />
            <Column field="avg_frt" header="Avg FRT" />
            <Column field="avg_resolution" header="Avg resolution" />
            <Column field="csat_avg" header="CSAT" />
        </DataTable>
    </div>
</template>
