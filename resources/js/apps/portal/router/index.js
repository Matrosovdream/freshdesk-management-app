import { createRouter, createWebHistory } from 'vue-router';

import LoginPage from '../pages/auth/LoginPage.vue';
import RegisterPage from '../pages/auth/RegisterPage.vue';
import ForgotPasswordPage from '../pages/auth/ForgotPasswordPage.vue';
import ResetPasswordPage from '../pages/auth/ResetPasswordPage.vue';
import VerifyEmailPage from '../pages/auth/VerifyEmailPage.vue';
import MagicLinkPage from '../pages/auth/MagicLinkPage.vue';

import HomePage from '../pages/HomePage.vue';
import RequestListPage from '../pages/requests/RequestListPage.vue';
import NewRequestPage from '../pages/requests/NewRequestPage.vue';
import RequestDetailPage from '../pages/requests/RequestDetailPage.vue';
import ProfilePage from '../pages/ProfilePage.vue';

import { useAuth } from '../stores/auth';
import { useConfig } from '../stores/config';
import { useUi } from '../stores/ui';

const routes = [
    // Public / guest-only
    {
        path: '/portal/login',
        name: 'portal.login',
        component: LoginPage,
        meta: { layout: 'public', guest: true },
    },
    {
        path: '/portal/register',
        name: 'portal.register',
        component: RegisterPage,
        meta: { layout: 'public', guest: true, requires: 'publicRegistration' },
    },
    {
        path: '/portal/forgot',
        name: 'portal.forgot',
        component: ForgotPasswordPage,
        meta: { layout: 'public', guest: true },
    },
    {
        path: '/portal/reset',
        name: 'portal.reset',
        component: ResetPasswordPage,
        meta: { layout: 'public', guest: true },
    },
    {
        path: '/portal/verify',
        name: 'portal.verify',
        component: VerifyEmailPage,
        meta: { layout: 'public' },
    },
    {
        path: '/portal/magic',
        name: 'portal.magic',
        component: MagicLinkPage,
        meta: { layout: 'public' },
    },

    // Authenticated
    {
        path: '/portal',
        name: 'portal.home',
        component: HomePage,
        meta: { layout: 'app', auth: true },
    },
    {
        path: '/portal/requests',
        name: 'portal.requests',
        component: RequestListPage,
        meta: { layout: 'app', auth: true },
    },
    {
        path: '/portal/requests/new',
        name: 'portal.requests.new',
        component: NewRequestPage,
        meta: { layout: 'app', auth: true },
    },
    {
        path: '/portal/requests/:id',
        name: 'portal.requests.show',
        component: RequestDetailPage,
        meta: { layout: 'app', auth: true },
        props: true,
    },
    {
        path: '/portal/profile',
        name: 'portal.profile',
        component: ProfilePage,
        meta: { layout: 'app', auth: true },
    },

    // Fallback
    {
        path: '/portal/:pathMatch(.*)*',
        redirect: { name: 'portal.home' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuth();
    const config = useConfig();
    const ui = useUi();

    // First navigation: make sure we've tried to resolve the current user.
    if (!auth.bootstrapped) {
        try {
            await auth.bootstrap();
        } catch {
            // bootstrap swallows 401 internally; anything else we just ignore
            // so the guard can still make a decision.
        }
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return {
            name: 'portal.login',
            query: { redirect: to.fullPath },
        };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        const roles = auth.user?.roles ?? [];
        const isStaff = roles.some((r) => r === 'superadmin' || r === 'manager' || r?.slug === 'superadmin' || r?.slug === 'manager');
        if (isStaff) {
            window.location.assign('/dashboard');
            return false;
        }
        return { name: 'portal.home' };
    }

    if (to.meta.requires === 'publicRegistration' && config.allowPublicRegistration === false) {
        ui.pushToast({
            severity: 'info',
            summary: 'Registration is disabled — ask your account manager for an invite.',
        });
        return { name: 'portal.login' };
    }

    return true;
});

export default router;
