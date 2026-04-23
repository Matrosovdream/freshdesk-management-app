import { defineStore } from 'pinia';
import { http } from '@shared/http';

const CACHE_TTL_MS = 10 * 60 * 1000;

export const useConfig = defineStore('dashboard-config', {
    state: () => ({
        ticketFields: [],
        products: [],
        businessHours: [],
        slaPolicies: [],
        automations: [],
        roles: [],
        loadedAt: 0,
        loading: false,
        error: null,
    }),

    getters: {
        stale: (state) => !state.loadedAt || Date.now() - state.loadedAt > CACHE_TTL_MS,
    },

    actions: {
        async load(force = false) {
            if (this.loading) return;
            if (!force && !this.stale) return;
            this.loading = true;
            this.error = null;
            try {
                const results = await Promise.allSettled([
                    http.get('/api/v1/admin/ticket-fields', { __silent401: true }),
                    http.get('/api/v1/admin/products', { __silent401: true }),
                    http.get('/api/v1/admin/business-hours', { __silent401: true }),
                    http.get('/api/v1/admin/sla-policies', { __silent401: true }),
                    http.get('/api/v1/admin/automations', { __silent401: true }),
                    http.get('/api/v1/admin/roles', { __silent401: true }),
                ]);
                const pick = (i) => (results[i].status === 'fulfilled' ? results[i].value?.data?.data ?? results[i].value?.data ?? [] : []);
                this.ticketFields = pick(0);
                this.products = pick(1);
                this.businessHours = pick(2);
                this.slaPolicies = pick(3);
                this.automations = pick(4);
                this.roles = pick(5);
                this.loadedAt = Date.now();
            } catch (e) {
                this.error = e;
            } finally {
                this.loading = false;
            }
        },
    },
});
