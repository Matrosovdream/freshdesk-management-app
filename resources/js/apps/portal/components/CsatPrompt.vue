<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';

const props = defineProps({
    loading: { type: Boolean, default: false },
    dismissible: { type: Boolean, default: true },
});

const emit = defineEmits(['submit', 'dismiss']);

const SCORES = [
    { value: 1, emoji: '😞', label: 'Very unhappy' },
    { value: 2, emoji: '🙁', label: 'Unhappy' },
    { value: 3, emoji: '😐', label: 'Neutral' },
    { value: 4, emoji: '🙂', label: 'Happy' },
    { value: 5, emoji: '😄', label: 'Very happy' },
];

const selected = ref(null);
const comment = ref('');

function submit() {
    if (!selected.value) return;
    emit('submit', { score: selected.value, comment: comment.value });
}
</script>

<template>
    <section class="bg-primary-50 border border-primary-200 rounded-lg p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="font-medium text-gray-800">How did we do?</h3>
                <p class="text-sm text-gray-500">Your feedback helps us get better.</p>
            </div>
            <Button
                v-if="dismissible"
                type="button"
                icon="pi pi-times"
                severity="secondary"
                text
                rounded
                size="small"
                aria-label="Dismiss"
                @click="emit('dismiss')"
            />
        </div>

        <div class="flex items-center gap-2 mt-3">
            <button
                v-for="score in SCORES"
                :key="score.value"
                type="button"
                class="h-10 w-10 rounded-full text-xl border transition-colors"
                :class="selected === score.value
                    ? 'bg-primary-500 border-primary-500 text-white'
                    : 'bg-white border-gray-200 hover:border-primary-400'"
                :aria-label="score.label"
                @click="selected = score.value"
            >
                {{ score.emoji }}
            </button>
        </div>

        <div v-if="selected" class="mt-3 space-y-2">
            <Textarea
                v-model="comment"
                rows="2"
                autoResize
                class="w-full"
                placeholder="Any extra comments? (optional)"
            />
            <div class="flex justify-end">
                <Button
                    type="button"
                    label="Submit rating"
                    :loading="loading"
                    :disabled="loading"
                    @click="submit"
                />
            </div>
        </div>
    </section>
</template>
