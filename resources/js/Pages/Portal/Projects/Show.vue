<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue';
import DiscussionTab from '@/Pages/Portal/Projects/Partials/DiscussionTab.vue';
import FilesTab from '@/Pages/Portal/Projects/Partials/FilesTab.vue';
import TasksTab from '@/Pages/Portal/Projects/Partials/TasksTab.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    project: { type: Object, required: true },
    tab: { type: String, required: true },
    taskFilters: { type: Object, default: () => ({}) },
    taskReference: { type: Object, default: () => ({}) },
    tasks: { type: Object, default: null },
    fileReference: { type: Object, default: () => ({}) },
    fileCan: { type: Object, default: () => ({}) },
    files: { type: Object, default: null },
    commentCan: { type: Object, default: () => ({}) },
    comments: { type: Object, default: null },
});

const tabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'tasks', label: 'Tasks' },
    { key: 'files', label: 'Files' },
    { key: 'discussion', label: 'Discussion' },
    { key: 'invoice', label: 'Invoice' },
];
</script>

<template>
    <Head :title="project.title" />

    <PortalLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="truncate text-xl font-semibold leading-tight text-gray-800">
                        {{ project.title }}
                    </h2>
                    <div class="mt-1 text-sm text-gray-600" v-if="project.client">
                        {{ project.client.name }}
                    </div>
                </div>

                <Link
                    :href="route('portal.projects.index')"
                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    Back
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 px-6">
                        <nav class="-mb-px flex flex-wrap gap-6" aria-label="Tabs">
                            <Link
                                v-for="t in tabs"
                                :key="t.key"
                                :href="route('portal.projects.show', { project: project.id, tab: t.key })"
                                class="whitespace-nowrap border-b-2 py-4 text-sm font-medium"
                                :class="tab === t.key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                preserve-scroll
                            >
                                {{ t.label }}
                            </Link>
                        </nav>
                    </div>

                    <div class="p-6">
                        <div v-if="tab === 'overview'" class="grid gap-6 lg:grid-cols-3">
                            <div class="space-y-6 lg:col-span-2">
                                <div class="rounded-lg border border-gray-200 p-5">
                                    <div class="text-sm font-semibold text-gray-900">Overview</div>
                                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ project.status }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Priority</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ project.priority }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Due date</dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                <span v-if="project.due_date">{{ project.due_date }}</span>
                                                <span v-else class="text-gray-400">—</span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Assignees</dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                <span v-if="project.assignees.length">
                                                    {{ project.assignees.map((a) => a.name).join(', ') }}
                                                </span>
                                                <span v-else class="text-gray-400">—</span>
                                            </dd>
                                        </div>
                                        <div class="sm:col-span-2" v-if="project.description">
                                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Description</dt>
                                            <dd class="mt-1 whitespace-pre-wrap text-sm text-gray-900">{{ project.description }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="rounded-lg border border-gray-200 p-5">
                                    <div class="text-sm font-semibold text-gray-900">Next steps</div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        Tasks, files, and discussion will appear in the other tabs as the project progresses.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <TasksTab
                            v-else-if="tab === 'tasks' && tasks"
                            :project-id="project.id"
                            :tasks="tasks"
                            :filters="taskFilters"
                            :reference="taskReference"
                        />

                        <FilesTab
                            v-else-if="tab === 'files' && files"
                            :project-id="project.id"
                            :files="files"
                            :reference="fileReference"
                            :can="fileCan"
                        />

                        <DiscussionTab
                            v-else-if="tab === 'discussion' && comments"
                            :project-id="project.id"
                            :comments="comments"
                            :can="commentCan"
                        />

                        <div v-else class="rounded-lg border border-dashed border-gray-200 p-8 text-center">
                            <div class="text-gray-900">
                                {{ tabs.find((t) => t.key === tab)?.label }} coming next.
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                This tab will be wired up in a later milestone.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
