<script setup>
import { ref } from 'vue';
import Select from 'primevue/select';
import Button from 'primevue/button';

const props = defineProps({
    views: { type: Array, default: () => [] },
    modelValue: { type: [String, Number, null], default: null },
});
const emit = defineEmits(['update:modelValue', 'save', 'delete']);

const selected = ref(props.modelValue);
</script>

<template>
    <div class="flex items-center gap-2">
        <Select
            v-model="selected"
            :options="views"
            optionLabel="name"
            optionValue="id"
            placeholder="Saved view"
            showClear
            class="w-56"
            @change="(e) => emit('update:modelValue', e.value)"
        />
        <Button icon="pi pi-save" text size="small" @click="emit('save')" aria-label="Save view" />
    </div>
</template>
