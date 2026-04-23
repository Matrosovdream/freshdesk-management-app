<script setup>
import DatePicker from 'primevue/datepicker';

const props = defineProps({
    modelValue: { type: Array, default: () => [null, null] },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <DatePicker
        :modelValue="modelValue"
        @update:modelValue="(v) => emit('update:modelValue', v)"
        selectionMode="range"
        dateFormat="yy-mm-dd"
        showIcon
        class="w-full"
        placeholder="Date range"
    />
</template>
