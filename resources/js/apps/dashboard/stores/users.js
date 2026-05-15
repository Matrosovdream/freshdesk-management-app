import { createListStore } from './createListStore';

const useBase = createListStore('dashboard-users', '/api/v1/admin/system/users');

export const useUsers = () => useBase();
