<script setup>
import { computed, onMounted, ref } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
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

const ratingMeta = {
    love:    { label: 'Love it',  icon: 'pi-heart-fill',     color: 'text-pink-500',     severity: 'success' },
    like:    { label: 'Like it',  icon: 'pi-thumbs-up-fill', color: 'text-green-500',    severity: 'success' },
    neutral: { label: 'Neutral',  icon: 'pi-minus-circle',   color: 'text-surface-400',  severity: 'secondary' },
    dislike: { label: 'Dislike',  icon: 'pi-thumbs-down-fill', color: 'text-orange-500', severity: 'warn' },
    hate:    { label: 'Hate it',  icon: 'pi-times-circle',   color: 'text-red-500',      severity: 'danger' },
};

const distribution = computed(() => {
    const dist = reports.csat?.distribution ?? {};
    const total = Object.values(dist).reduce((s, v) => s + (Number(v) || 0), 0);
    return Object.entries(ratingMeta).map(([key, meta]) => {
        const count = Number(dist[key]) || 0;
        const share = total > 0 ? Math.round((count / total) * 100) : 0;
        return { key, ...meta, count, share };
    });
});

const totals = computed(() => {
    const dist = reports.csat?.distribution ?? {};
    const total = Object.values(dist).reduce((s, v) => s + (Number(v) || 0), 0);
    const positive = (Number(dist.love) || 0) + (Number(dist.like) || 0);
    const score = total > 0 ? Math.round((positive / total) * 100) : null;
    return { total, positive, score };
});

async function load() {
    await reports.fetch('csat', rangeParams());
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

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                <p class="text-xs text-surface-500">CSAT score</p>
                <p class="text-2xl font-semibold mt-1">{{ totals.score === null ? '—' : `${totals.score}%` }}</p>
                <p class="text-xs text-surface-400 mt-1">% positive (love + like)</p>
            </div>
            <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                <p class="text-xs text-surface-500">Total ratings</p>
                <p class="text-2xl font-semibold mt-1">{{ totals.total }}</p>
            </div>
            <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                <p class="text-xs text-surface-500">Positive ratings</p>
                <p class="text-2xl font-semibold mt-1">{{ totals.positive }}</p>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 xl:col-span-5 bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
                <h3 class="font-medium mb-4">Ratings distribution</h3>
                <div v-if="totals.total === 0" class="text-surface-400 text-center py-10">No ratings yet.</div>
                <div v-else class="flex flex-col gap-3">
                    <div v-for="row in distribution" :key="row.key" class="flex items-center gap-3">
                        <div class="flex items-center gap-2 w-28 text-sm">
                            <i :class="['pi', row.icon, row.color]"></i>
                            <span>{{ row.label }}</span>
                        </div>
                        <div class="flex-1 h-2 bg-surface-200 dark:bg-surface-700 rounded overflow-hidden">
                            <div class="h-full bg-primary" :style="{ width: row.share + '%' }" />
                        </div>
                        <span class="text-sm w-10 text-right">{{ row.count }}</span>
                        <span class="text-xs text-surface-500 w-10 text-right">{{ row.share }}%</span>
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-7">
                <DataTable
                    :value="reports.csat?.comments ?? []"
                    :loading="reports.loading['csat']"
                    stripedRows
                    paginator
                    :rows="10"
                    :rowsPerPageOptions="[10, 25, 50]"
                    paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    currentPageReportTemplate="{first}–{last} of {totalRecords}">
                    <Column field="rating" header="Rating" style="width: 9rem">
                        <template #body="{ data }">
                            <Tag :value="ratingMeta[data.rating]?.label ?? data.rating"
                                :severity="ratingMeta[data.rating]?.severity ?? 'info'" />
                        </template>
                    </Column>
                    <Column field="comment" header="Comment" />
                    <Column field="agent.name" header="Agent" />
                    <Column field="created_at" header="Date" style="width: 11rem">
                        <template #body="{ data }">{{ $formatDate(data.created_at) }}</template>
                    </Column>
                    <template #empty>
                        <div class="text-surface-400 text-center py-6">No comments in this range.</div>
                    </template>
                </DataTable>
            </div>
        </div>
    </div>
</template>
