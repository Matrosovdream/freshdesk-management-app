import { createListStore } from './createListStore';
import { http, ensureCsrf } from '@shared/http';

const useBase = createListStore('dashboard-contacts', '/api/v1/admin/contacts');

export const useContacts = () => {
    const store = useBase();

    if (!store._extended) {
        store._extended = true;

        store.hardDelete = async (id) => {
            await ensureCsrf();
            await http.delete(`/api/v1/admin/contacts/${id}/hard-delete`);
            store.items = store.items.filter((c) => c.id !== id);
        };

        store.restore = async (id) => {
            await ensureCsrf();
            await http.post(`/api/v1/admin/contacts/${id}/restore`);
            await store.refresh();
        };

        store.sendInvite = async (id) => {
            await ensureCsrf();
            await http.post(`/api/v1/admin/contacts/${id}/send-invite`);
        };

        store.makeAgent = async (id, payload = {}) => {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/contacts/${id}/make-agent`, payload);
            return data?.data ?? data;
        };

        store.merge = async (primaryId, secondaryIds) => {
            await ensureCsrf();
            const { data } = await http.post('/api/v1/admin/contacts/merge', {
                primary_id: primaryId,
                secondary_ids: secondaryIds,
            });
            return data?.data ?? data;
        };

        store.importCsv = async (file, mapping = {}) => {
            await ensureCsrf();
            const form = new FormData();
            form.append('file', file);
            if (mapping) form.append('mapping', JSON.stringify(mapping));
            const { data } = await http.post('/api/v1/admin/contacts/import', form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            return data?.data ?? data;
        };

        store.exportCsv = async (filters = {}) => {
            await ensureCsrf();
            const { data } = await http.post('/api/v1/admin/contacts/export', { filters });
            return data?.data?.download_url ?? data?.download_url;
        };
    }

    return store;
};
