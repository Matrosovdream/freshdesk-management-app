import { createListStore } from './createListStore';

const useBase = createListStore('dashboard-roles', '/api/v1/admin/roles');

export const useRoles = () => useBase();
