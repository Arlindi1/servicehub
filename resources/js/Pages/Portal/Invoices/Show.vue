<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    invoice: { type: Object, required: true },
});

const money = (cents) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format((Number(cents ?? 0) || 0) / 100);
</script>

<template>
    <Head :title="invoice.number" />

    <PortalLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="truncate text-xl font-semibold leading-tight text-gray-800">
                        {{ invoice.number }}
                    </h2>
                    <div class="mt-1 text-sm text-gray-600" v-if="invoice.client">
                        {{ invoice.client.name }}
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('portal.invoices.index')"
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
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</div>
                                <div class="mt-1 text-sm text-gray-900">{{ invoice.status }}</div>
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
    </PortalLayout>
</template>
