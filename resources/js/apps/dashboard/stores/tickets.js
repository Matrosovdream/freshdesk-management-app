import { createListStore } from './createListStore';
import { http, ensureCsrf } from '@shared/http';

const useBase = createListStore('dashboard-tickets', '/api/v1/admin/tickets');

export const useTickets = () => {
    const store = useBase();

    if (!store._extended) {
        store._extended = true;

        store.bulkUpdate = async (ids, properties) => {
            await ensureCsrf();
            const { data } = await http.post('/api/v1/admin/tickets/bulk-update', { ids, properties });
            await store.refresh();
            return data;
        };

        store.bulkDelete = async (ids) => {
            await ensureCsrf();
            await http.post('/api/v1/admin/tickets/bulk-delete', { ids });
            store.items = store.items.filter((t) => !ids.includes(t.id));
            store.clearSelection();
        };

        store.merge = async (primaryId, secondaryIds) => {
            await ensureCsrf();
            const { data } = await http.post('/api/v1/admin/tickets/merge', {
                primary_id: primaryId,
                secondary_ids: secondaryIds,
            });
            await store.refresh();
            return data?.data ?? data;
        };

        store.forward = async (id, payload) => {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/tickets/${id}/forward`, payload);
            return data?.data ?? data;
        };

        store.outboundEmail = async (payload) => {
            await ensureCsrf();
            const { data } = await http.post('/api/v1/admin/tickets/outbound-email', payload);
            return data?.data ?? data;
        };

        store.assign = async (id, agentId) => {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/tickets/${id}/assign`, { responder_id: agentId });
            return data?.data ?? data;
        };

        store.restore = async (id) => {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/tickets/${id}/restore`);
            return data?.data ?? data;
        };

        store.reply = async (id, payload) => {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/tickets/${id}/reply`, payload);
            return data?.data ?? data;
        };

        store.note = async (id, payload) => {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/tickets/${id}/note`, payload);
            return data?.data ?? data;
        };

        store.conversations = async (id) => {
            const { data } = await http.get(`/api/v1/admin/tickets/${id}/conversations`);
            return data?.data ?? data ?? [];
        };

        store.timeEntries = async (id) => {
            const { data } = await http.get(`/api/v1/admin/tickets/${id}/time-entries`);
            return data?.data ?? data ?? [];
        };

        store.addTimeEntry = async (id, payload) => {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/tickets/${id}/time-entries`, payload);
            return data?.data ?? data;
        };

        store.updateTimeEntry = async (entryId, patch) => {
            await ensureCsrf();
            const { data } = await http.put(`/api/v1/admin/time-entries/${entryId}`, patch);
            return data?.data ?? data;
        };

        store.deleteConversation = async (convId) => {
            await ensureCsrf();
            await http.delete(`/api/v1/admin/conversations/${convId}`);
        };
    }

    return store;
};
