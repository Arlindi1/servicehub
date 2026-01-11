<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    can: { type: Object, required: true },
});

const flashSuccess = computed(() => usePage().props.flash?.success);

const money = (cents) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format((Number(cents ?? 0) || 0) / 100);

const statusBadgeClass = computed(() => {
    switch (props.invoice.status) {
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
});

const actionForm = useForm({});

const markSent = () => {
    actionForm.patch(route('app.invoices.markSent', props.invoice.id), { preserveScroll: true });
};

const markPaid = () => {
    actionForm.patch(route('app.invoices.markPaid', props.invoice.id), { preserveScroll: true });
};

const voidInvoice = () => {
    actionForm.patch(route('app.invoices.void', props.invoice.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="invoice.number" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="truncate text-xl font-semibold leading-tight text-gray-800">
                            {{ invoice.number }}
                        </h2>
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                            :class="statusBadgeClass"
                        >
                            {{ invoice.status }}
                        </span>
                    </div>
                    <div class="mt-1 text-sm text-gray-600" v-if="invoice.client">
                        {{ invoice.client.name }}
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('app.invoices.index')"
                        class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        Back
                    </Link>

                    <a
                        :href="route('invoices.pdf', invoice.id)"
                        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        Download PDF
                    </a>

                    <Link
                        v-if="can.update"
                        :href="route('app.invoices.edit', invoice.id)"
                        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        Edit
                    </Link>

                    <PrimaryButton v-if="can.markSent" type="button" @click="markSent" :disabled="actionForm.processing">
                        Mark Sent
                    </PrimaryButton>

                    <PrimaryButton v-if="can.markPaid" type="button" @click="markPaid" :disabled="actionForm.processing">
                        Mark Paid
                    </PrimaryButton>

                    <DangerButton v-if="can.void" type="button" @click="voidInvoice" :disabled="actionForm.processing">
                        Void
                    </DangerButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div v-if="flashSuccess" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ flashSuccess }}
                </div>

                <div class="space-y-6">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Client</div>
                                <div class="mt-1 text-sm text-gray-900">
                                    <div v-if="invoice.client">{{ invoice.client.name }}</div>
                                    <div v-if="invoice.client?.email" class="text-gray-600">{{ invoice.client.email }}</div>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Project</div>
                                <div class="mt-1 text-sm text-gray-900">
                                    <span v-if="invoice.project">{{ invoice.project.title }}</span>
                                    <span v-else class="text-gray-400">-</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Issued</div>
                                <div class="mt-1 text-sm text-gray-900">
                                    <span v-if="invoice.issued_at">{{ invoice.issued_at }}</span>
                                    <span v-else class="text-gray-400">-</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Due</div>
                                <div class="mt-1 text-sm text-gray-900">
                                    <span v-if="invoice.due_at">{{ invoice.due_at }}</span>
                                    <span v-else class="text-gray-400">-</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="invoice.notes" class="mt-6">
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Notes</div>
                            <div class="mt-2 whitespace-pre-wrap text-sm text-gray-800">{{ invoice.notes }}</div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div class="border-b border-gray-100 p-6">
                            <div class="text-sm font-semibold text-gray-900">Items</div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Description
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Qty
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Unit
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Line total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="item in invoice.items" :key="item.id">
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ item.description }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            {{ money(item.unit_price) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ money(item.line_total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-gray-100 p-6">
                            <div class="flex justify-end">
                                <div class="w-full max-w-sm space-y-1 text-sm">
                                    <div class="flex items-center justify-between text-gray-700">
                                        <div>Subtotal</div>
                                        <div class="font-medium text-gray-900">{{ money(invoice.subtotal) }}</div>
                                    </div>
                                    <div class="flex items-center justify-between text-gray-700">
                                        <div>Total</div>
                                        <div class="font-semibold text-gray-900">{{ money(invoice.total) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
