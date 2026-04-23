<script setup>
import { onMounted, reactive, ref } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Password from 'primevue/password';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import { useSystem } from '@/stores/system';
import { useUi } from '@/stores/ui';

const system = useSystem();
const ui = useUi();

const form = reactive({ domain: '', api_key: '' });
const editingKey = ref(false);
const loading = ref(false);
const testResult = ref(null);
const showClearModal = ref(false);

onMounted(async () => {
    await system.fetchFreshdesk();
    form.domain = system.freshdesk?.domain || '';
});

async function save() {
    loading.value = true;
    try {
        await system.updateFreshdesk({ domain: form.domain, api_key: form.api_key || undefined });
        ui.pushToast({ severity: 'success', summary: 'Connection saved.' });
        editingKey.value = false;
        form.api_key = '';
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Save failed.' });
    } finally {
        loading.value = false;
    }
}

async function test() {
    try {
        const result = await system.testFreshdesk();
        testResult.value = { ok: true, message: result?.message || `Connected as ${result?.agent?.name || 'agent'}.` };
        ui.pushToast({ severity: 'success', summary: testResult.value.message });
    } catch (e) {
        testResult.value = { ok: false, message: e?.response?.data?.message || 'Connection failed.' };
    }
}

async function clearMirror() {
    try {
        await system.clearMirror();
        ui.pushToast({ severity: 'warn', summary: 'Local mirror cleared.' });
    } catch {
        ui.pushToast({ severity: 'error', summary: 'Clear failed.' });
    }
}
</script>

<template>
    <div class="flex flex-col gap-4 max-w-2xl">
        <h1 class="text-2xl font-semibold">Freshdesk connection</h1>

        <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-5 flex flex-col gap-3">
            <Message v-if="testResult" :severity="testResult.ok ? 'success' : 'error'" :closable="false">
                {{ testResult.message }}
            </Message>

            <div>
                <label class="text-sm font-medium">Domain</label>
                <InputText v-model="form.domain" placeholder="acme.freshdesk.com" class="w-full" />
            </div>

            <div>
                <label class="text-sm font-medium">API key</label>
                <div class="flex gap-2 items-center">
                    <Password v-if="editingKey" v-model="form.api_key" :toggleMask="true" :feedback="false" fluid />
                    <span v-else class="text-surface-500">•••••••••••• <small>(stored)</small></span>
                    <Button :label="editingKey ? 'Cancel' : 'Edit key'" size="small" outlined @click="editingKey = !editingKey" />
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <Button label="Test connection" icon="pi pi-bolt" outlined @click="test" />
                <Button label="Save connection" :loading="loading" @click="save" />
            </div>

            <div v-if="system.freshdesk?.rate_limit_remaining != null" class="text-xs text-surface-500">
                Last rate-limit remaining: {{ system.freshdesk.rate_limit_remaining }}<span v-if="system.freshdesk.rate_limit_total">/{{ system.freshdesk.rate_limit_total }}</span>
            </div>
        </div>

        <div class="border border-red-300 rounded-lg p-5">
            <h3 class="font-medium text-red-600 mb-1">Danger zone</h3>
            <p class="text-sm text-surface-500 mb-3">Clearing the local mirror removes every row synced from Freshdesk.</p>
            <Button label="Clear local mirror" severity="danger" outlined @click="showClearModal = true" />
        </div>

        <ConfirmModal
            v-model="showClearModal"
            title="Clear local mirror?"
            message="This deletes every locally-mirrored Freshdesk row. It does not affect Freshdesk itself."
            confirmLabel="Clear"
            confirmSeverity="danger"
            requireTyped="CLEAR"
            @confirm="clearMirror"
        />
    </div>
</template>
