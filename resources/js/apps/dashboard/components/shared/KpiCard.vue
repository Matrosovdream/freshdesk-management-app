<script setup>
const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number, null], default: null },
    delta: { type: Number, default: null },
    icon: { type: String, default: 'pi pi-chart-line' },
    tone: { type: String, default: 'default' }, // 'default' | 'danger' | 'warn' | 'success'
});
const toneClasses = {
    default: 'text-surface-900 dark:text-surface-0',
    danger:  'text-red-600',
    warn:    'text-amber-600',
    success: 'text-green-600',
};
</script>

<template>
    <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-surface-500 uppercase tracking-wide">{{ label }}</span>
            <i :class="icon" class="text-surface-400"></i>
        </div>
        <div class="mt-2 text-2xl font-semibold" :class="toneClasses[tone] || toneClasses.default">
            {{ value ?? '—' }}
        </div>
        <div v-if="delta != null" class="mt-1 text-xs" :class="delta >= 0 ? 'text-green-600' : 'text-red-600'">
            {{ delta >= 0 ? '▲' : '▼' }} {{ Math.abs(delta) }}%
        </div>
    </div>
</template>
