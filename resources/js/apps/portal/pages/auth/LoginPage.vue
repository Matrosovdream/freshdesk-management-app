<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import InputOtp from 'primevue/inputotp';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';

import { useAuth } from '../../stores/auth';
import { useConfig } from '../../stores/config';

const auth = useAuth();
const config = useConfig();
const route = useRoute();
const router = useRouter();

const activeTab = ref('email');
const emailForm = reactive({ email: '', password: '', remember: false });
const pinForm = reactive({ pin: '' });

const errors = reactive({});
const generalError = ref('');
const loading = ref(false);

function resetErrors() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    generalError.value = '';
}

function handleError(e) {
    const status = e?.response?.status;
    const data = e?.response?.data;
    if (status === 422 && data?.errors) {
        Object.assign(errors, data.errors);
        if (data.errors.credentials) generalError.value = data.errors.credentials[0] || data.errors.credentials;
    } else if (status === 401) {
        generalError.value = 'Invalid credentials.';
    } else if (status === 403) {
        generalError.value = 'This account is disabled. Contact support.';
    } else {
        generalError.value = 'Something went wrong. Please try again.';
    }
}

function redirectAfterLogin(user) {
    // Staff (superadmin/manager) go to the dashboard SPA.
    // Customers (or anyone else) stay in the portal SPA.
    const isStaff = Array.isArray(user?.roles)
        && user.roles.some((r) => r === 'superadmin' || r === 'manager' || r?.slug === 'superadmin' || r?.slug === 'manager');

    const targetFromQuery = typeof route.query.redirect === 'string' ? route.query.redirect : null;

    if (isStaff) {
        // Full page navigation — dashboard is a separate SPA bundle.
        window.location.assign(targetFromQuery?.startsWith('/dashboard') ? targetFromQuery : '/dashboard');
        return;
    }

    router.push(
        targetFromQuery && !targetFromQuery.startsWith('/dashboard')
            ? targetFromQuery
            : { name: 'portal.home' },
    );
}

async function submit(payload) {
    resetErrors();
    loading.value = true;
    try {
        const user = await auth.login(payload);
        redirectAfterLogin(user);
    } catch (e) {
        handleError(e);
    } finally {
        loading.value = false;
    }
}

const submitEmail = () => submit({
    email: emailForm.email,
    password: emailForm.password,
    remember: emailForm.remember,
});
const submitPin = () => submit({ pin: pinForm.pin });
</script>

<template>
    <div>
        <div>
            <div class="text-center mb-6">
                <div class="text-surface-900 dark:text-surface-0 text-xl font-semibold mb-1">Sign in</div>
                <span class="text-muted-color text-sm">Portal, dashboard, or support — one sign-in.</span>
            </div>

            <Tabs v-model:value="activeTab">
                <TabList>
                    <Tab value="email">Email</Tab>
                    <Tab value="pin">PIN</Tab>
                </TabList>

                <TabPanels>
                    <TabPanel value="email">
                        <form @submit.prevent="submitEmail" class="flex flex-col gap-3">
                            <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>

                            <div>
                                <label class="block text-sm font-medium mb-1" for="login-email">Email</label>
                                <InputText
                                    id="login-email"
                                    v-model="emailForm.email"
                                    type="email"
                                    autocomplete="email"
                                    class="w-full"
                                    required
                                />
                                <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email?.[0] || errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1" for="login-password">Password</label>
                                <Password
                                    id="login-password"
                                    v-model="emailForm.password"
                                    :toggleMask="true"
                                    :feedback="false"
                                    autocomplete="current-password"
                                    fluid
                                    required
                                />
                                <p v-if="errors.password" class="text-xs text-red-500 mt-1">{{ errors.password?.[0] || errors.password }}</p>
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 text-sm">
                                    <Checkbox v-model="emailForm.remember" binary /> Remember me
                                </label>
                                <router-link :to="{ name: 'portal.forgot' }" class="text-sm text-primary hover:underline">
                                    Forgot password?
                                </router-link>
                            </div>

                            <Button type="submit" label="Sign in" :loading="loading" class="w-full" />
                        </form>
                    </TabPanel>

                    <TabPanel value="pin">
                        <form @submit.prevent="submitPin" class="flex flex-col items-center gap-4 py-2">
                            <Message v-if="generalError" severity="error" :closable="false" class="w-full">{{ generalError }}</Message>
                            <p class="text-muted-color text-sm">Enter your 4-digit PIN</p>
                            <InputOtp v-model="pinForm.pin" :length="4" integerOnly />
                            <p v-if="errors.pin" class="text-xs text-red-500">{{ errors.pin?.[0] || errors.pin }}</p>
                            <Button
                                type="submit"
                                label="Sign in"
                                :loading="loading"
                                :disabled="pinForm.pin.length !== 4"
                                class="w-full"
                            />
                        </form>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <div class="mt-4 flex flex-col items-center gap-2 text-sm">
                <router-link
                    v-if="config.allowPublicRegistration"
                    :to="{ name: 'portal.register' }"
                    class="text-muted-color hover:underline"
                >
                    Create an account
                </router-link>
            </div>
        </div>
    </div>
</template>
