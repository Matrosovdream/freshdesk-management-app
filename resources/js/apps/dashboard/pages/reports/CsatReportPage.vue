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
    await reports.fetch('csat', { from: range.value[0], to: range.value[1] });
}
onMounted(load);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">CSAT</h1>
            <div class="flex gap-2 items-center">
                <DateRangePicker v-model="range" />
                <Button label="Apply" @click="load" />
            </div>
        </div>
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 xl:col-span-5 bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4 min-h-[240px]">
                <h3 class="font-medium mb-2">Ratings distribution</h3>
                <p v-if="!reports.csat" class="text-surface-400 text-center py-10">No data.</p>
                <pre v-else class="text-xs bg-surface-100 dark:bg-surface-800 p-3 rounded overflow-auto">{{ reports.csat?.distribution }}</pre>
            </div>
            <div class="col-span-12 xl:col-span-7">
                <DataTable :value="reports.csat?.comments || []" stripedRows>
                    <Column field="rating" header="Rating" />
                    <Column field="comment" header="Comment" />
                    <Column field="agent.name" header="Agent" />
                    <Column field="created_at" header="Date" />
                </DataTable>
            </div>
        </div>
    </div>
</template>
