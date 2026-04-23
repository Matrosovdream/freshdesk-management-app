<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ScopePill from '@/components/shared/ScopePill.vue';
import { useAgents } from '@/stores/agents';
import { useAuth } from '@/stores/auth';

const agents = useAgents();
const auth = useAuth();
const router = useRouter();
onMounted(() => agents.fetch());
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Agents</h1>
            <div class="flex gap-2">
                <Button v-if="auth.can('agents.bulk_create')" label="Bulk create" icon="pi pi-upload" outlined />
                <Button v-if="auth.can('agents.create')" label="+ New agent" icon="pi pi-plus" @click="router.push('/agents/new')" />
            </div>
        </div>
        <ScopePill />
        <DataTable :value="agents.items" :loading="agents.loading" stripedRows dataKey="id"
            @row-click="(e) => router.push(`/agents/${e.data.id}`)" rowHover>
            <Column field="name" header="Name" />
            <Column field="email" header="Email" />
            <Column field="type" header="Type" />
            <Column field="ticket_scope" header="Scope" />
            <Column field="last_login_at" header="Last login" />
        </DataTable>
    </div>
</template>
