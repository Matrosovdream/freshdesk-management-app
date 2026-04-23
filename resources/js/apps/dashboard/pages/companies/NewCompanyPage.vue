<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';
import TagInput from '@/components/shared/TagInput.vue';
import { useCompanies } from '@/stores/companies';
import { useUi } from '@/stores/ui';

const companies = useCompanies();
const ui = useUi();
const router = useRouter();

const form = reactive({
    name: '', description: '', domains: [], note: '',
    health_score: null, account_tier: '', renewal_date: '', industry: '',
});
const errors = reactive({});
const loading = ref(false);
const generalError = ref('');

async function submit() {
    loading.value = true;
    generalError.value = '';
    Object.keys(errors).forEach((k) => delete errors[k]);
    try {
        const created = await companies.create(form);
        ui.pushToast({ severity: 'success', summary: 'Company created.' });
        router.push(created?.id ? `/companies/${created.id}` : '/companies');
    } catch (e) {
        if (e.validation) Object.assign(errors, e.validation);
        else generalError.value = e?.response?.data?.message || 'Could not create company.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-4 max-w-3xl">
        <h1 class="text-2xl font-semibold">New company</h1>
        <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
        <form @submit.prevent="submit" class="flex flex-col gap-3 bg-surface-0 dark:bg-surface-900 p-5 rounded-lg border border-surface-200 dark:border-surface-700">
            <div>
                <label class="text-sm font-medium">Name</label>
                <InputText v-model="form.name" class="w-full" required />
                <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name[0] }}</p>
            </div>
            <div>
                <label class="text-sm font-medium">Description</label>
                <Textarea v-model="form.description" class="w-full" rows="2" />
            </div>
            <div>
                <label class="text-sm font-medium">Domains</label>
                <TagInput v-model="form.domains" placeholder="example.com" />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="text-sm font-medium">Industry</label>
                    <InputText v-model="form.industry" class="w-full" />
                </div>
                <div>
                    <label class="text-sm font-medium">Tier</label>
                    <InputText v-model="form.account_tier" class="w-full" />
                </div>
                <div>
                    <label class="text-sm font-medium">Health score</label>
                    <InputNumber v-model="form.health_score" class="w-full" />
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <Button label="Cancel" severity="secondary" outlined @click="router.push('/companies')" />
                <Button label="Create" type="submit" :loading="loading" />
            </div>
        </form>
    </div>
</template>
