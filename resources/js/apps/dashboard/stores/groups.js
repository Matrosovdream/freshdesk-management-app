import { createListStore } from './createListStore';

const useBase = createListStore('dashboard-groups', '/api/v1/admin/groups');

export const useGroups = () => useBase();
