import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';
import { useAuth } from './stores/auth';
import { useConfig } from './stores/config';
import { useUi } from './stores/ui';
import { useLayout, readCachedDarkMode } from './layout/composables/layout';
import { onRateLimit } from '@shared/http';
import { formatDate } from '@shared/datetime';

import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

import '@/assets/tailwind.css';
import '@/assets/styles.scss';

// Apply the cached dark-mode class synchronously, before any await or app mount,
// so reloads don't flash white while /me is in flight.
const cachedDark = readCachedDarkMode();
if (cachedDark !== null) {
    document.documentElement.classList.toggle('app-dark', cachedDark);
}

async function bootstrap() {
    const app = createApp(App);
    const pinia = createPinia();

    app.use(pinia);
    app.use(router);
    app.use(PrimeVue, {
        theme: {
            preset: Aura,
            options: {
                darkModeSelector: '.app-dark',
            },
        },
    });
    app.use(ToastService);
    app.use(ConfirmationService);

    app.config.globalProperties.$formatDate = formatDate;

    const auth = useAuth();
    const config = useConfig();
    const ui = useUi();

    const { setDarkMode } = useLayout();

    // Seed reactive state with the cached value so the moon/sun icon matches
    // the already-applied DOM class before bootstrap finishes.
    if (cachedDark !== null) setDarkMode(cachedDark);

    onRateLimit((info) => ui.setRateLimit(info));

    await Promise.allSettled([auth.bootstrap(), config.load()]);

    // Reconcile with the server preference (DB is the source of truth).
    const serverDark = auth.user?.preferences?.dark_theme;
    if (typeof serverDark === 'boolean') {
        setDarkMode(serverDark);
    }

    app.mount('#app');
}

bootstrap();
