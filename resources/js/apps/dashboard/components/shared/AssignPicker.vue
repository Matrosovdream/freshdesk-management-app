<script setup>
import { ref, watch } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import { http } from '@shared/http';

const props = defineProps({
    modelValue: { type: [Number, String, Object, null], default: null },
    kind: { type: String, default: 'agents' }, // 'agents' | 'contacts'
    placeholder: { type: String, default: 'Search…' },
});
const emit = defineEmits(['update:modelValue']);

const suggestions = ref([]);
const selected = ref(null);

const basePath = () => (props.kind === 'contacts' ? '/api/v1/admin/contacts' : '/api/v1/admin/agents');

async function resolve(value) {
    if (value == null || value === '') {
        selected.value = null;
        return;
    }
    if (typeof value === 'object') {
        selected.value = value;
        return;
    }
    if (selected.value?.id === value) return;
    try {
        const { data } = await http.get(`${basePath()}/${value}`);
        selected.value = data?.data ?? data ?? { id: value, name: String(value) };
    } catch {
        selected.value = { id: value, name: String(value) };
    }
}

watch(() => props.modelValue, resolve, { immediate: true });

async function search(ev) {
    try {
        const { data } = await http.get(basePath(), { params: { autocomplete: ev.query, search: ev.query } });
        suggestions.value = data?.data ?? data ?? [];
    } catch {
        suggestions.value = [];
    }
}

function onChange(v) {
    selected.value = v && typeof v === 'object' ? v : null;
    emit('update:modelValue', v?.id ?? v ?? null);
}
</script>

<template>
    <AutoComplete
        :modelValue="selected"
        :suggestions="suggestions"
        @complete="search"
        @update:modelValue="onChange"
        :placeholder="placeholder"
        optionLabel="name"
        :dropdown="true"
        class="w-full"
    />
</template>
