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
import InputOtp from 'primevue/inputotp';
import Button from 'primevue/button';
import Message from 'primevue/message';

import { useAuth } from '../../stores/auth';
import { useConfig } from '../../stores/config';

const auth = useAuth();
const config = useConfig();
const route = useRoute();
const router = useRouter();
const toast = useToast();

const activeTab = ref('email');

const emailForm = reactive({ email: '', password: '' });
const pinForm = reactive({ pin: '' });

const errors = reactive({});
const generalError = ref('');
const loading = ref(false);
const pinLoading = ref(false);

function redirectAfterLogin() {
    const redirect = route.query.redirect && typeof route.query.redirect === 'string'
        ? route.query.redirect
        : { name: 'portal.home' };
    router.push(redirect);
}

function handleLoginError(e) {
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

async function submitEmail() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    generalError.value = '';
    loading.value = true;
    try {
        await auth.login({ email: emailForm.email, password: emailForm.password });
        redirectAfterLogin();
    } catch (e) {
        handleLoginError(e);
    } finally {
        loading.value = false;
    }
}

async function submitPin() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    generalError.value = '';
    pinLoading.value = true;
    try {
        await auth.login({ pin: pinForm.pin });
        redirectAfterLogin();
    } catch (e) {
        handleLoginError(e);
    } finally {
        pinLoading.value = false;
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
                <Tab value="email">Email</Tab>
                <Tab value="pin">PIN</Tab>
            </TabList>

            <TabPanels>
                <TabPanel value="email">
                    <form class="space-y-3" @submit.prevent="submitEmail">
                        <Message v-if="generalError" severity="error" :closable="false">
                            {{ generalError }}
                        </Message>

                        <div>
                            <label class="block text-sm text-gray-700 mb-1" for="login-email">Email</label>
                            <InputText
                                id="login-email"
                                v-model="emailForm.email"
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
                                v-model="emailForm.password"
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

                <TabPanel value="pin">
                    <form class="space-y-4 flex flex-col items-center" @submit.prevent="submitPin">
                        <Message v-if="generalError" severity="error" :closable="false" class="w-full">
                            {{ generalError }}
                        </Message>

                        <p class="text-sm text-gray-500">Enter your 4-digit PIN.</p>
                        <InputOtp v-model="pinForm.pin" :length="4" integerOnly />
                        <p v-if="errors.pin" class="text-xs text-red-600">{{ errors.pin?.[0] || errors.pin }}</p>

                        <Button
                            type="submit"
                            label="Sign in"
                            class="w-full"
                            :loading="pinLoading"
                            :disabled="pinLoading || pinForm.pin.length !== 4"
                        />
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
