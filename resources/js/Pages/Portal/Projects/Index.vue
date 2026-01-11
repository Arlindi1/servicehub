<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    projects: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');

const submitSearch = () => {
    router.get(
        route('portal.projects.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Projects" />

    <PortalLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Projects
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 p-6">
                        <form class="flex flex-wrap items-center gap-3" @submit.prevent="submitSearch">
                            <div class="w-full sm:max-w-md">
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    class="block w-full"
                                    placeholder="Search by title..."
                                />
                            </div>

                            <PrimaryButton type="submit">
                                Search
                            </PrimaryButton>

                            <Link
                                v-if="filters.search"
                                :href="route('portal.projects.index')"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Clear
                            </Link>
                        </form>
                    </div>

                    <div class="p-6">
                        <div v-if="projects.data.length === 0" class="text-center">
                            <div class="text-gray-900">No projects yet.</div>
                            <div class="mt-2 text-sm text-gray-600">
                                Projects shared with you will appear here.
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
                                            Status
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Due Date
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
                                                :href="route('portal.projects.show', project.id)"
                                                class="font-medium text-gray-900 hover:text-indigo-600"
                                            >
                                                {{ project.title }}
                                            </Link>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ project.status }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            <span v-if="project.due_date">{{ project.due_date }}</span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                            <Link
                                                :href="route('portal.projects.show', project.id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
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
    </PortalLayout>
</template>

