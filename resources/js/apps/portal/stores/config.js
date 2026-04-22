import { defineStore } from 'pinia';
import { http } from '../../../shared/http';

export const useConfig = defineStore('portal-config', {
    state: () => ({
        ticketFields: [],
        products: [],
        allowPublicRegistration: false,
        requireCaptcha: false,
        csatOnResolve: true,
        loaded: false,
        loading: false,
    }),

    actions: {
        async load() {
            if (this.loaded || this.loading) return;
            this.loading = true;

            // Fire in parallel — the public config endpoint is the only one
            // that doesn't require auth, so the other two may 401 for guests.
            const results = await Promise.allSettled([
                http.get('/api/v1/portal/config/public', { __silent401: true }),
                http.get('/api/v1/portal/ticket-fields', { __silent401: true }),
                http.get('/api/v1/portal/products', { __silent401: true }),
            ]);

            const [publicCfg, fields, products] = results;

            if (publicCfg.status === 'fulfilled') {
                const body = publicCfg.value.data?.data ?? publicCfg.value.data ?? {};
                this.allowPublicRegistration = !!body.allowPublicRegistration;
                this.requireCaptcha = !!body.requireCaptcha;
                this.csatOnResolve = body.csatOnResolve !== false;
            }

            if (fields.status === 'fulfilled') {
                this.ticketFields = fields.value.data?.data ?? fields.value.data ?? [];
            }

            if (products.status === 'fulfilled') {
                this.products = products.value.data?.data ?? products.value.data ?? [];
            }

            this.loaded = true;
            this.loading = false;
        },

        async reload() {
            this.loaded = false;
            await this.load();
        },
    },
});
