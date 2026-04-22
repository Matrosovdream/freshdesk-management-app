<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps({
    status: { type: String, default: 'open' },
});

const STATUS_MAP = {
    open: { label: "We're on it", severity: 'info' },
    pending: { label: 'Waiting for your reply', severity: 'warn' },
    pending_reply: { label: 'Waiting for your reply', severity: 'warn' },
    resolved: { label: 'Resolved', severity: 'success' },
    closed: { label: 'Closed', severity: 'secondary' },
};

const descriptor = computed(() => {
    const key = (props.status || '').toString().toLowerCase();
    return STATUS_MAP[key] || { label: props.status || 'Unknown', severity: 'secondary' };
});
</script>

<template>
    <Tag :value="descriptor.label" :severity="descriptor.severity" rounded />
</template>
