<script setup>
import { computed } from 'vue';
import Avatar from 'primevue/avatar';

const props = defineProps({
    agent: { type: Object, default: null },
    size: { type: String, default: 'normal' },
    showName: { type: Boolean, default: false },
});

const initials = computed(() => {
    const name = props.agent?.name || props.agent?.email || '';
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((s) => s[0])
        .join('')
        .toUpperCase() || '?';
});
</script>

<template>
    <div class="inline-flex items-center gap-2">
        <Avatar
            v-if="agent?.avatar_url"
            :image="agent.avatar_url"
            shape="circle"
            :size="size"
        />
        <Avatar v-else :label="initials" shape="circle" :size="size" />
        <span v-if="showName" class="text-sm">{{ agent?.name || 'Unassigned' }}</span>
    </div>
</template>
