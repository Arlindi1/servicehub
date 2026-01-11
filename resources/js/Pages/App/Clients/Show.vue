<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    client: { type: Object, required: true },
    can: { type: Object, required: true },
    projectsCount: { type: Number, required: true },
    invoicesCount: { type: Number, required: true },
    latestProjects: { type: Array, required: true },
    latestInvoices: { type: Array, required: true },
});

const flashSuccess = computed(() => usePage().props.flash?.success);

const confirmingDelete = ref(false);

const destroyForm = useForm({});

const confirmDelete = () => {
    confirmingDelete.value = true;
};

const closeModal = () => {
    confirmingDelete.value = false;
    destroyForm.reset();
};

const destroy = () => {
    destroyForm.delete(route('app.clients.destroy', props.client.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const money = (cents) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format((Number(cents ?? 0) || 0) / 100);

const projectStatusClass = (status) => {
    switch (status) {
        case 'Active':
            return 'bg-green-50 text-green-700 ring-green-200';
        case 'Waiting on Client':
            return 'bg-yellow-50 text-yellow-700 ring-yellow-200';
        case 'Delivered':
            return 'bg-blue-50 text-blue-700 ring-blue-200';
        case 'Completed':
            return 'bg-indigo-50 text-indigo-700 ring-indigo-200';
        case 'Archived':
            return 'bg-gray-100 text-gray-700 ring-gray-200';
        default:
            return 'bg-gray-50 text-gray-700 ring-gray-200';
    }
};

const invoiceStatusClass = (status) => {
    switch (status) {
        case 'Paid':
            return 'bg-green-50 text-green-700 ring-green-200';
        case 'Sent':
            return 'bg-blue-50 text-blue-700 ring-blue-200';
        case 'Overdue':
            return 'bg-red-50 text-red-700 ring-red-200';
        case 'Void':
            return 'bg-gray-100 text-gray-700 ring-gray-200';
        default:
            return 'bg-yellow-50 text-yellow-700 ring-yellow-200';
    }
};
</script>

<template>
    <Head :title="client.name" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ client.name }}
                </h2>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('app.clients.index')"
                        class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        Back
                    </Link>

                    <Link
                        v-if="can.update"
                        :href="route('app.clients.edit', client.id)"
                        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        Edit
                    </Link>

                    <DangerButton v-if="can.delete" type="button" @click="confirmDelete">
                        Delete
                    </DangerButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="flashSuccess" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ flashSuccess }}
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-2">
                        <div class="border-b border-gray-100 p-6">
                            <div class="text-sm text-gray-600">Client details</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">
                                {{ client.name }}
                            </div>
                        </div>

                        <div class="p-6">
                            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Email</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ client.email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Phone</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <span v-if="client.phone">{{ client.phone }}</span>
                                        <span v-else class="text-gray-400">—</span>
                                    </dd>
                                </div>
                                <div class="sm:col-span-2" v-if="client.notes">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Notes</dt>
                                    <dd class="mt-1 whitespace-pre-wrap text-sm text-gray-900">{{ client.notes }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="border-b border-gray-100 p-6">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-gray-900">
                                        Projects ({{ projectsCount }})
                                    </div>
                                    <Link
                                        :href="route('app.projects.index', { client_id: client.id })"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                    >
                                        View all
                                    </Link>
                                </div>
                            </div>
                            <div v-if="latestProjects.length === 0" class="p-6">
                                <div class="text-sm font-medium text-gray-900">No projects yet</div>
                                <div class="mt-1 text-sm text-gray-600">
                                    Create the first project for {{ client.name }}.
                                </div>
                                <div class="mt-4">
                                    <Link :href="route('app.projects.create', { client_id: client.id })">
                                        <PrimaryButton type="button">
                                            Create project
                                        </PrimaryButton>
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="divide-y divide-gray-100">
                                <Link
                                    v-for="project in latestProjects"
                                    :key="project.id"
                                    :href="route('app.projects.show', project.id)"
                                    class="block p-6 transition hover:bg-gray-50"
                                >
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-gray-900">
                                                {{ project.title }}
                                            </div>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                                <span v-if="project.due_date">Due {{ project.due_date }}</span>
                                                <span v-else>No due date</span>
                                                <span class="text-gray-300">•</span>
                                                <span class="capitalize">{{ project.priority }}</span>
                                            </div>
                                        </div>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                            :class="projectStatusClass(project.status)"
                                        >
                                            {{ project.status }}
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="border-b border-gray-100 p-6">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-gray-900">
                                        Invoices ({{ invoicesCount }})
                                    </div>
                                    <Link
                                        :href="route('app.invoices.index', { client_id: client.id })"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                    >
                                        View all
                                    </Link>
                                </div>
                            </div>
                            <div v-if="latestInvoices.length === 0" class="p-6">
                                <div class="text-sm font-medium text-gray-900">No invoices yet</div>
                                <div class="mt-1 text-sm text-gray-600">
                                    Create an invoice for {{ client.name }} when you're ready.
                                </div>
                                <div class="mt-4">
                                    <Link :href="route('app.invoices.create', { client_id: client.id })">
                                        <PrimaryButton type="button">
                                            Create invoice
                                        </PrimaryButton>
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="divide-y divide-gray-100">
                                <Link
                                    v-for="invoice in latestInvoices"
                                    :key="invoice.id"
                                    :href="route('app.invoices.show', invoice.id)"
                                    class="block p-6 transition hover:bg-gray-50"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-gray-900">
                                                {{ invoice.number }}
                                            </div>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                                <span>{{ money(invoice.total) }}</span>
                                                <span class="text-gray-300">•</span>
                                                <span v-if="invoice.due_at">Due {{ invoice.due_at }}</span>
                                                <span v-else>No due date</span>
                                            </div>
                                        </div>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                            :class="invoiceStatusClass(invoice.status)"
                                        >
                                            {{ invoice.status }}
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <Modal :show="confirmingDelete" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Delete client</h2>
            <p class="mt-1 text-sm text-gray-600">
                This will permanently delete <span class="font-medium">{{ client.name }}</span>.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <SecondaryButton type="button" @click="closeModal">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    type="button"
                    :class="{ 'opacity-25': destroyForm.processing }"
                    :disabled="destroyForm.processing"
                    @click="destroy"
                >
                    Delete
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
