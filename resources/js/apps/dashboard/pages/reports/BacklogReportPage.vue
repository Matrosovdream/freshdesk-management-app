<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import DateRangePicker from '@/components/shared/DateRangePicker.vue';
import { useReports } from '@/stores/reports';

const reports = useReports();
const range = ref([null, null]);

async function load() {
    await reports.fetch('backlog', { from: range.value[0], to: range.value[1] });
}
async function exportCsv() {
    const url = await reports.export('backlog', { from: range.value[0], to: range.value[1] });
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
        <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4 min-h-[240px]">
            <h3 class="font-medium mb-2">Open/Pending by age</h3>
            <p v-if="!reports.backlog" class="text-surface-400 text-center py-10">No data.</p>
            <pre v-else class="text-xs bg-surface-100 dark:bg-surface-800 p-3 rounded overflow-auto">{{ reports.backlog }}</pre>
        </div>
    </div>
</template>
