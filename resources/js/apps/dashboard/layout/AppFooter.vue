<script setup>
import { computed } from 'vue';
import { useSystem } from '@/stores/system';
import { useUi } from '@/stores/ui';
import { useRouter } from 'vue-router';

const system = useSystem();
const ui = useUi();
const router = useRouter();

const rlTone = computed(() => {
    const r = ui.rateLimit?.remaining;
    if (r == null) return 'text-surface-500';
    if (r < 100) return 'text-red-500';
    if (r < 500) return 'text-amber-500';
    return 'text-green-600';
});
</script>

<template>
    <div class="layout-footer flex items-center justify-between gap-3 flex-wrap">
        <div class="text-sm text-surface-500">
            <span v-if="system.freshdesk?.domain">Connected to <b>{{ system.freshdesk.domain }}</b></span>
            <span v-else>No Freshdesk connection configured</span>
        </div>
        <button
            v-if="ui.rateLimit"
            class="text-xs px-2 py-1 rounded-full border border-surface-200 dark:border-surface-700"
            :class="rlTone"
            @click="router.push('/system/freshdesk')"
        >
            Rate limit: {{ ui.rateLimit.remaining }}<span v-if="ui.rateLimit.limit">/{{ ui.rateLimit.limit }}</span>
        </button>
    </div>
</template>
