import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';
import { useAuth } from './stores/auth';
import { useConfig } from './stores/config';
import { useUi } from './stores/ui';
import { onRateLimit } from '@shared/http';

import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

import '@/assets/tailwind.css';
import '@/assets/styles.scss';

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
    const ui = useUi();

    onRateLimit((info) => ui.setRateLimit(info));

    await Promise.allSettled([auth.bootstrap(), config.load()]);

    app.mount('#app');
}

bootstrap();
