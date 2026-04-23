import { defineStore } from 'pinia';
import { http } from '@shared/http';

export const useAudit = defineStore('dashboard-audit', {
    state: () => ({
        items: [],
        filters: {},
        cursor: null,
        hasMore: false,
        loading: false,
        error: null,
    }),

    actions: {
        async fetch(filters = {}) {
            this.loading = true;
            this.error = null;
            this.filters = { ...this.filters, ...filters };
            try {
                const { data } = await http.get('/api/v1/admin/audit-log', { params: this.filters });
                this.items = data?.data ?? data ?? [];
                this.cursor = data?.meta?.next_cursor ?? null;
                this.hasMore = !!this.cursor;
            } catch (e) {
                this.error = e;
            } finally {
                this.loading = false;
            }
        },

        async fetchNextPage() {
            if (!this.hasMore || this.loading) return;
            this.loading = true;
            try {
                const { data } = await http.get('/api/v1/admin/audit-log', {
                    params: { ...this.filters, cursor: this.cursor },
                });
                this.items = [...this.items, ...(data?.data ?? [])];
                this.cursor = data?.meta?.next_cursor ?? null;
                this.hasMore = !!this.cursor;
            } finally {
                this.loading = false;
            }
        },
    },
});
