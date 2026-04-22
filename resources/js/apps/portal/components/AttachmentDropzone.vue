<script setup>
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import Message from 'primevue/message';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    maxFiles: { type: Number, default: 10 },
    maxSizeMb: { type: Number, default: 15 },
});

const emit = defineEmits(['update:modelValue', 'change']);

const dragOver = ref(false);
const error = ref('');
const input = ref(null);

const maxBytes = computed(() => props.maxSizeMb * 1024 * 1024);

function openPicker() {
    input.value?.click();
}

function onFilesChosen(event) {
    addFiles(Array.from(event.target.files || []));
    event.target.value = '';
}

function onDrop(event) {
    dragOver.value = false;
    addFiles(Array.from(event.dataTransfer?.files || []));
}

function addFiles(files) {
    error.value = '';
    const next = [...props.modelValue];
    for (const file of files) {
        if (next.length >= props.maxFiles) {
            error.value = `You can attach up to ${props.maxFiles} files.`;
            break;
        }
        if (file.size > maxBytes.value) {
            error.value = `"${file.name}" is larger than ${props.maxSizeMb}MB.`;
            continue;
        }
        next.push(file);
    }
    emit('update:modelValue', next);
    emit('change', next);
}

function remove(index) {
    const next = [...props.modelValue];
    next.splice(index, 1);
    emit('update:modelValue', next);
    emit('change', next);
}

function prettyBytes(size) {
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / 1024 / 1024).toFixed(1)} MB`;
}
</script>

<template>
    <div>
        <div
            class="border-2 border-dashed rounded-lg p-4 text-center text-sm"
            :class="dragOver ? 'border-primary-400 bg-primary-50' : 'border-gray-300 bg-gray-50'"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
        >
            <p class="text-gray-600">
                Drag files here, or
                <button type="button" class="text-primary-600 underline" @click="openPicker">browse</button>
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Up to {{ maxFiles }} files, {{ maxSizeMb }}MB each.
            </p>
            <input ref="input" type="file" multiple class="hidden" @change="onFilesChosen" />
        </div>

        <Message v-if="error" severity="warn" :closable="false" class="mt-2">
            {{ error }}
        </Message>

        <ul v-if="modelValue.length" class="mt-2 space-y-1">
            <li
                v-for="(file, idx) in modelValue"
                :key="`${file.name}-${idx}`"
                class="flex items-center justify-between text-sm bg-white border border-gray-200 rounded px-2 py-1"
            >
                <span class="truncate">{{ file.name }} <span class="text-gray-400">· {{ prettyBytes(file.size) }}</span></span>
                <Button
                    type="button"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    rounded
                    size="small"
                    aria-label="Remove attachment"
                    @click="remove(idx)"
                />
            </li>
        </ul>
    </div>
</template>
