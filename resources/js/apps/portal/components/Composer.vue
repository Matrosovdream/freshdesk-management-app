<script setup>
import { ref } from 'vue';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import AttachmentDropzone from './AttachmentDropzone.vue';

const props = defineProps({
    loading: { type: Boolean, default: false },
    submitLabel: { type: String, default: 'Send reply' },
    secondaryLabel: { type: String, default: '' },
    showSecondary: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'secondary']);

const body = ref('');
const attachments = ref([]);

function buildPayload() {
    return { body: body.value, attachments: attachments.value };
}

function onSubmit() {
    if (!body.value.trim()) return;
    emit('submit', buildPayload());
}

function onSecondary() {
    emit('secondary', buildPayload());
}

function reset() {
    body.value = '';
    attachments.value = [];
}

defineExpose({ reset });
</script>

<template>
    <div class="space-y-3">
        <div>
            <label for="composer-body" class="sr-only">Your reply</label>
            <Textarea
                id="composer-body"
                v-model="body"
                rows="5"
                autoResize
                class="w-full"
                placeholder="Type your reply…"
                :disabled="loading"
            />
            <p class="text-xs text-gray-400 mt-1">Basic formatting: **bold**, *italic*, links, and lists.</p>
        </div>

        <AttachmentDropzone v-model="attachments" />

        <div class="flex items-center justify-end gap-2">
            <Button
                v-if="showSecondary"
                type="button"
                :label="secondaryLabel"
                severity="secondary"
                outlined
                :disabled="loading || !body.trim()"
                @click="onSecondary"
            />
            <Button
                type="button"
                :label="submitLabel"
                :loading="loading"
                :disabled="loading || !body.trim()"
                @click="onSubmit"
            />
        </div>
    </div>
</template>
