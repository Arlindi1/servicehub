<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DiscussionTab from '@/Pages/App/Projects/Partials/DiscussionTab.vue';
import FilesTab from '@/Pages/App/Projects/Partials/FilesTab.vue';
import TasksTab from '@/Pages/App/Projects/Partials/TasksTab.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    project: { type: Object, required: true },
    tab: { type: String, required: true },
    reference: { type: Object, required: true },
    taskFilters: { type: Object, default: () => ({}) },
    taskReference: { type: Object, default: () => ({}) },
    taskCan: { type: Object, default: () => ({}) },
    tasks: { type: Object, default: null },
    fileReference: { type: Object, default: () => ({}) },
    fileCan: { type: Object, default: () => ({}) },
    files: { type: Object, default: null },
    commentCan: { type: Object, default: () => ({}) },
    comments: { type: Object, default: null },
    can: { type: Object, required: true },
});

const flashSuccess = computed(() => usePage().props.flash?.success);

const tabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'tasks', label: 'Tasks' },
    { key: 'files', label: 'Files' },
    { key: 'discussion', label: 'Discussion' },
    { key: 'invoice', label: 'Invoice' },
];

const metaForm = useForm({
    status: props.project.status,
    priority: props.project.priority,
    due_date: props.project.due_date ?? '',
});

const submitMeta = () => {
    metaForm.patch(route('app.projects.meta.update', props.project.id), {
        preserveScroll: true,
    });
};

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
    destroyForm.delete(route('app.projects.destroy', props.project.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head :title="project.title" />

    <AppLayout>
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

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('app.projects.index')"
                        class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        Back
                    </Link>

                    <Link
                        v-if="can.update"
                        :href="route('app.projects.edit', project.id)"
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

                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 px-6">
                        <nav class="-mb-px flex flex-wrap gap-6" aria-label="Tabs">
                            <Link
                                v-for="t in tabs"
                                :key="t.key"
                                :href="route('app.projects.show', { project: project.id, tab: t.key })"
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
                                    <div class="text-sm font-semibold text-gray-900">Details</div>
                                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Client</dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                <Link
                                                    v-if="project.client"
                                                    :href="route('app.clients.show', project.client.id)"
                                                    class="font-medium text-indigo-600 hover:text-indigo-900"
                                                >
                                                    {{ project.client.name }}
                                                </Link>
                                                <span v-else class="text-gray-400">—</span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Created</dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                <span v-if="project.created_at">{{ project.created_at }}</span>
                                                <span v-else class="text-gray-400">—</span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Created by</dt>
                                            <dd class="mt-1 text-sm text-gray-900">
                                                <span v-if="project.created_by">{{ project.created_by.name }}</span>
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
                                    <div class="text-sm font-semibold text-gray-900">Status</div>
                                    <div class="mt-1 text-sm text-gray-600">
                                        Update status, priority, and due date.
                                    </div>

                                    <div class="mt-4" v-if="can.update">
                                        <form class="space-y-4" @submit.prevent="submitMeta">
                                            <div>
                                                <InputLabel for="meta_status" value="Status" />
                                                <select
                                                    id="meta_status"
                                                    v-model="metaForm.status"
                                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    required
                                                >
                                                    <option v-for="s in reference.statuses" :key="s" :value="s">{{ s }}</option>
                                                </select>
                                                <InputError class="mt-2" :message="metaForm.errors.status" />
                                            </div>

                                            <div>
                                                <InputLabel for="meta_priority" value="Priority" />
                                                <select
                                                    id="meta_priority"
                                                    v-model="metaForm.priority"
                                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    required
                                                >
                                                    <option v-for="p in reference.priorities" :key="p" :value="p">
                                                        {{ p.charAt(0).toUpperCase() + p.slice(1) }}
                                                    </option>
                                                </select>
                                                <InputError class="mt-2" :message="metaForm.errors.priority" />
                                            </div>

                                            <div>
                                                <InputLabel for="meta_due_date" value="Due date" />
                                                <TextInput
                                                    id="meta_due_date"
                                                    v-model="metaForm.due_date"
                                                    type="date"
                                                    class="mt-1 block w-full"
                                                />
                                                <InputError class="mt-2" :message="metaForm.errors.due_date" />
                                            </div>

                                            <div class="flex items-center justify-end">
                                                <PrimaryButton :disabled="metaForm.processing">
                                                    Save
                                                </PrimaryButton>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="mt-4 text-sm text-gray-700" v-else>
                                        {{ project.status }} · {{ project.priority }} ·
                                        <span v-if="project.due_date">{{ project.due_date }}</span>
                                        <span v-else>No due date</span>
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
                            :can="taskCan"
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
    </AppLayout>

    <Modal :show="confirmingDelete" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Delete project</h2>
            <p class="mt-1 text-sm text-gray-600">
                This will permanently delete <span class="font-medium">{{ project.title }}</span>.
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
