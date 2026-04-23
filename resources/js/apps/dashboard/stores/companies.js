import { createListStore } from './createListStore';
import { http, ensureCsrf } from '@shared/http';

const useBase = createListStore('dashboard-companies', '/api/v1/admin/companies');

export const useCompanies = () => {
    const store = useBase();

    if (!store._extended) {
        store._extended = true;

        store.importCsv = async (file) => {
            await ensureCsrf();
            const form = new FormData();
            form.append('file', file);
            const { data } = await http.post('/api/v1/admin/companies/import', form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            return data?.data ?? data;
        };

        store.exportCsv = async (filters = {}) => {
            await ensureCsrf();
            const { data } = await http.post('/api/v1/admin/companies/export', { filters });
            return data?.data?.download_url ?? data?.download_url;
        };
    }

    return store;
};
