<script setup>
defineProps({
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <div class="mx-auto mb-3 h-10 w-10 rounded-full bg-primary-500 flex items-center justify-center text-white font-semibold">
                    H
                </div>
                <h1 class="text-xl font-semibold text-gray-800">Helpdesk</h1>
                <p v-if="subtitle" class="text-sm text-gray-500 mt-1">{{ subtitle }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <slot />
            </div>
            <p class="text-center text-xs text-gray-400 mt-6">
                &copy; {{ new Date().getFullYear() }} Helpdesk
            </p>
        </div>
    </div>
</template>
