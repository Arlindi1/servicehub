<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InvoiceForm from '@/Pages/App/Invoices/Partials/InvoiceForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    clients: { type: Array, required: true },
    projects: { type: Array, required: true },
    prefillClientId: { type: Number, default: null },
    defaultDueAt: { type: String, default: '' },
});

const form = useForm({
    client_id: props.prefillClientId ? String(props.prefillClientId) : '',
    project_id: '',
    due_at: props.defaultDueAt ?? '',
    notes: '',
    items: [
        { description: '', quantity: 1, unit_price: 0 },
    ],
});

const submit = () => {
    form.post(route('app.invoices.store'));
};
</script>

<template>
    <Head title="New Invoice" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    New Invoice
                </h2>
                <Link
                    :href="route('app.invoices.index')"
                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    Back
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <form class="space-y-6" @submit.prevent="submit">
                    <InvoiceForm :form="form" :clients="clients" :projects="projects" submit-label="Create invoice" />
                </form>
            </div>
        </div>
    </AppLayout>
</template>
