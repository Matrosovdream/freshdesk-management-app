<script setup>
import { ref } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: 'Are you sure?' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirm' },
    confirmSeverity: { type: String, default: 'primary' },
    requireTyped: { type: String, default: '' }, // e.g. 'CLEAR' or 'RESYNC'
});
const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']);

const typed = ref('');

function onHide() {
    typed.value = '';
    emit('update:modelValue', false);
}

function canConfirm() {
    if (!props.requireTyped) return true;
    return typed.value === props.requireTyped;
}

function confirm() {
    emit('confirm');
    onHide();
}
</script>

<template>
    <Dialog :visible="modelValue" @update:visible="emit('update:modelValue', $event)" :header="title" :closable="true" modal :style="{ width: '28rem' }">
        <p class="mb-3">{{ message }}</p>
        <div v-if="requireTyped" class="mb-3">
            <label class="text-xs text-surface-500 block mb-1">Type <b>{{ requireTyped }}</b> to confirm</label>
            <InputText v-model="typed" class="w-full" />
        </div>
        <template #footer>
            <Button label="Cancel" severity="secondary" outlined @click="onHide" />
            <Button :label="confirmLabel" :severity="confirmSeverity" :disabled="!canConfirm()" @click="confirm" />
        </template>
    </Dialog>
</template>
