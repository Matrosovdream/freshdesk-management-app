<script setup>
import { computed, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { http } from '@shared/http';
import { useTickets } from '@/stores/tickets';
import { useAgents } from '@/stores/agents';
import { useUi } from '@/stores/ui';

const props = defineProps({ modelValue: { type: Boolean, default: false } });
const emit = defineEmits(['update:modelValue', 'assigned']);

const tickets = useTickets();
const agents = useAgents();
const ui = useUi();

const strategy = ref('round_robin');
const targetAgents = ref([]);
const unassigned = ref([]);
const loading = ref(false);

const strategies = [
    { label: 'Round-robin', value: 'round_robin' },
    { label: 'Least loaded', value: 'least_loaded' },
    { label: 'Manual', value: 'manual' },
];

const preview = computed(() => {
    if (!unassigned.value.length || !targetAgents.value.length) return [];
    if (strategy.value === 'round_robin') {
        return unassigned.value.map((t, i) => ({
            ticket_id: t.id,
            subject:   t.subject,
            agent_id:  targetAgents.value[i % targetAgents.value.length],
        }));
    }
    // Least-loaded approximation: distribute evenly.
    const counts = Object.fromEntries(targetAgents.value.map((id) => [id, 0]));
    return unassigned.value.map((t) => {
        const next = Object.entries(counts).sort((a, b) => a[1] - b[1])[0][0];
        counts[next]++;
        return { ticket_id: t.id, subject: t.subject, agent_id: Number(next) };
    });
});

async function loadUnassigned() {
    loading.value = true;
    try {
        const { data } = await http.get('/api/v1/admin/tickets', {
            params: { responder_id_is_null: 1, status: [2, 3], per_page: 100 },
        });
        unassigned.value = (data?.data ?? data ?? []).filter((t) => !t.responder_id);
    } finally {
        loading.value = false;
    }
}

async function assignAll() {
    const byAgent = {};
    preview.value.forEach((r) => {
        (byAgent[r.agent_id] ??= []).push(r.ticket_id);
    });
    for (const [agentId, ids] of Object.entries(byAgent)) {
        await tickets.bulkUpdate(ids, { responder_id: Number(agentId) });
    }
    ui.pushToast({ severity: 'success', summary: `Assigned ${preview.value.length} ticket(s).` });
    emit('assigned');
    emit('update:modelValue', false);
}

watch(() => props.modelValue, (open) => {
    if (open) {
        Promise.allSettled([loadUnassigned(), agents.fetch({ available: 1 })]);
    }
});
</script>

<template>
    <Dialog :visible="modelValue" @update:visible="(v) => emit('update:modelValue', v)" header="Assign queue" modal :style="{ width: '48rem' }">
        <div class="flex flex-col gap-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-surface-500">Target agents</label>
                    <MultiSelect v-model="targetAgents" :options="agents.items" optionLabel="name" optionValue="id" filter class="w-full" placeholder="Pick agents" />
                </div>
                <div>
                    <label class="text-xs text-surface-500">Strategy</label>
                    <Select v-model="strategy" :options="strategies" optionLabel="label" optionValue="value" class="w-full" />
                </div>
            </div>
            <DataTable :value="preview" :loading="loading" stripedRows :paginator="preview.length > 10" :rows="10">
                <Column field="ticket_id" header="#" style="width: 6rem" />
                <Column field="subject" header="Subject" />
                <Column header="Agent">
                    <template #body="{ data }">
                        {{ agents.items.find((a) => a.id === data.agent_id)?.name || data.agent_id }}
                    </template>
                </Column>
            </DataTable>
            <p v-if="!unassigned.length" class="text-surface-500 text-center py-3">No unassigned tickets in scope.</p>
        </div>
        <template #footer>
            <Button label="Cancel" severity="secondary" outlined @click="emit('update:modelValue', false)" />
            <Button label="Assign all" :disabled="!preview.length" @click="assignAll" />
        </template>
    </Dialog>
</template>
