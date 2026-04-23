<script setup>
import { computed } from 'vue';
import { useAuth } from '@/stores/auth';
import AppMenuItem from './AppMenuItem.vue';

const auth = useAuth();

const model = computed(() => {
    const can = (r) => auth.can(r);
    const showForManager = !auth.isManagerOnly; // used to hide admin-only sections

    const menu = [
        {
            label: 'Home',
            items: [
                { label: 'Overview', icon: 'pi pi-fw pi-home', to: '/' },
            ],
        },
        {
            label: 'Workspace',
            items: [
                can('tickets.view')   && { label: 'Tickets',   icon: 'pi pi-fw pi-ticket',   to: '/tickets' },
                can('contacts.view')  && { label: 'Contacts',  icon: 'pi pi-fw pi-users',    to: '/contacts' },
                can('companies.view') && { label: 'Companies', icon: 'pi pi-fw pi-building', to: '/companies' },
                can('agents.view')    && { label: 'Agents',    icon: 'pi pi-fw pi-id-card',  to: '/agents' },
                can('groups.view') && showForManager && { label: 'Groups', icon: 'pi pi-fw pi-sitemap', to: '/groups' },
            ].filter(Boolean),
        },
        can('reports.view') && {
            label: 'Reports',
            items: [
                { label: 'Backlog',           icon: 'pi pi-fw pi-inbox',       to: '/reports/backlog' },
                { label: 'Agent performance', icon: 'pi pi-fw pi-user',        to: '/reports/agent-performance' },
                { label: 'Group performance', icon: 'pi pi-fw pi-users',       to: '/reports/group-performance' },
                { label: 'SLA breaches',      icon: 'pi pi-fw pi-exclamation-triangle', to: '/reports/sla-breaches' },
                { label: 'Volume',            icon: 'pi pi-fw pi-chart-line',  to: '/reports/volume' },
                { label: 'CSAT',              icon: 'pi pi-fw pi-star',        to: '/reports/csat' },
            ],
        },
        can('audit.view') && showForManager && {
            label: 'Audit',
            items: [
                { label: 'Audit log', icon: 'pi pi-fw pi-history', to: '/audit-log' },
            ],
        },
    ].filter(Boolean);

    if (auth.isSuperadmin) {
        const systemItems = [
            can('system.freshdesk.view')  && { label: 'Freshdesk',  icon: 'pi pi-fw pi-link',      to: '/system/freshdesk' },
            can('system.managers.view')   && { label: 'Managers',   icon: 'pi pi-fw pi-user-edit', to: '/system/managers' },
            can('system.sync_jobs.view')  && { label: 'Sync jobs',  icon: 'pi pi-fw pi-sync',      to: '/system/sync-jobs' },
            can('system.settings.view')   && { label: 'Settings',   icon: 'pi pi-fw pi-cog',       to: '/system/settings' },
            can('system.api_keys.view')   && { label: 'API keys',   icon: 'pi pi-fw pi-key',       to: '/system/api-keys' },
        ].filter(Boolean);
        if (systemItems.length) menu.push({ label: 'System', items: systemItems });
    }

    return menu;
});
</script>

<template>
    <ul class="layout-menu">
        <li v-if="auth.isManagerOnly && auth.assignedGroups?.length" class="px-4 py-2 text-xs text-surface-500">
            <div class="font-medium">Your groups</div>
            <div class="mt-1 flex flex-wrap gap-1">
                <span v-for="g in auth.assignedGroups" :key="g.id" class="px-2 py-0.5 rounded-full bg-surface-200 dark:bg-surface-800 text-[11px]">{{ g.name }}</span>
            </div>
        </li>
        <template v-for="(item, i) in model" :key="item.label">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
