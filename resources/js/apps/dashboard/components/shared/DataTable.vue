<script setup>
import { computed } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    value: { type: Array, default: () => [] },
    columns: { type: Array, default: () => [] },
    selection: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
    loading: { type: Boolean, default: false },
    emptyMessage: { type: String, default: 'No records found.' },
    rowAction: { type: Function, default: null }, // (row) => void
});

const emit = defineEmits(['update:selection', 'sort', 'load-more', 'row-click']);

const hasSelection = computed(() => props.columns.some((c) => c.selectionMode));
</script>

<template>
    <DataTable
        :value="value"
        :loading="loading"
        :selection="selection"
        @update:selection="(s) => emit('update:selection', s)"
        :dataKey="rowKey"
        :rowHover="true"
        @row-click="(e) => emit('row-click', e.data)"
        :selectionMode="hasSelection ? null : undefined"
        stripedRows
        class="w-full"
    >
        <template #empty>
            <div class="py-8 text-center text-surface-500">{{ emptyMessage }}</div>
        </template>
        <Column
            v-for="col in columns"
            :key="col.field ?? col.header ?? col.selectionMode"
            :field="col.field"
            :header="col.header"
            :sortable="col.sortable"
            :selectionMode="col.selectionMode"
            :style="col.style"
            :headerStyle="col.headerStyle"
        >
            <template v-if="col.template" #body="slotProps">
                <component :is="col.template" :row="slotProps.data" :value="col.field ? slotProps.data[col.field] : null" />
            </template>
        </Column>
        <slot />
    </DataTable>
</template>
