import { createListStore } from './createListStore';
import { http, ensureCsrf } from '@shared/http';

const useBase = createListStore('dashboard-managers', '/api/v1/admin/system/managers');

export const useManagers = () => {
    const store = useBase();

    if (!store._extended) {
        store._extended = true;

        store.setScope = async (id, groupIds) => {
            await ensureCsrf();
            const { data } = await http.post(`/api/v1/admin/system/managers/${id}/scope`, {
                group_ids: groupIds,
            });
            return data?.data ?? data;
        };
    }

    return store;
};
