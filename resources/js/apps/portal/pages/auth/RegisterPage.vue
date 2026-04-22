<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';

import { http, ensureCsrf } from '../../../../shared/http';
import { useConfig } from '../../stores/config';

const router = useRouter();
const config = useConfig();

const form = reactive({
    name: '',
    email: '',
    company: '',
    phone: '',
    password: '',
    password_confirmation: '',
    captcha: '',
    accept_terms: false,
});

const errors = reactive({});
const generalError = ref('');
const loading = ref(false);

async function submit() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    generalError.value = '';

    if (!form.accept_terms) {
        errors.accept_terms = ['Please accept the terms.'];
        return;
    }

    loading.value = true;
    try {
        await ensureCsrf();
        await http.post('/api/v1/portal/auth/register', form);
        router.push({ name: 'portal.verify', query: { email: form.email } });
    } catch (e) {
        const status = e?.response?.status;
        const data = e?.response?.data;
        if (status === 422 && data?.errors) {
            Object.assign(errors, data.errors);
        } else {
            generalError.value = 'Something went wrong. Please try again.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <header class="text-center mb-5">
            <h2 class="text-lg font-semibold text-gray-800">Create your account</h2>
            <p class="text-sm text-gray-500 mt-1">So we can help you faster next time.</p>
        </header>

        <form class="space-y-3" @submit.prevent="submit">
            <Message v-if="generalError" severity="error" :closable="false">
                {{ generalError }}
            </Message>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="reg-name">Name</label>
                <InputText id="reg-name" v-model="form.name" class="w-full" required />
                <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name?.[0] || errors.name }}</p>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="reg-email">Email</label>
                <InputText id="reg-email" v-model="form.email" type="email" class="w-full" required />
                <p v-if="errors.email" class="text-xs text-red-600 mt-1">{{ errors.email?.[0] || errors.email }}</p>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="reg-company">Company <span class="text-gray-400">(optional)</span></label>
                <InputText id="reg-company" v-model="form.company" class="w-full" />
                <p v-if="errors.company" class="text-xs text-red-600 mt-1">{{ errors.company?.[0] || errors.company }}</p>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="reg-phone">Phone <span class="text-gray-400">(optional)</span></label>
                <InputText id="reg-phone" v-model="form.phone" class="w-full" />
                <p v-if="errors.phone" class="text-xs text-red-600 mt-1">{{ errors.phone?.[0] || errors.phone }}</p>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="reg-password">Password</label>
                <Password id="reg-password" v-model="form.password" toggleMask class="w-full" inputClass="w-full" required />
                <p class="text-xs text-gray-400 mt-1">At least 10 characters.</p>
                <p v-if="errors.password" class="text-xs text-red-600 mt-1">{{ errors.password?.[0] || errors.password }}</p>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1" for="reg-password-confirm">Confirm password</label>
                <Password id="reg-password-confirm" v-model="form.password_confirmation" toggleMask :feedback="false" class="w-full" inputClass="w-full" required />
                <p v-if="errors.password_confirmation" class="text-xs text-red-600 mt-1">{{ errors.password_confirmation?.[0] || errors.password_confirmation }}</p>
            </div>

            <div v-if="config.requireCaptcha">
                <label class="block text-sm text-gray-700 mb-1" for="reg-captcha">Captcha</label>
                <InputText id="reg-captcha" v-model="form.captcha" class="w-full" />
                <p v-if="errors.captcha" class="text-xs text-red-600 mt-1">{{ errors.captcha?.[0] || errors.captcha }}</p>
            </div>

            <div class="flex items-start gap-2">
                <Checkbox inputId="reg-terms" v-model="form.accept_terms" :binary="true" />
                <label for="reg-terms" class="text-sm text-gray-700">
                    I agree to the terms of service and privacy policy.
                </label>
            </div>
            <p v-if="errors.accept_terms" class="text-xs text-red-600 -mt-2">{{ errors.accept_terms?.[0] || errors.accept_terms }}</p>

            <Button type="submit" label="Create account" class="w-full" :loading="loading" :disabled="loading" />
        </form>

        <div class="mt-4 text-center text-sm">
            <router-link :to="{ name: 'portal.login' }" class="text-primary-600 hover:underline">
                Already have an account? Sign in
            </router-link>
        </div>
    </div>
</template>
