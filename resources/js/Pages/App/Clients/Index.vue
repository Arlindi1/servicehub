<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    clients: { type: Object, required: true },
    filters: { type: Object, required: true },
    can: { type: Object, required: true },
});

const flashSuccess = computed(() => usePage().props.flash?.success);

const search = ref(props.filters.search ?? '');

const submitSearch = () => {
    router.get(
        route('app.clients.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Clients" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Clients
                </h2>

                <Link v-if="can.create" :href="route('app.clients.create')">
                    <PrimaryButton>
                        New Client
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="flashSuccess" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ flashSuccess }}
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 p-6">
                        <form class="flex flex-wrap items-center gap-3" @submit.prevent="submitSearch">
                            <div class="w-full sm:max-w-md">
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    class="block w-full"
                                    placeholder="Search by name or email..."
                                />
                            </div>

                            <PrimaryButton type="submit">
                                Search
                            </PrimaryButton>

                            <Link
                                v-if="filters.search"
                                :href="route('app.clients.index')"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Clear
                            </Link>
                        </form>
                    </div>

                    <div class="p-6">
                        <div v-if="clients.data.length === 0" class="text-center">
                            <div class="text-gray-900">
                                No clients found.
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                Create your first client to start organizing projects and invoices.
                            </div>
                            <div class="mt-4" v-if="can.create">
                                <Link :href="route('app.clients.create')">
                                    <PrimaryButton>
                                        New Client
                                    </PrimaryButton>
                                </Link>
                            </div>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Client
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Email
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Phone
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="client in clients.data" :key="client.id">
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <Link
                                                :href="route('app.clients.show', client.id)"
                                                class="font-medium text-gray-900 hover:text-indigo-600"
                                            >
                                                {{ client.name }}
                                            </Link>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ client.email }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            <span v-if="client.phone">{{ client.phone }}</span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                            <div class="flex justify-end gap-3">
                                                <Link
                                                    v-if="client.can.view"
                                                    :href="route('app.clients.show', client.id)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    v-if="client.can.update"
                                                    :href="route('app.clients.edit', client.id)"
                                                    class="text-gray-700 hover:text-gray-900"
                                                >
                                                    Edit
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-6">
                                <Pagination :links="clients.links" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
