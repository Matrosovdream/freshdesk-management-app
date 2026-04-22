<script setup>
import { computed } from 'vue';
import StatusChip from './StatusChip.vue';

const props = defineProps({
    request: { type: Object, required: true },
});

const MINUTE = 60;
const HOUR = 3600;
const DAY = 86400;

const relativeTime = computed(() => {
    const ts = props.request.updated_at || props.request.created_at;
    if (!ts) return '';
    const diff = Math.max(1, Math.floor((Date.now() - new Date(ts).getTime()) / 1000));
    if (diff < MINUTE) return `${diff}s ago`;
    if (diff < HOUR) return `${Math.floor(diff / MINUTE)}m ago`;
    if (diff < DAY) return `${Math.floor(diff / HOUR)}h ago`;
    if (diff < DAY * 30) return `${Math.floor(diff / DAY)}d ago`;
    return new Date(ts).toLocaleDateString();
});

const preview = computed(() => {
    const src = props.request.last_message_preview || props.request.description_preview || props.request.description || '';
    const text = String(src).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    return text.length > 120 ? `${text.slice(0, 120)}…` : text;
});
</script>

<template>
    <router-link
        :to="{ name: 'portal.requests.show', params: { id: request.id } }"
        class="block rounded-lg border border-gray-200 bg-white p-4 hover:border-primary-300 hover:shadow-sm transition-colors"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <h3 class="font-medium text-gray-800 truncate">
                        {{ request.subject || 'Untitled request' }}
                    </h3>
                    <span
                        v-if="request.unread"
                        class="inline-block h-2 w-2 rounded-full bg-primary-500"
                        aria-label="Unread"
                    />
                </div>
                <p v-if="preview" class="text-sm text-gray-500 mt-1 line-clamp-2">
                    {{ preview }}
                </p>
            </div>
            <div class="flex flex-col items-end gap-1 shrink-0">
                <StatusChip :status="request.status" />
                <span class="text-xs text-gray-400">{{ relativeTime }}</span>
            </div>
        </div>
    </router-link>
</template>
