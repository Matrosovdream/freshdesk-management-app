<script setup>
const props = defineProps({
    count: { type: Number, default: 0 },
});
const emit = defineEmits(['clear']);
</script>

<template>
    <Transition
        enter-active-class="transition-transform duration-150"
        enter-from-class="translate-y-full"
        leave-active-class="transition-transform duration-150"
        leave-to-class="translate-y-full"
    >
        <div
            v-if="count > 0"
            class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 shadow-lg rounded-full px-6 py-3 flex items-center gap-4"
        >
            <span class="font-medium">{{ count }} selected</span>
            <div class="h-5 w-px bg-surface-300 dark:bg-surface-700"></div>
            <slot />
            <Button icon="pi pi-times" rounded text @click="emit('clear')" aria-label="Clear selection" />
        </div>
    </Transition>
</template>
