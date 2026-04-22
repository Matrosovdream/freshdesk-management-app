import { createApp } from 'vue';
import { createPinia } from 'pinia';

import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

import App from './App.vue';
import router from './router';
import { useAuth } from './stores/auth';
import { useConfig } from './stores/config';

import '../../../css/portal.css';

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

    const auth = useAuth();
    const config = useConfig();

    // Kick these off in parallel; both stores swallow 401 internally so a
    // logged-out user still sees the SPA boot.
    await Promise.allSettled([auth.bootstrap(), config.load()]);

    app.mount('#app');
}

bootstrap();
