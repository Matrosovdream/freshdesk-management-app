import { defineStore } from 'pinia';
import { http } from '../../../shared/http';

export const useRequests = defineStore('portal-requests', {
    state: () => ({
        list: [],
        byId: {},
        loadingList: false,
        loadingDetail: false,
        submitting: false,
        cursor: null,
        hasMore: false,
        filters: {
            status: 'all',
            search: '',
            scope: 'own',
        },
    }),

    actions: {
        async load(filters = {}) {
            this.filters = { ...this.filters, ...filters };
            this.loadingList = true;
            this.cursor = null;
            try {
                const params = this._buildParams();
                const { data } = await http.get('/api/v1/portal/requests', { params });
                const items = data?.data ?? data?.items ?? [];
                this.list = items;
                for (const item of items) {
                    this.byId[item.id] = item;
                }
                this.cursor = data?.meta?.next_cursor ?? data?.next_cursor ?? null;
                this.hasMore = !!this.cursor;
            } finally {
                this.loadingList = false;
            }
        },

        async loadNextPage() {
            if (!this.cursor || this.loadingList) return;
            this.loadingList = true;
            try {
                const params = this._buildParams();
                params.cursor = this.cursor;
                const { data } = await http.get('/api/v1/portal/requests', { params });
                const items = data?.data ?? data?.items ?? [];
                this.list = [...this.list, ...items];
                for (const item of items) {
                    this.byId[item.id] = item;
                }
                this.cursor = data?.meta?.next_cursor ?? data?.next_cursor ?? null;
                this.hasMore = !!this.cursor;
            } finally {
                this.loadingList = false;
            }
        },

        async fetch(id) {
            this.loadingDetail = true;
            try {
                const { data } = await http.get(`/api/v1/portal/requests/${id}`);
                const item = data?.data ?? data;
                this.byId[id] = item;
                return item;
            } finally {
                this.loadingDetail = false;
            }
        },

        async submit(payload) {
            this.submitting = true;
            try {
                const isMultipart = payload instanceof FormData;
                const { data } = await http.post(
                    '/api/v1/portal/requests',
                    payload,
                    isMultipart
                        ? { headers: { 'Content-Type': 'multipart/form-data' } }
                        : undefined,
                );
                const item = data?.data ?? data;
                if (item?.id) this.byId[item.id] = item;
                return item;
            } finally {
                this.submitting = false;
            }
        },

        async reply(id, payload) {
            const isMultipart = payload instanceof FormData;
            const { data } = await http.post(
                `/api/v1/portal/requests/${id}/reply`,
                payload,
                isMultipart
                    ? { headers: { 'Content-Type': 'multipart/form-data' } }
                    : undefined,
            );
            return data?.data ?? data;
        },

        async resolve(id) {
            const { data } = await http.post(`/api/v1/portal/requests/${id}/resolve`);
            const item = data?.data ?? data;
            if (item?.id) this.byId[id] = item;
            return item;
        },

        async reopen(id) {
            const { data } = await http.post(`/api/v1/portal/requests/${id}/reopen`);
            const item = data?.data ?? data;
            if (item?.id) this.byId[id] = item;
            return item;
        },

        async rate(id, { score, comment }) {
            const { data } = await http.post(`/api/v1/portal/requests/${id}/rate`, {
                score,
                comment,
            });
            return data?.data ?? data;
        },

        _buildParams() {
            const params = {};
            if (this.filters.status && this.filters.status !== 'all') {
                params.status = this.filters.status;
            }
            if (this.filters.search) {
                params.search = this.filters.search;
            }
            if (this.filters.scope) {
                params.scope = this.filters.scope;
            }
            return params;
        },
    },
});
