<script setup>
import { useAuth } from '@/stores/auth';
const auth = useAuth();
</script>

<template>
    <div v-if="auth.isManagerOnly" class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-surface-500">Showing data for your groups:</span>
        <template v-if="auth.assignedGroups?.length">
            <span v-for="g in auth.assignedGroups" :key="g.id" class="px-2 py-0.5 rounded-full text-xs bg-primary/10 text-primary">
                {{ g.name }}
            </span>
        </template>
        <span v-else class="text-xs text-amber-600">
            No groups assigned — ask an admin to assign you to a group.
        </span>
    </div>
</template>
