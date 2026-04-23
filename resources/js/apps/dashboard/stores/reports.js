import { defineStore } from 'pinia';
import { http, ensureCsrf } from '@shared/http';

export const useReports = defineStore('dashboard-reports', {
    state: () => ({
        backlog: null,
        agentPerformance: null,
        groupPerformance: null,
        slaBreaches: null,
        volume: null,
        csat: null,
        loading: {},
        error: null,
    }),

    actions: {
        async fetch(report, params = {}) {
            this.loading = { ...this.loading, [report]: true };
            try {
                const { data } = await http.get(`/api/v1/admin/reports/${report}`, { params });
                const payload = data?.data ?? data;
                const key = report.replace(/-(\w)/g, (_, c) => c.toUpperCase());
                this[key] = payload;
                return payload;
            } catch (e) {
                this.error = e;
                throw e;
            } finally {
                this.loading = { ...this.loading, [report]: false };
            }
        },

        async export(report, filters = {}) {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/reports/${report}/export`, { filters });
            return data?.data?.download_url ?? data?.download_url;
        },
    },
});
