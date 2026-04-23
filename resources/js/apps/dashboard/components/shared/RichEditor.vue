<script setup>
import Editor from 'primevue/editor';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <Editor
        :modelValue="modelValue"
        @update:modelValue="(v) => emit('update:modelValue', v)"
        :placeholder="placeholder"
        editorStyle="min-height: 180px"
    />
</template>
