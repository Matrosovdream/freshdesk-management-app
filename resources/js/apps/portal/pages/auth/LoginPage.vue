<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';

import { useAuth } from '../../stores/auth';
import { useConfig } from '../../stores/config';

const auth = useAuth();
const config = useConfig();
const route = useRoute();
const router = useRouter();
const toast = useToast();

const activeTab = ref('password');

const passwordForm = reactive({ email: '', password: '' });
const magicForm = reactive({ email: '' });

const errors = reactive({});
const generalError = ref('');
const loading = ref(false);
const magicLoading = ref(false);

async function submitPassword() {
    errors.email = null;
    errors.password = null;
    generalError.value = '';
    loading.value = true;
    try {
        await auth.login(passwordForm);
        const redirect = route.query.redirect && typeof route.query.redirect === 'string'
            ? route.query.redirect
            : { name: 'portal.home' };
        router.push(redirect);
    } catch (e) {
        const status = e?.response?.status;
        const data = e?.response?.data;
        if (status === 422 && data?.errors) {
            Object.assign(errors, data.errors);
        } else if (status === 401) {
            generalError.value = 'Email or password is incorrect.';
        } else if (status === 403) {
            generalError.value = 'This account is disabled. Contact support.';
        } else {
            generalError.value = 'Something went wrong. Please try again.';
        }
    } finally {
        loading.value = false;
    }
}

async function submitMagic() {
    errors.magicEmail = null;
    magicLoading.value = true;
    try {
        await auth.magicLinkSend(magicForm.email);
        toast.add({
            severity: 'success',
            summary: 'Check your inbox',
            detail: "If an account exists, we've sent a link.",
            life: 5000,
        });
    } catch (e) {
        const data = e?.response?.data;
        if (data?.errors?.email) {
            errors.magicEmail = data.errors.email;
        } else {
            // Deliberate: same generic message to prevent enumeration.
            toast.add({
                severity: 'success',
                summary: 'Check your inbox',
                detail: "If an account exists, we've sent a link.",
                life: 5000,
            });
        }
    } finally {
        magicLoading.value = false;
    }
}
</script>

<template>
    <div>
        <header class="text-center mb-5">
            <h2 class="text-lg font-semibold text-gray-800">Sign in</h2>
            <p class="text-sm text-gray-500 mt-1">Sign in to submit and track your requests.</p>
        </header>

        <Tabs v-model:value="activeTab">
            <TabList>
                <Tab value="password">Password</Tab>
                <Tab value="magic">Email me a link</Tab>
            </TabList>

            <TabPanels>
                <TabPanel value="password">
                    <form class="space-y-3" @submit.prevent="submitPassword">
                        <Message v-if="generalError" severity="error" :closable="false">
                            {{ generalError }}
                        </Message>

                        <div>
                            <label class="block text-sm text-gray-700 mb-1" for="login-email">Email</label>
                            <InputText
                                id="login-email"
                                v-model="passwordForm.email"
                                type="email"
                                autocomplete="email"
                                class="w-full"
                                required
                            />
                            <p v-if="errors.email" class="text-xs text-red-600 mt-1">{{ errors.email?.[0] || errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-700 mb-1" for="login-password">Password</label>
                            <Password
                                id="login-password"
                                v-model="passwordForm.password"
                                toggleMask
                                :feedback="false"
                                autocomplete="current-password"
                                class="w-full"
                                inputClass="w-full"
                                required
                            />
                            <p v-if="errors.password" class="text-xs text-red-600 mt-1">{{ errors.password?.[0] || errors.password }}</p>
                        </div>

                        <Button type="submit" label="Sign in" class="w-full" :loading="loading" :disabled="loading" />
                    </form>
                </TabPanel>

                <TabPanel value="magic">
                    <form class="space-y-3" @submit.prevent="submitMagic">
                        <p class="text-sm text-gray-500">We'll email you a one-time sign-in link.</p>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1" for="magic-email">Email</label>
                            <InputText
                                id="magic-email"
                                v-model="magicForm.email"
                                type="email"
                                autocomplete="email"
                                class="w-full"
                                required
                            />
                            <p v-if="errors.magicEmail" class="text-xs text-red-600 mt-1">{{ errors.magicEmail?.[0] || errors.magicEmail }}</p>
                        </div>
                        <Button type="submit" label="Send me a link" class="w-full" :loading="magicLoading" :disabled="magicLoading" />
                    </form>
                </TabPanel>
            </TabPanels>
        </Tabs>

        <div class="mt-4 flex flex-col items-center gap-2 text-sm">
            <router-link :to="{ name: 'portal.forgot' }" class="text-primary-600 hover:underline">
                Forgot password?
            </router-link>
            <router-link
                v-if="config.allowPublicRegistration"
                :to="{ name: 'portal.register' }"
                class="text-gray-600 hover:underline"
            >
                Create an account
            </router-link>
        </div>
    </div>
</template>
