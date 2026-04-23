<script setup>
import { computed, onMounted, ref } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import { useSystem } from '@/stores/system';
import { useUi } from '@/stores/ui';

const system = useSystem();
const ui = useUi();

const local = ref({});
const loading = ref(false);

const groups = computed(() => {
    const byGroup = {};
    for (const s of system.settings) {
        byGroup[s.group] = byGroup[s.group] || [];
        byGroup[s.group].push(s);
    }
    return byGroup;
});

async function load() {
    await system.fetchSettings();
    local.value = Object.fromEntries(system.settings.map((s) => [s.key, s.value]));
}

async function save() {
    loading.value = true;
    try {
        const updates = Object.entries(local.value).map(([key, value]) => ({ key, value }));
        await system.updateSettings(updates);
        ui.pushToast({ severity: 'success', summary: 'Settings saved.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Save failed.' });
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div class="flex flex-col gap-4 max-w-3xl">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-semibold">Settings</h1>
            <Button label="Save" :loading="loading" @click="save" />
        </div>

        <div v-for="(items, group) in groups" :key="group" class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-5">
            <h3 class="font-medium capitalize mb-3">{{ group }}</h3>
            <div class="flex flex-col gap-3">
                <div v-for="s in items" :key="s.key">
                    <label class="text-sm font-medium mb-1 block">{{ s.label || s.key }}</label>
                    <template v-if="s.type === 'boolean'">
                        <label class="flex items-center gap-2 text-sm"><Checkbox v-model="local[s.key]" binary /> enabled</label>
                    </template>
                    <InputText v-else v-model="local[s.key]" class="w-full" />
                    <p v-if="s.description" class="text-xs text-surface-500 mt-1">{{ s.description }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
