<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';

const props = defineProps({
    organization: { type: Object, required: true },
    logoUrl: { type: String, default: null },
});

const flashSuccess = computed(() => usePage().props.flash?.success);
const flashError = computed(() => usePage().props.flash?.error);

const form = useForm({
    name: props.organization.name ?? '',
    brand_color: props.organization.brand_color ?? '',
    logo: null,
    invoice_prefix: props.organization.invoice_prefix ?? 'INV',
    invoice_due_days_default: props.organization.invoice_due_days_default ?? 14,
    billing_email: props.organization.billing_email ?? '',
});

const logoPreviewUrl = ref(props.logoUrl);
let objectUrl = null;

const onLogoChange = (e) => {
    const file = e.target.files?.[0] ?? null;
    form.logo = file;

    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }

    if (file) {
        objectUrl = URL.createObjectURL(file);
        logoPreviewUrl.value = objectUrl;
        return;
    }

    logoPreviewUrl.value = props.logoUrl;
};

onUnmounted(() => {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
    }
});

const submit = () => {
    form.post(route('app.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Settings" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="flashSuccess" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ flashSuccess }}
                </div>

                <div v-if="flashError" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ flashError }}
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="border-b border-gray-100 p-6">
                            <div class="text-base font-semibold text-gray-900">Organization profile</div>
                            <div class="mt-1 text-sm text-gray-600">
                                Update your organization name, brand color, and logo.
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid gap-4 sm:grid-cols-12">
                                <div class="sm:col-span-8">
                                    <InputLabel for="org_name" value="Organization name" />
                                    <TextInput id="org_name" v-model="form.name" type="text" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <div class="sm:col-span-4">
                                    <InputLabel for="org_brand_color" value="Brand color" />
                                    <TextInput
                                        id="org_brand_color"
                                        v-model="form.brand_color"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="#0ea5e9"
                                    />
                                    <InputError class="mt-2" :message="form.errors.brand_color" />
                                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-600" v-if="form.brand_color">
                                        <span
                                            class="inline-block h-3 w-3 rounded border border-gray-200"
                                            :style="{ backgroundColor: form.brand_color }"
                                        />
                                        <span>Preview</span>
                                    </div>
                                </div>

                                <div class="sm:col-span-12">
                                    <InputLabel for="org_logo" value="Logo (optional)" />
                                    <input
                                        id="org_logo"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200"
                                        @change="onLogoChange"
                                    />
                                    <InputError class="mt-2" :message="form.errors.logo" />

                                    <div class="mt-4" v-if="logoPreviewUrl">
                                        <div class="text-xs font-medium text-gray-600">Preview</div>
                                        <img
                                            :src="logoPreviewUrl"
                                            alt="Organization logo preview"
                                            class="mt-2 h-16 w-auto rounded border border-gray-200 bg-white p-2"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="border-b border-gray-100 p-6">
                            <div class="text-base font-semibold text-gray-900">Invoice defaults</div>
                            <div class="mt-1 text-sm text-gray-600">
                                Configure invoice numbering and default due dates.
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid gap-4 sm:grid-cols-12">
                                <div class="sm:col-span-4">
                                    <InputLabel for="invoice_prefix" value="Invoice prefix" />
                                    <TextInput
                                        id="invoice_prefix"
                                        v-model="form.invoice_prefix"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="INV"
                                    />
                                    <div class="mt-1 text-xs text-gray-500">Example: {{ form.invoice_prefix || 'INV' }}-00001</div>
                                    <InputError class="mt-2" :message="form.errors.invoice_prefix" />
                                </div>

                                <div class="sm:col-span-4">
                                    <InputLabel for="invoice_due_days_default" value="Default due days" />
                                    <TextInput
                                        id="invoice_due_days_default"
                                        v-model.number="form.invoice_due_days_default"
                                        type="number"
                                        min="1"
                                        max="365"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.invoice_due_days_default" />
                                </div>

                                <div class="sm:col-span-4">
                                    <InputLabel for="billing_email" value="Billing email (optional)" />
                                    <TextInput
                                        id="billing_email"
                                        v-model="form.billing_email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        placeholder="billing@yourcompany.com"
                                    />
                                    <InputError class="mt-2" :message="form.errors.billing_email" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Save changes
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
