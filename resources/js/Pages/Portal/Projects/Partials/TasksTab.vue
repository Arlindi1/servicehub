<script setup>
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    projectId: { type: Number, required: true },
    tasks: { type: Object, required: true },
    filters: { type: Object, required: true },
    reference: { type: Object, required: true },
});

const statusFilter = ref(props.filters.status ?? '');
const assigneeFilter = ref(props.filters.assigned_to_user_id ? String(props.filters.assigned_to_user_id) : '');

const submitFilters = () => {
    router.get(
        route('portal.projects.show', props.projectId),
        {
            tab: 'tasks',
            task_status: statusFilter.value || undefined,
            task_assigned_to_user_id: assigneeFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const hasTasks = computed(() => (props.tasks?.data?.length ?? 0) > 0);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-lg border border-gray-200 p-5">
            <div class="text-sm font-semibold text-gray-900">Tasks</div>

            <form class="mt-4 grid gap-3 sm:grid-cols-12" @submit.prevent="submitFilters">
                <div class="sm:col-span-5">
                    <select
                        v-model="statusFilter"
                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All statuses</option>
                        <option v-for="s in reference.statuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>

                <div class="sm:col-span-5">
                    <select
                        v-model="assigneeFilter"
                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All assignees</option>
                        <option v-for="u in reference.assignees" :key="u.id" :value="String(u.id)">
                            {{ u.name }}
                        </option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <PrimaryButton type="submit" class="w-full justify-center">
                        Filter
                    </PrimaryButton>
                </div>

                <div class="sm:col-span-12">
                    <Link
                        v-if="filters.status || filters.assigned_to_user_id"
                        :href="route('portal.projects.show', { project: projectId, tab: 'tasks' })"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Clear filters
                    </Link>
                </div>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200">
            <div v-if="!hasTasks" class="p-8 text-center">
                <div class="text-gray-900">No tasks shared yet.</div>
                <div class="mt-2 text-sm text-gray-600">
                    Tasks will appear here as your team adds next steps to the project.
                </div>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Title
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Assignee
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Due date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="task in tasks.data" :key="task.id">
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ task.title }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ task.status }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span v-if="task.assignee">{{ task.assignee.name }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span v-if="task.due_date">{{ task.due_date }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="tasks.links?.length" class="border-t border-gray-100 p-5">
                <Pagination :links="tasks.links" />
            </div>
        </div>
    </div>
</template>
