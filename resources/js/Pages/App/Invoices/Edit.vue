<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InvoiceForm from '@/Pages/App/Invoices/Partials/InvoiceForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    invoice: { type: Object, required: true },
    clients: { type: Array, required: true },
    projects: { type: Array, required: true },
});

const form = useForm({
    client_id: String(props.invoice.client_id),
    project_id: props.invoice.project_id ? String(props.invoice.project_id) : '',
    due_at: props.invoice.due_at ?? '',
    notes: props.invoice.notes ?? '',
    items: props.invoice.items?.length
        ? props.invoice.items.map((i) => ({
            description: i.description,
            quantity: i.quantity,
            unit_price: i.unit_price,
        }))
        : [{ description: '', quantity: 1, unit_price: 0 }],
});

const submit = () => {
    form.patch(route('app.invoices.update', props.invoice.id));
};
</script>

<template>
    <Head :title="`Edit ${invoice.number}`" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit {{ invoice.number }}
                </h2>
                <Link
                    :href="route('app.invoices.show', invoice.id)"
                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    Back
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <form class="space-y-6" @submit.prevent="submit">
                    <InvoiceForm :form="form" :clients="clients" :projects="projects" submit-label="Save changes" />
                </form>
            </div>
        </div>
    </AppLayout>
</template>

