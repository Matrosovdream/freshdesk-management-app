import { createRouter, createWebHistory } from 'vue-router';

import AppLayout from '@/layout/AppLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

import { useAuth } from '@/stores/auth';
import { useUi } from '@/stores/ui';

// Auth
import LoginPage from '@/pages/auth/LoginPage.vue';
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage.vue';
import ResetPasswordPage from '@/pages/auth/ResetPasswordPage.vue';

// Authenticated
import OverviewPage from '@/pages/OverviewPage.vue';
import TicketListPage from '@/pages/tickets/TicketListPage.vue';
import NewTicketPage from '@/pages/tickets/NewTicketPage.vue';
import TicketDetailPage from '@/pages/tickets/TicketDetailPage.vue';
import ContactListPage from '@/pages/contacts/ContactListPage.vue';
import NewContactPage from '@/pages/contacts/NewContactPage.vue';
import ContactDetailPage from '@/pages/contacts/ContactDetailPage.vue';
import CompanyListPage from '@/pages/companies/CompanyListPage.vue';
import NewCompanyPage from '@/pages/companies/NewCompanyPage.vue';
import CompanyDetailPage from '@/pages/companies/CompanyDetailPage.vue';
import AgentListPage from '@/pages/agents/AgentListPage.vue';
import NewAgentPage from '@/pages/agents/NewAgentPage.vue';
import AgentDetailPage from '@/pages/agents/AgentDetailPage.vue';
import GroupListPage from '@/pages/groups/GroupListPage.vue';
import BacklogReportPage from '@/pages/reports/BacklogReportPage.vue';
import AgentPerformancePage from '@/pages/reports/AgentPerformancePage.vue';
import GroupPerformancePage from '@/pages/reports/GroupPerformancePage.vue';
import SlaBreachReportPage from '@/pages/reports/SlaBreachReportPage.vue';
import VolumeReportPage from '@/pages/reports/VolumeReportPage.vue';
import CsatReportPage from '@/pages/reports/CsatReportPage.vue';
import AuditLogPage from '@/pages/AuditLogPage.vue';
import FreshdeskConnectionPage from '@/pages/system/FreshdeskConnectionPage.vue';
import ManagersPage from '@/pages/system/ManagersPage.vue';
import SyncJobsPage from '@/pages/system/SyncJobsPage.vue';
import SettingsPage from '@/pages/system/SettingsPage.vue';
import ApiKeysPage from '@/pages/system/ApiKeysPage.vue';
import ProfilePage from '@/pages/ProfilePage.vue';

const routes = [
    {
        path: '/',
        component: AppLayout,
        children: [
            { path: '', name: 'dashboard.overview', component: OverviewPage, meta: { auth: true } },

            { path: 'tickets', name: 'dashboard.tickets', component: TicketListPage, meta: { auth: true, right: 'tickets.view' } },
            { path: 'tickets/new', name: 'dashboard.tickets.new', component: NewTicketPage, meta: { auth: true, right: 'tickets.create' } },
            { path: 'tickets/:id', name: 'dashboard.tickets.show', component: TicketDetailPage, meta: { auth: true, right: 'tickets.view' }, props: true },

            { path: 'contacts', name: 'dashboard.contacts', component: ContactListPage, meta: { auth: true, right: 'contacts.view' } },
            { path: 'contacts/new', name: 'dashboard.contacts.new', component: NewContactPage, meta: { auth: true, right: 'contacts.create' } },
            { path: 'contacts/:id', name: 'dashboard.contacts.show', component: ContactDetailPage, meta: { auth: true, right: 'contacts.view' }, props: true },

            { path: 'companies', name: 'dashboard.companies', component: CompanyListPage, meta: { auth: true, right: 'companies.view' } },
            { path: 'companies/new', name: 'dashboard.companies.new', component: NewCompanyPage, meta: { auth: true, right: 'companies.create' } },
            { path: 'companies/:id', name: 'dashboard.companies.show', component: CompanyDetailPage, meta: { auth: true, right: 'companies.view' }, props: true },

            { path: 'agents', name: 'dashboard.agents', component: AgentListPage, meta: { auth: true, right: 'agents.view' } },
            { path: 'agents/new', name: 'dashboard.agents.new', component: NewAgentPage, meta: { auth: true, right: 'agents.create' } },
            { path: 'agents/:id', name: 'dashboard.agents.show', component: AgentDetailPage, meta: { auth: true, right: 'agents.view' }, props: true },

            { path: 'groups', name: 'dashboard.groups', component: GroupListPage, meta: { auth: true, right: 'groups.view' } },

            { path: 'reports/backlog', name: 'dashboard.reports.backlog', component: BacklogReportPage, meta: { auth: true, right: 'reports.view' } },
            { path: 'reports/agent-performance', name: 'dashboard.reports.agents', component: AgentPerformancePage, meta: { auth: true, right: 'reports.view' } },
            { path: 'reports/group-performance', name: 'dashboard.reports.groups', component: GroupPerformancePage, meta: { auth: true, right: 'reports.view' } },
            { path: 'reports/sla-breaches', name: 'dashboard.reports.sla', component: SlaBreachReportPage, meta: { auth: true, right: 'reports.view' } },
            { path: 'reports/volume', name: 'dashboard.reports.volume', component: VolumeReportPage, meta: { auth: true, right: 'reports.view' } },
            { path: 'reports/csat', name: 'dashboard.reports.csat', component: CsatReportPage, meta: { auth: true, right: 'reports.view' } },

            { path: 'audit-log', name: 'dashboard.audit', component: AuditLogPage, meta: { auth: true, right: 'audit.view' } },

            { path: 'system/freshdesk', name: 'dashboard.system.freshdesk', component: FreshdeskConnectionPage, meta: { auth: true, role: 'superadmin', right: 'system.freshdesk.view' } },
            { path: 'system/managers', name: 'dashboard.system.managers', component: ManagersPage, meta: { auth: true, role: 'superadmin', right: 'system.managers.view' } },
            { path: 'system/sync-jobs', name: 'dashboard.system.sync', component: SyncJobsPage, meta: { auth: true, role: 'superadmin', right: 'system.sync_jobs.view' } },
            { path: 'system/settings', name: 'dashboard.system.settings', component: SettingsPage, meta: { auth: true, role: 'superadmin', right: 'system.settings.view' } },
            { path: 'system/api-keys', name: 'dashboard.system.api-keys', component: ApiKeysPage, meta: { auth: true, role: 'superadmin', right: 'system.api_keys.view' } },

            { path: 'profile', name: 'dashboard.profile', component: ProfilePage, meta: { auth: true } },
        ],
    },
    {
        path: '/',
        component: PublicLayout,
        children: [
            { path: 'login', name: 'dashboard.login', component: LoginPage, meta: { guest: true } },
            { path: 'forgot', name: 'dashboard.forgot', component: ForgotPasswordPage, meta: { guest: true } },
            { path: 'reset', name: 'dashboard.reset', component: ResetPasswordPage, meta: { guest: true } },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: { name: 'dashboard.overview' },
    },
];

const router = createRouter({
    history: createWebHistory('/dashboard'),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuth();
    const ui = useUi();

    if (!auth.bootstrapped) {
        try {
            await auth.bootstrap();
        } catch {
            // swallow — guard still makes a decision
        }
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        const redirect = encodeURIComponent('/dashboard' + to.fullPath);
        window.location.assign(`/portal/login?redirect=${redirect}`);
        return false;
    }

    if (to.meta.guest) {
        const redirect = encodeURIComponent(
            typeof to.query.redirect === 'string' && to.query.redirect.startsWith('/dashboard')
                ? to.query.redirect
                : '/dashboard',
        );
        window.location.assign(`/portal/login?redirect=${redirect}`);
        return false;
    }

    if (to.meta.role && !auth.hasRole(to.meta.role)) {
        ui.pushToast({ severity: 'warn', summary: 'You do not have access to this page.' });
        return { name: 'dashboard.overview' };
    }

    if (to.meta.right && !auth.can(to.meta.right)) {
        ui.pushToast({ severity: 'warn', summary: 'You do not have permission to view this page.' });
        return { name: 'dashboard.overview' };
    }

    return true;
});

export default router;
