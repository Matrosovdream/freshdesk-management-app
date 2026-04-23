import { defineStore } from 'pinia';

export const useUi = defineStore('dashboard-ui', {
    state: () => ({
        toasts: [],
        rateLimit: null,
        globalLoading: false,
    }),

    actions: {
        pushToast(toast) {
            this.toasts.push({ id: Date.now() + Math.random(), life: 3500, ...toast });
        },
        clearToasts() {
            this.toasts = [];
        },
        setRateLimit(info) {
            this.rateLimit = { ...info, updatedAt: Date.now() };
        },
        setGlobalLoading(v) {
            this.globalLoading = !!v;
        },
    },
});
