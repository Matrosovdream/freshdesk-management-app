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
const selected = ref(props.modelValue);

watch(() => props.modelValue, (v) => (selected.value = v));

async function search(ev) {
    const q = ev.query;
    const path = props.kind === 'contacts' ? '/api/v1/admin/contacts' : '/api/v1/admin/agents';
    try {
        const { data } = await http.get(path, { params: { autocomplete: q, search: q } });
        suggestions.value = data?.data ?? data ?? [];
    } catch {
        suggestions.value = [];
    }
}

function onSelect(e) {
    const val = e.value?.id ?? e.value;
    emit('update:modelValue', val);
}
</script>

<template>
    <AutoComplete
        v-model="selected"
        :suggestions="suggestions"
        @complete="search"
        @update:modelValue="(v) => emit('update:modelValue', v?.id ?? v)"
        :placeholder="placeholder"
        optionLabel="name"
        :dropdown="true"
        class="w-full"
    />
</template>
