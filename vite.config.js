import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/apps/dashboard/main.js',
                'resources/js/apps/portal/main.js',
                'resources/css/portal.css',
            ],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
        Components({
            resolvers: [PrimeVueResolver()],
        }),
    ],
    resolve: {
        alias: {
            '@dashboard': fileURLToPath(new URL('./resources/js/apps/dashboard', import.meta.url)),
            '@portal': fileURLToPath(new URL('./resources/js/apps/portal', import.meta.url)),
            '@shared': fileURLToPath(new URL('./resources/js/shared', import.meta.url)),
            '@': fileURLToPath(new URL('./resources/js/apps/dashboard', import.meta.url)),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
