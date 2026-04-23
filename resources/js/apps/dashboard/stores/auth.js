import { defineStore } from 'pinia';
import { http, ensureCsrf } from '@shared/http';

export const useAuth = defineStore('dashboard-auth', {
    state: () => ({
        user: null,
        loading: false,
        error: null,
        bootstrapped: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        rights: (state) => state.user?.rights ?? [],
        roles: (state) => state.user?.roles ?? [],
        isSuperadmin() {
            return this.hasRole('superadmin');
        },
        isManagerOnly() {
            return this.hasRole('manager') && !this.hasRole('superadmin');
        },
        assignedGroups: (state) => state.user?.assigned_groups ?? state.user?.manager_groups ?? [],
    },

    actions: {
        async bootstrap() {
            if (this.bootstrapped) return;
            this.loading = true;
            try {
                await ensureCsrf();
                const { data } = await http.get('/api/v1/admin/auth/me', { __silent401: true });
                this.user = data?.data ?? data ?? null;
            } catch (e) {
                if (e?.response?.status !== 401) this.error = e;
                this.user = null;
            } finally {
                this.bootstrapped = true;
                this.loading = false;
            }
        },

        async login(payload) {
            this.loading = true;
            this.error = null;
            try {
                await ensureCsrf();
                await http.post('/api/v1/admin/auth/login', payload);
                const { data } = await http.get('/api/v1/admin/auth/me');
                this.user = data?.data ?? data ?? null;
                return this.user;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await http.post('/api/v1/admin/auth/logout');
            } catch {
                // ignore
            }
            this.user = null;
        },

        async forgot(email) {
            await ensureCsrf();
            await http.post('/api/v1/admin/auth/forgot', { email });
        },

        async reset(payload) {
            await ensureCsrf();
            await http.post('/api/v1/admin/auth/reset', payload);
            const { data } = await http.get('/api/v1/admin/auth/me');
            this.user = data?.data ?? data ?? null;
        },

        can(right) {
            if (!this.user) return false;
            if (this.hasRole('superadmin')) return true;
            return this.rights.includes(right);
        },

        hasRole(slug) {
            return this.roles.some((r) => r === slug || r?.slug === slug || r?.name === slug);
        },
    },
});
