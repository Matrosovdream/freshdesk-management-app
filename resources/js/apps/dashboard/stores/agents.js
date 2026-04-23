import { createListStore } from './createListStore';
import { http, ensureCsrf } from '@shared/http';

const useBase = createListStore('dashboard-agents', '/api/v1/admin/agents');

export const useAgents = () => {
    const store = useBase();

    if (!store._extended) {
        store._extended = true;

        store.bulkCreate = async (file) => {
            await ensureCsrf();
            const form = new FormData();
            form.append('file', file);
            const { data } = await http.post('/api/v1/admin/agents/bulk', form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            return data?.data ?? data;
        };

        store.autocomplete = async (q) => {
            const { data } = await http.get('/api/v1/admin/agents', { params: { autocomplete: q } });
            return data?.data ?? data ?? [];
        };
    }

    return store;
};
