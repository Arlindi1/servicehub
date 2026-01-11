<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    invoices: { type: Object, required: true },
    filters: { type: Object, required: true },
    reference: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const submitFilters = () => {
    router.get(
        route('portal.invoices.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const money = (cents) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format((Number(cents ?? 0) || 0) / 100);
</script>

<template>
    <Head title="Invoices" />

    <PortalLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Invoices
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 p-6">
                        <form class="grid gap-3 sm:grid-cols-12" @submit.prevent="submitFilters">
                            <div class="sm:col-span-7">
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    class="block w-full"
                                    placeholder="Search by invoice number..."
                                />
                            </div>

                            <div class="sm:col-span-4">
                                <select
                                    v-model="status"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All statuses</option>
                                    <option v-for="s in reference.statuses" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </div>

                            <div class="sm:col-span-1">
                                <PrimaryButton type="submit" class="w-full justify-center">
                                    Go
                                </PrimaryButton>
                            </div>

                            <div class="sm:col-span-12">
                                <Link
                                    v-if="filters.search || filters.status"
                                    :href="route('portal.invoices.index')"
                                    class="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Clear filters
                                </Link>
                            </div>
                        </form>
                    </div>

                    <div class="p-6">
                        <div v-if="invoices.data.length === 0" class="text-center">
                            <div class="text-gray-900">No invoices yet.</div>
                            <div class="mt-2 text-sm text-gray-600">
                                Invoices for your projects will appear here.
                            </div>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Invoice
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Project
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Due
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Total
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="invoice in invoices.data" :key="invoice.id">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                            {{ invoice.number }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <span v-if="invoice.project">{{ invoice.project.title }}</span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            {{ invoice.status }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <span v-if="invoice.due_at">{{ invoice.due_at }}</span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ money(invoice.total) }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <Link
                                                :href="route('portal.invoices.show', invoice.id)"
                                                class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="invoices.links?.length" class="mt-6">
                            <Pagination :links="invoices.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
