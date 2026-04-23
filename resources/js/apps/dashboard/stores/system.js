import { defineStore } from 'pinia';
import { http, ensureCsrf } from '@shared/http';

export const useSystem = defineStore('dashboard-system', {
    state: () => ({
        freshdesk: null,
        settings: [],
        apiKeys: [],
        syncJobs: [],
        syncIntervals: {},
        loading: false,
        error: null,
    }),

    actions: {
        async fetchFreshdesk() {
            const { data } = await http.get('/api/v1/admin/system/freshdesk');
            this.freshdesk = data?.data ?? data ?? null;
            return this.freshdesk;
        },

        async updateFreshdesk(payload) {
            await ensureCsrf();
            const { data } = await http.put('/api/v1/admin/system/freshdesk', payload);
            this.freshdesk = data?.data ?? data ?? this.freshdesk;
            return this.freshdesk;
        },

        async testFreshdesk() {
            await ensureCsrf();
            const { data } = await http.post('/api/v1/admin/system/freshdesk/test');
            return data?.data ?? data;
        },

        async clearMirror() {
            await ensureCsrf();
            await http.post('/api/v1/admin/system/freshdesk/clear-mirror', { confirm: 'CLEAR' });
        },

        async fetchSettings() {
            const { data } = await http.get('/api/v1/admin/system/settings');
            this.settings = data?.data ?? data ?? [];
            return this.settings;
        },

        async updateSettings(updates) {
            await ensureCsrf();
            const { data } = await http.put('/api/v1/admin/system/settings', { updates });
            return data?.data ?? data;
        },

        async fetchApiKeys() {
            const { data } = await http.get('/api/v1/admin/system/api-keys');
            this.apiKeys = data?.data ?? data ?? [];
            return this.apiKeys;
        },

        async createApiKey(payload) {
            await ensureCsrf();
            const { data } = await http.post('/api/v1/admin/system/api-keys', payload);
            return data?.data ?? data; // includes one-time plaintext key
        },

        async rotateApiKey(id) {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/system/api-keys/${id}/rotate`);
            return data?.data ?? data;
        },

        async revokeApiKey(id) {
            await ensureCsrf();
            await http.post(`/api/v1/admin/system/api-keys/${id}/revoke`);
            this.apiKeys = this.apiKeys.map((k) => (k.id === id ? { ...k, status: 'revoked' } : k));
        },

        async fetchSyncJobs(params = {}) {
            const { data } = await http.get('/api/v1/admin/system/sync-jobs', { params });
            this.syncJobs = data?.data?.jobs ?? data?.data ?? data ?? [];
            this.syncIntervals = data?.data?.intervals ?? {};
            return this.syncJobs;
        },

        async runSync(resource) {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/system/sync-jobs/${resource}/run`);
            return data?.data ?? data;
        },

        async fullResync() {
            await ensureCsrf();
            const { data } = await http.post('/api/v1/admin/system/sync-jobs/full-resync', { confirm: 'RESYNC' });
            return data?.data ?? data;
        },
    },
});
