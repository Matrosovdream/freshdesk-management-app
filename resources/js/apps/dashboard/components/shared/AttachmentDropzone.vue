<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    accept: { type: String, default: '*' },
    maxSizeMb: { type: Number, default: 20 },
});
const emit = defineEmits(['update:modelValue']);

const dragging = ref(false);
const input = ref(null);
const error = ref('');

function handleFiles(files) {
    const accepted = [];
    for (const f of files) {
        if (f.size > props.maxSizeMb * 1024 * 1024) {
            error.value = `${f.name} exceeds ${props.maxSizeMb} MB.`;
            continue;
        }
        accepted.push(f);
    }
    emit('update:modelValue', [...props.modelValue, ...accepted]);
}

function onDrop(e) {
    dragging.value = false;
    handleFiles(Array.from(e.dataTransfer?.files || []));
}

function onPick(e) {
    handleFiles(Array.from(e.target.files || []));
    e.target.value = '';
}

function remove(idx) {
    const next = [...props.modelValue];
    next.splice(idx, 1);
    emit('update:modelValue', next);
}
</script>

<template>
    <div>
        <div
            class="border-2 border-dashed rounded-lg p-6 text-center transition-colors"
            :class="dragging ? 'border-primary bg-primary/5' : 'border-surface-300 dark:border-surface-700'"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <i class="pi pi-cloud-upload text-3xl text-surface-400"></i>
            <p class="mt-2 text-sm">Drop files here or</p>
            <Button label="Browse" size="small" outlined class="mt-2" @click="input?.click()" />
            <input ref="input" type="file" :accept="accept" multiple class="hidden" @change="onPick" />
        </div>
        <p v-if="error" class="text-xs text-red-500 mt-2">{{ error }}</p>
        <ul v-if="modelValue.length" class="mt-3 space-y-1">
            <li v-for="(f, idx) in modelValue" :key="idx" class="flex items-center gap-2 text-sm">
                <i class="pi pi-paperclip"></i>
                <span class="truncate">{{ f.name }}</span>
                <span class="text-xs text-surface-500">{{ (f.size / 1024).toFixed(1) }} KB</span>
                <Button icon="pi pi-times" rounded text size="small" @click="remove(idx)" aria-label="Remove" />
            </li>
        </ul>
    </div>
</template>
