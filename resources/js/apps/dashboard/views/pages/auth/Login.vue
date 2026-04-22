<script setup>
import FloatingConfigurator from '@/components/FloatingConfigurator.vue';
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
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

const router = useRouter();

const http = axios.create({
    baseURL: '/',
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
});

const activeTab = ref('email');

const emailForm = reactive({ email: '', password: '', remember: false });
const pinForm = reactive({ pin: '' });

const errors = reactive({});
const generalError = ref('');
const loading = ref(false);
const pinLoading = ref(false);

async function ensureCsrf() {
    await http.get('sanctum/csrf-cookie');
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

function resetErrors() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    generalError.value = '';
}

async function submitEmail() {
    resetErrors();
    loading.value = true;
    try {
        await ensureCsrf();
        await http.post('api/v1/admin/auth/login', {
            email: emailForm.email,
            password: emailForm.password,
            remember: emailForm.remember,
        });
        window.location.href = '/dashboard';
    } catch (e) {
        handleError(e);
    } finally {
        loading.value = false;
    }
}

async function submitPin() {
    resetErrors();
    pinLoading.value = true;
    try {
        await ensureCsrf();
        await http.post('api/v1/admin/auth/login', { pin: pinForm.pin });
        window.location.href = '/dashboard';
    } catch (e) {
        handleError(e);
    } finally {
        pinLoading.value = false;
    }
}
</script>

<template>
    <FloatingConfigurator />
    <div class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="w-full bg-surface-0 dark:bg-surface-900 py-16 px-8 sm:px-20" style="border-radius: 53px; min-width: 24rem">
                    <div class="text-center mb-8">
                        <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-2">Welcome back</div>
                        <span class="text-muted-color font-medium">Sign in to continue</span>
                    </div>

                    <Tabs v-model:value="activeTab">
                        <TabList>
                            <Tab value="email">Email</Tab>
                            <Tab value="pin">PIN</Tab>
                        </TabList>

                        <TabPanels>
                            <TabPanel value="email">
                                <form @submit.prevent="submitEmail" class="md:w-[26rem]">
                                    <Message v-if="generalError" severity="error" :closable="false" class="mb-4">
                                        {{ generalError }}
                                    </Message>

                                    <label for="login-email" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Email</label>
                                    <InputText id="login-email" type="email" placeholder="Email address" class="w-full mb-2" v-model="emailForm.email" required />
                                    <p v-if="errors.email" class="text-xs text-red-500 mb-2">{{ errors.email?.[0] || errors.email }}</p>

                                    <label for="login-password" class="block text-surface-900 dark:text-surface-0 font-medium mb-2 mt-4">Password</label>
                                    <Password id="login-password" v-model="emailForm.password" placeholder="Password" :toggleMask="true" class="mb-2" fluid :feedback="false" required />
                                    <p v-if="errors.password" class="text-xs text-red-500 mb-2">{{ errors.password?.[0] || errors.password }}</p>

                                    <div class="flex items-center justify-between mt-4 mb-6 gap-8">
                                        <div class="flex items-center">
                                            <Checkbox v-model="emailForm.remember" inputId="rememberme" binary class="mr-2" />
                                            <label for="rememberme" class="text-sm">Remember me</label>
                                        </div>
                                    </div>

                                    <Button type="submit" label="Sign in" class="w-full" :loading="loading" :disabled="loading" />
                                </form>
                            </TabPanel>

                            <TabPanel value="pin">
                                <form @submit.prevent="submitPin" class="md:w-[26rem] flex flex-col items-center gap-4 py-4">
                                    <Message v-if="generalError" severity="error" :closable="false" class="w-full">
                                        {{ generalError }}
                                    </Message>

                                    <p class="text-muted-color">Enter your 4-digit PIN</p>
                                    <InputOtp v-model="pinForm.pin" :length="4" integerOnly />
                                    <p v-if="errors.pin" class="text-xs text-red-500">{{ errors.pin?.[0] || errors.pin }}</p>

                                    <Button
                                        type="submit"
                                        label="Sign in"
                                        class="w-full mt-2"
                                        :loading="pinLoading"
                                        :disabled="pinLoading || pinForm.pin.length !== 4"
                                    />
                                </form>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pi-eye,
.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}
</style>
