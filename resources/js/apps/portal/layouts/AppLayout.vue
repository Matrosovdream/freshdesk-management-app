<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Menu from 'primevue/menu';
import Button from 'primevue/button';
import { useAuth } from '../stores/auth';

const auth = useAuth();
const router = useRouter();

const menu = ref(null);
const menuItems = ref([
    {
        label: 'Profile',
        icon: 'pi pi-user',
        command: () => router.push({ name: 'portal.profile' }),
    },
    {
        separator: true,
    },
    {
        label: 'Sign out',
        icon: 'pi pi-sign-out',
        command: async () => {
            await auth.logout();
            router.push({ name: 'portal.login' });
        },
    },
]);

function toggleUserMenu(event) {
    menu.value.toggle(event);
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between gap-4">
                <router-link :to="{ name: 'portal.home' }" class="flex items-center gap-2 font-semibold text-gray-800">
                    <span class="h-7 w-7 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm">H</span>
                    <span>Helpdesk</span>
                </router-link>

                <nav class="hidden sm:flex items-center gap-1 text-sm">
                    <router-link
                        :to="{ name: 'portal.home' }"
                        class="px-3 py-1.5 rounded hover:bg-gray-100 text-gray-700"
                        active-class="text-primary-600 font-medium"
                    >
                        Home
                    </router-link>
                    <router-link
                        :to="{ name: 'portal.requests' }"
                        class="px-3 py-1.5 rounded hover:bg-gray-100 text-gray-700"
                        active-class="text-primary-600 font-medium"
                    >
                        My requests
                    </router-link>
                    <router-link
                        :to="{ name: 'portal.requests.new' }"
                        class="px-3 py-1.5 rounded hover:bg-gray-100 text-gray-700"
                        active-class="text-primary-600 font-medium"
                    >
                        New request
                    </router-link>
                    <router-link
                        :to="{ name: 'portal.profile' }"
                        class="px-3 py-1.5 rounded hover:bg-gray-100 text-gray-700"
                        active-class="text-primary-600 font-medium"
                    >
                        Profile
                    </router-link>
                </nav>

                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        severity="secondary"
                        text
                        :label="auth.firstName || 'Account'"
                        icon="pi pi-user"
                        aria-haspopup="true"
                        aria-controls="portal-user-menu"
                        @click="toggleUserMenu"
                    />
                    <Menu ref="menu" id="portal-user-menu" :model="menuItems" :popup="true" />
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 py-6">
            <slot />
        </main>
    </div>
</template>
