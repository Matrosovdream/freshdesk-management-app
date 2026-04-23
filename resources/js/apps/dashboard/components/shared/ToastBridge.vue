<script setup>
import { watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useUi } from '@/stores/ui';

const toast = useToast();
const ui = useUi();

watch(
    () => ui.toasts.length,
    (n) => {
        if (!n) return;
        for (const t of ui.toasts.splice(0, n)) {
            toast.add({
                severity: t.severity || 'info',
                summary: t.summary,
                detail: t.detail,
                life: t.life ?? 3500,
            });
        }
    },
);
</script>

<template><span class="hidden"></span></template>
