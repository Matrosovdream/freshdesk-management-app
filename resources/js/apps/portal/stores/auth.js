import { defineStore } from 'pinia';
import { http, ensureCsrf } from '../../../shared/http';

export const useAuth = defineStore('portal-auth', {
    state: () => ({
        user: null,
        loading: false,
        error: null,
        bootstrapped: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        firstName: (state) => {
            if (!state.user?.name) return '';
            return state.user.name.trim().split(/\s+/)[0] || '';
        },
        canViewCompanyTickets: (state) => !!state.user?.can_view_company_tickets,
    },

    actions: {
        async bootstrap() {
            if (this.bootstrapped) return;
            this.loading = true;
            try {
                await ensureCsrf();
                const { data } = await http.get('/api/v1/portal/auth/me', {
                    __silent401: true,
                });
                this.user = data?.data ?? data ?? null;
            } catch (e) {
                if (e?.response?.status !== 401) {
                    this.error = e;
                }
                this.user = null;
            } finally {
                this.bootstrapped = true;
                this.loading = false;
            }
        },

        async login({ email, password }) {
            this.loading = true;
            this.error = null;
            try {
                await ensureCsrf();
                await http.post('/api/v1/portal/auth/login', { email, password });
                const { data } = await http.get('/api/v1/portal/auth/me');
                this.user = data?.data ?? data ?? null;
                return this.user;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await http.post('/api/v1/portal/auth/logout');
            } catch {
                // ignore — we're wiping local state either way
            }
            this.user = null;
        },

        async magicLinkSend(email) {
            await ensureCsrf();
            await http.post('/api/v1/portal/auth/magic-link', { email });
        },

        async magicLinkConsume(token) {
            await ensureCsrf();
            await http.post('/api/v1/portal/auth/magic-link/consume', { token });
            const { data } = await http.get('/api/v1/portal/auth/me');
            this.user = data?.data ?? data ?? null;
            return this.user;
        },

        async forgot(email) {
            await ensureCsrf();
            await http.post('/api/v1/portal/auth/forgot', { email });
        },

        async reset(payload) {
            await ensureCsrf();
            await http.post('/api/v1/portal/auth/reset', payload);
            // Server auto-logs-in; fetch the current user.
            const { data } = await http.get('/api/v1/portal/auth/me');
            this.user = data?.data ?? data ?? null;
        },

        async verify(token) {
            await ensureCsrf();
            await http.post('/api/v1/portal/auth/verify', { token });
            try {
                const { data } = await http.get('/api/v1/portal/auth/me');
                this.user = data?.data ?? data ?? null;
            } catch {
                // If verify doesn't auto-login yet, that's fine.
            }
        },

        async logoutOthers() {
            await http.post('/api/v1/portal/auth/logout-others');
        },

        setUser(user) {
            this.user = user;
        },
    },
});
