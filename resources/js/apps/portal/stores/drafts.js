import { defineStore } from 'pinia';
import { http } from '../../../shared/http';

let debounceHandle = null;

export const useDrafts = defineStore('portal-drafts', {
    state: () => ({
        current: null,
        loading: false,
        saving: false,
    }),

    actions: {
        async load() {
            this.loading = true;
            try {
                const { data } = await http.get('/api/v1/portal/drafts');
                this.current = data?.data ?? data ?? null;
                return this.current;
            } catch (e) {
                if (e?.response?.status === 404) {
                    this.current = null;
                    return null;
                }
                throw e;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Debounced save. Fires 2000ms after the last call.
         */
        save(payload) {
            if (debounceHandle) {
                clearTimeout(debounceHandle);
            }
            return new Promise((resolve, reject) => {
                debounceHandle = setTimeout(async () => {
                    this.saving = true;
                    try {
                        const { data } = await http.post('/api/v1/portal/drafts', payload);
                        this.current = data?.data ?? data ?? payload;
                        resolve(this.current);
                    } catch (e) {
                        reject(e);
                    } finally {
                        this.saving = false;
                    }
                }, 2000);
            });
        },

        async saveNow(payload) {
            if (debounceHandle) {
                clearTimeout(debounceHandle);
                debounceHandle = null;
            }
            this.saving = true;
            try {
                const { data } = await http.post('/api/v1/portal/drafts', payload);
                this.current = data?.data ?? data ?? payload;
                return this.current;
            } finally {
                this.saving = false;
            }
        },

        async clear() {
            if (debounceHandle) {
                clearTimeout(debounceHandle);
                debounceHandle = null;
            }
            try {
                await http.delete('/api/v1/portal/drafts');
            } catch {
                // best-effort — user probably just finished submitting
            }
            this.current = null;
        },
    },
});
