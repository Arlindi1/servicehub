<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    projects: { type: Object, required: true },
    clients: { type: Array, required: true },
    filters: { type: Object, required: true },
    reference: { type: Object, required: true },
    can: { type: Object, required: true },
});

const flashSuccess = computed(() => usePage().props.flash?.success);

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const clientId = ref(props.filters.client_id ? String(props.filters.client_id) : '');

const submitFilters = () => {
    router.get(
        route('app.projects.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            client_id: clientId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Projects" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Projects
                </h2>

                <Link v-if="can.create" :href="route('app.projects.create')">
                    <PrimaryButton>
                        New Project
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
                        <form class="grid gap-3 sm:grid-cols-12" @submit.prevent="submitFilters">
                            <div class="sm:col-span-5">
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    class="block w-full"
                                    placeholder="Search by project or client..."
                                />
                            </div>

                            <div class="sm:col-span-3">
                                <select
                                    v-model="clientId"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All clients</option>
                                    <option v-for="client in clients" :key="client.id" :value="String(client.id)">
                                        {{ client.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="sm:col-span-3">
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
                                    v-if="filters.search || filters.status || filters.client_id"
                                    :href="route('app.projects.index')"
                                    class="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Clear filters
                                </Link>
                            </div>
                        </form>
                    </div>

                    <div class="p-6">
                        <div v-if="projects.data.length === 0" class="text-center">
                            <div class="text-gray-900">No projects found.</div>
                            <div class="mt-2 text-sm text-gray-600">
                                Create a project to start tracking tasks, files, and client updates.
                            </div>
                            <div class="mt-4" v-if="can.create">
                                <Link :href="route('app.projects.create')">
                                    <PrimaryButton>
                                        New Project
                                    </PrimaryButton>
                                </Link>
                            </div>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Project
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Client
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Due Date
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Assignees
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="project in projects.data" :key="project.id">
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <Link
                                                :href="route('app.projects.show', project.id)"
                                                class="font-medium text-gray-900 hover:text-indigo-600"
                                            >
                                                {{ project.title }}
                                            </Link>
                                            <div class="text-xs text-gray-500">
                                                Priority: {{ project.priority }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            <span v-if="project.client">{{ project.client.name }}</span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ project.status }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            <span v-if="project.due_date">{{ project.due_date }}</span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <span v-if="project.assignees.length">
                                                {{ project.assignees.map((a) => a.name).join(', ') }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                            <div class="flex justify-end gap-3">
                                                <Link
                                                    v-if="project.can.view"
                                                    :href="route('app.projects.show', project.id)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    v-if="project.can.update"
                                                    :href="route('app.projects.edit', project.id)"
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
                                <Pagination :links="projects.links" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

