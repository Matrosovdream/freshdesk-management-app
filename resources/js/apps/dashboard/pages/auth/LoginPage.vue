<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import InputOtp from 'primevue/inputotp';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { useAuth } from '@/stores/auth';

const auth = useAuth();
const router = useRouter();
const route = useRoute();

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
        generalError.value = 'Incorrect email or password.';
    } else if (status === 403) {
        generalError.value = "Your account doesn't have dashboard access.";
    } else {
        generalError.value = 'Something went wrong. Please try again.';
    }
}

async function submit(payload) {
    resetErrors();
    loading.value = true;
    try {
        await auth.login(payload);
        router.push(route.query.redirect || '/');
    } catch (e) {
        handleError(e);
    } finally {
        loading.value = false;
    }
}

const submitEmail = () => submit({ email: emailForm.email, password: emailForm.password, remember: emailForm.remember });
const submitPin = () => submit({ pin: pinForm.pin });
</script>

<template>
    <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
        <div class="bg-surface-0 dark:bg-surface-900 py-12 px-8 sm:px-12" style="border-radius: 53px;">
            <div class="text-center mb-8">
                <div class="text-surface-900 dark:text-surface-0 text-2xl font-medium mb-1">Sign in</div>
                <span class="text-muted-color text-sm">Access the admin dashboard</span>
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
                                <label class="block text-sm font-medium mb-1">Email</label>
                                <InputText v-model="emailForm.email" type="email" class="w-full" required />
                                <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email[0] || errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Password</label>
                                <Password v-model="emailForm.password" :toggleMask="true" :feedback="false" fluid required />
                                <p v-if="errors.password" class="text-xs text-red-500 mt-1">{{ errors.password[0] || errors.password }}</p>
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 text-sm">
                                    <Checkbox v-model="emailForm.remember" binary /> Remember me
                                </label>
                                <router-link to="/forgot" class="text-sm text-primary hover:underline">Forgot password?</router-link>
                            </div>

                            <Button type="submit" label="Sign in" :loading="loading" class="w-full" />
                        </form>
                    </TabPanel>

                    <TabPanel value="pin">
                        <form @submit.prevent="submitPin" class="flex flex-col items-center gap-4 py-2">
                            <Message v-if="generalError" severity="error" :closable="false" class="w-full">{{ generalError }}</Message>
                            <p class="text-muted-color text-sm">Enter your 4-digit PIN</p>
                            <InputOtp v-model="pinForm.pin" :length="4" integerOnly />
                            <Button type="submit" label="Sign in" :loading="loading" :disabled="pinForm.pin.length !== 4" class="w-full" />
                        </form>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </div>
</template>
