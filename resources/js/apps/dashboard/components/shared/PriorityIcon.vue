<script setup>
import { computed } from 'vue';

const props = defineProps({
    priority: { type: [Number, String], required: true },
});

const MAP = {
    1: { label: 'Low',    icon: 'pi pi-chevron-down',   color: 'text-surface-400' },
    2: { label: 'Medium', icon: 'pi pi-minus',          color: 'text-blue-500' },
    3: { label: 'High',   icon: 'pi pi-chevron-up',     color: 'text-orange-500' },
    4: { label: 'Urgent', icon: 'pi pi-arrow-up',       color: 'text-red-500' },
};

const info = computed(() => MAP[props.priority] || { label: String(props.priority), icon: 'pi pi-circle', color: '' });
</script>

<template>
    <span class="inline-flex items-center gap-1">
        <i :class="[info.icon, info.color]"></i>
        <span class="text-xs">{{ info.label }}</span>
    </span>
</template>
