<script setup>
import AutoComplete from 'primevue/autocomplete';
import { ref } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    suggestions: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Add tag…' },
});
const emit = defineEmits(['update:modelValue']);

const matches = ref([]);

function search(ev) {
    const q = ev.query.toLowerCase();
    matches.value = (props.suggestions || []).filter((s) => s.toLowerCase().includes(q));
}
</script>

<template>
    <AutoComplete
        :modelValue="modelValue"
        @update:modelValue="(v) => emit('update:modelValue', v)"
        multiple
        typeahead
        :suggestions="matches"
        @complete="search"
        :placeholder="placeholder"
        class="w-full"
    />
</template>
