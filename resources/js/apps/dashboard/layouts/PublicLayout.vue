<script setup>
import FloatingConfigurator from '@/components/FloatingConfigurator.vue';
</script>

<template>
    <FloatingConfigurator />
    <div class="bg-surface-50 dark:bg-surface-950 min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md p-6">
            <router-view />
        </div>
    </div>
    <Toast />
</template>
