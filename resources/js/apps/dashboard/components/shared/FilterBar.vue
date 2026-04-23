<script setup>
import { ref, watch } from 'vue';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Button from 'primevue/button';

const props = defineProps({
    schema: { type: Array, required: true }, // [{ key, label, type, options?, placeholder? }]
    modelValue: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue', 'apply', 'clear']);

const local = ref({ ...props.modelValue });

watch(
    () => props.modelValue,
    (v) => (local.value = { ...v }),
    { deep: true },
);

function apply() {
    emit('update:modelValue', { ...local.value });
    emit('apply', { ...local.value });
}

function clear() {
    local.value = {};
    emit('update:modelValue', {});
    emit('clear');
}
</script>

<template>
    <div class="flex flex-wrap items-end gap-3 p-3 bg-surface-50 dark:bg-surface-900 rounded-lg">
        <div v-for="f in schema" :key="f.key" class="flex flex-col gap-1 min-w-40">
            <label class="text-xs text-surface-500">{{ f.label }}</label>
            <MultiSelect
                v-if="f.type === 'multi'"
                v-model="local[f.key]"
                :options="f.options || []"
                :optionLabel="f.optionLabel || 'label'"
                :optionValue="f.optionValue || 'value'"
                :placeholder="f.placeholder || 'Any'"
                class="w-full"
                filter
            />
            <Select
                v-else-if="f.type === 'select'"
                v-model="local[f.key]"
                :options="f.options || []"
                :optionLabel="f.optionLabel || 'label'"
                :optionValue="f.optionValue || 'value'"
                :placeholder="f.placeholder || 'Any'"
                class="w-full"
                showClear
            />
            <InputText
                v-else
                v-model="local[f.key]"
                :placeholder="f.placeholder || ''"
                @keydown.enter="apply"
                class="w-full"
            />
        </div>
        <div class="flex gap-2 ml-auto">
            <Button label="Clear" severity="secondary" outlined @click="clear" />
            <Button label="Apply" @click="apply" />
        </div>
    </div>
</template>
