<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

import PublicLayout from './layouts/PublicLayout.vue';
import AppLayout from './layouts/AppLayout.vue';

const route = useRoute();

const LayoutComponent = computed(() => {
    const name = route.meta?.layout || 'app';
    return name === 'public' ? PublicLayout : AppLayout;
});
</script>

<template>
    <component :is="LayoutComponent">
        <router-view />
    </component>
    <Toast position="top-right" />
    <ConfirmDialog />
</template>
