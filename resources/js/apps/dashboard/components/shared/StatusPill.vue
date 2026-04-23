<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: [String, Number], required: true },
});

const STATUS_MAP = {
    2: { label: 'Open',     severity: 'info'    },
    3: { label: 'Pending',  severity: 'warn'    },
    4: { label: 'Resolved', severity: 'success' },
    5: { label: 'Closed',   severity: 'secondary' },
};

const info = computed(() => {
    const entry = STATUS_MAP[props.status] || STATUS_MAP[String(props.status).toLowerCase()];
    if (entry) return entry;
    return { label: String(props.status), severity: 'secondary' };
});

const classes = computed(() => {
    const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium';
    const palette = {
        info: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
        warn: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
        success: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200',
        secondary: 'bg-surface-200 text-surface-700 dark:bg-surface-700 dark:text-surface-200',
    };
    return `${base} ${palette[info.value.severity] || palette.secondary}`;
});
</script>

<template>
    <span :class="classes">{{ info.label }}</span>
</template>
