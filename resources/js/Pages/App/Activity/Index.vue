<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    activity: { type: Object, required: true },
    filters: { type: Object, required: true },
    reference: { type: Object, required: true },
});

const event = ref(props.filters.event ?? '');
const subjectType = ref(props.filters.subject_type ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

const subjectTypeLabel = (value) => {
    switch (value) {
        case 'App\\Models\\Client':
            return 'Client';
        case 'App\\Models\\Project':
            return 'Project';
        case 'App\\Models\\Task':
            return 'Task';
        case 'App\\Models\\ProjectFile':
            return 'File';
        case 'App\\Models\\Comment':
            return 'Comment';
        case 'App\\Models\\Invoice':
            return 'Invoice';
        default:
            return value?.split('\\\\').pop() ?? value;
    }
};

const formatDateTime = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleString();
};

const actorLabel = (item) => {
    if (item.actor?.name) return item.actor.name;
    if (item.actor_type === 'system') return 'System';
    return item.actor_type;
};

const subjectName = (item) => {
    const description = item.description ?? {};
    return (
        description.number ||
        description.title ||
        description.original_name ||
        description.name ||
        description.project_title ||
        `#${item.subject_id}`
    );
};

const submitFilters = () => {
    router.get(
        route('app.activity.index'),
        {
            event: event.value || undefined,
            subject_type: subjectType.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Activity Log" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Activity Log
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 p-6">
                        <form class="grid gap-3 sm:grid-cols-12" @submit.prevent="submitFilters">
                            <div class="sm:col-span-3">
                                <select
                                    v-model="event"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All events</option>
                                    <option v-for="e in reference.events" :key="e" :value="e">{{ e }}</option>
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <select
                                    v-model="subjectType"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All subjects</option>
                                    <option v-for="s in reference.subject_types" :key="s" :value="s">{{ subjectTypeLabel(s) }}</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <input
                                    v-model="from"
                                    type="date"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <input
                                    v-model="to"
                                    type="date"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <PrimaryButton type="submit" class="w-full justify-center">
                                    Apply
                                </PrimaryButton>
                            </div>

                            <div class="sm:col-span-12">
                                <Link
                                    v-if="filters.event || filters.subject_type || filters.from || filters.to"
                                    :href="route('app.activity.index')"
                                    class="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Clear filters
                                </Link>
                            </div>
                        </form>
                    </div>

                    <div v-if="activity.data.length === 0" class="p-6 text-sm text-gray-600">
                        No activity entries yet.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        When
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Actor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Event
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Subject
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="item in activity.data" :key="item.id">
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ formatDateTime(item.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="flex items-center gap-2">
                                            <div class="font-medium text-gray-900">{{ actorLabel(item) }}</div>
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                                {{ item.actor_type }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="font-medium text-gray-900">{{ item.event }}</div>
                                        <div v-if="item.description?.from && item.description?.to" class="mt-1 text-xs text-gray-500">
                                            {{ item.description.from }} → {{ item.description.to }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="text-xs uppercase tracking-wide text-gray-500">
                                            {{ item.subject_label }}
                                        </div>
                                        <div class="mt-1">
                                            <Link
                                                v-if="item.url"
                                                :href="item.url"
                                                class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                            >
                                                {{ subjectName(item) }}
                                            </Link>
                                            <div v-else class="text-sm font-medium text-gray-900">
                                                {{ subjectName(item) }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="activity.links?.length" class="border-t border-gray-100 p-6">
                        <Pagination :links="activity.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
