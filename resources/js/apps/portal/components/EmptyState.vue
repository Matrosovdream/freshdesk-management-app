<script setup>
defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    icon: { type: String, default: 'pi pi-inbox' },
});
</script>

<template>
    <div class="text-center py-10 px-4 bg-white border border-dashed border-gray-200 rounded-lg">
        <div class="flex justify-center mb-3">
            <slot name="illustration">
                <i :class="[icon, 'text-3xl text-gray-300']" aria-hidden="true" />
            </slot>
        </div>
        <h3 v-if="title" class="font-medium text-gray-800">{{ title }}</h3>
        <p v-if="description" class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">
            {{ description }}
        </p>
        <div v-if="$slots.cta" class="mt-4 flex justify-center">
            <slot name="cta" />
        </div>
    </div>
</template>
