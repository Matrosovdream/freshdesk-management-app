<script setup>
import { onMounted, ref, watch } from 'vue';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { useAgents } from '@/stores/agents';

const props = defineProps({ id: { type: [String, Number], required: true } });
const agents = useAgents();
const agent = ref(null);
const tab = ref('profile');

async function load() {
    agent.value = await agents.show(props.id);
}
onMounted(load);
watch(() => props.id, load);
</script>

<template>
    <div v-if="agent" class="flex flex-col gap-4">
        <h1 class="text-2xl font-semibold">{{ agent.name || agent.email }}</h1>
        <Tabs v-model:value="tab">
            <TabList>
                <Tab value="profile">Profile</Tab>
                <Tab value="tickets">Assigned tickets</Tab>
                <Tab value="time">Time entries</Tab>
                <Tab value="perf">Performance</Tab>
            </TabList>
            <TabPanels>
                <TabPanel value="profile">
                    <pre class="text-xs bg-surface-100 dark:bg-surface-800 p-3 rounded overflow-auto">{{ agent }}</pre>
                </TabPanel>
                <TabPanel value="tickets"><p class="text-surface-500 text-sm py-6 text-center">Assigned tickets table.</p></TabPanel>
                <TabPanel value="time"><p class="text-surface-500 text-sm py-6 text-center">Time entries table.</p></TabPanel>
                <TabPanel value="perf"><p class="text-surface-500 text-sm py-6 text-center">30d performance: resolved, avg FRT, avg resolution, CSAT.</p></TabPanel>
            </TabPanels>
        </Tabs>
    </div>
    <div v-else class="py-20 text-center text-surface-500">Loading agent…</div>
</template>
