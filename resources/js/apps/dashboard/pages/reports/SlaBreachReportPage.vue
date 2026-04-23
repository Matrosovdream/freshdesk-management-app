<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import DateRangePicker from '@/components/shared/DateRangePicker.vue';
import { useReports } from '@/stores/reports';
import { useRouter } from 'vue-router';

const reports = useReports();
const router = useRouter();
const range = ref([null, null]);

async function load() {
    await reports.fetch('sla-breaches', { from: range.value[0], to: range.value[1] });
}
onMounted(load);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">SLA breaches</h1>
            <div class="flex gap-2 items-center">
                <DateRangePicker v-model="range" />
                <Button label="Apply" @click="load" />
            </div>
        </div>
        <DataTable :value="reports.slaBreaches?.rows || []" :loading="reports.loading['sla-breaches']" stripedRows
            @row-click="(e) => router.push(`/tickets/${e.data.ticket_id}`)" rowHover>
            <Column field="display_id" header="#" />
            <Column field="subject" header="Subject" />
            <Column field="breach_type" header="Breach" />
            <Column field="breached_at" header="At" />
            <Column field="agent.name" header="Agent" />
        </DataTable>
    </div>
</template>
