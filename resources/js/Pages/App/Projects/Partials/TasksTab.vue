<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    projectId: { type: Number, required: true },
    tasks: { type: Object, required: true },
    filters: { type: Object, required: true },
    reference: { type: Object, required: true },
    can: { type: Object, required: true },
});

const statusFilter = ref(props.filters.status ?? '');
const assigneeFilter = ref(props.filters.assigned_to_user_id ? String(props.filters.assigned_to_user_id) : '');

const submitFilters = () => {
    router.get(
        route('app.projects.show', props.projectId),
        {
            tab: 'tasks',
            task_status: statusFilter.value || undefined,
            task_assigned_to_user_id: assigneeFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const createForm = useForm({
    title: '',
    status: props.reference.statuses?.[0] ?? 'Todo',
    assigned_to_user_id: '',
    due_date: '',
});

const submitCreate = () => {
    createForm.post(route('app.projects.tasks.store', props.projectId), {
        preserveScroll: true,
        onSuccess: () => createForm.reset('title', 'assigned_to_user_id', 'due_date'),
    });
};

const updateStatus = (taskId, status) => {
    router.patch(
        route('app.tasks.status.update', taskId),
        { status },
        { preserveScroll: true, preserveState: true },
    );
};

const editingTask = ref(null);
const editingOpen = ref(false);

const editForm = useForm({
    title: '',
    status: props.reference.statuses?.[0] ?? 'Todo',
    assigned_to_user_id: '',
    due_date: '',
});

const openEdit = (task) => {
    editingTask.value = task;
    editingOpen.value = true;

    editForm.title = task.title;
    editForm.status = task.status;
    editForm.assigned_to_user_id = task.assigned_to_user_id ? String(task.assigned_to_user_id) : '';
    editForm.due_date = task.due_date ?? '';
};

const closeEdit = () => {
    editingOpen.value = false;
    editingTask.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!editingTask.value) {
        return;
    }

    editForm.patch(route('app.tasks.update', editingTask.value.id), {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
};

const confirmingDelete = ref(false);
const taskToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (task) => {
    taskToDelete.value = task;
    confirmingDelete.value = true;
};

const closeDelete = () => {
    taskToDelete.value = null;
    confirmingDelete.value = false;
    deleteForm.reset();
};

const destroy = () => {
    if (!taskToDelete.value) {
        return;
    }

    deleteForm.delete(route('app.tasks.destroy', taskToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDelete(),
    });
};

const hasTasks = computed(() => (props.tasks?.data?.length ?? 0) > 0);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-lg border border-gray-200 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm font-semibold text-gray-900">Tasks</div>
            </div>

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
                        :href="route('app.projects.show', { project: projectId, tab: 'tasks' })"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Clear filters
                    </Link>
                </div>
            </form>
        </div>

        <div v-if="can.create" class="rounded-lg border border-gray-200 p-5">
            <div class="text-sm font-semibold text-gray-900">Create task</div>

            <form class="mt-4 grid gap-4 sm:grid-cols-12" @submit.prevent="submitCreate">
                <div class="sm:col-span-12">
                    <InputLabel for="task_title" value="Title" />
                    <TextInput
                        id="task_title"
                        v-model="createForm.title"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Write a clear, actionable task..."
                    />
                    <InputError class="mt-2" :message="createForm.errors.title" />
                </div>

                <div class="sm:col-span-4">
                    <InputLabel for="task_status" value="Status" />
                    <select
                        id="task_status"
                        v-model="createForm.status"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option v-for="s in reference.statuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                    <InputError class="mt-2" :message="createForm.errors.status" />
                </div>

                <div class="sm:col-span-4">
                    <InputLabel for="task_assignee" value="Assignee" />
                    <select
                        id="task_assignee"
                        v-model="createForm.assigned_to_user_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Unassigned</option>
                        <option v-for="u in reference.assignees" :key="u.id" :value="String(u.id)">
                            {{ u.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="createForm.errors.assigned_to_user_id" />
                </div>

                <div class="sm:col-span-4">
                    <InputLabel for="task_due_date" value="Due date" />
                    <TextInput
                        id="task_due_date"
                        v-model="createForm.due_date"
                        type="date"
                        class="mt-1 block w-full"
                    />
                    <InputError class="mt-2" :message="createForm.errors.due_date" />
                </div>

                <div class="sm:col-span-12 flex items-center justify-end">
                    <PrimaryButton :disabled="createForm.processing">
                        Add task
                    </PrimaryButton>
                </div>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200">
            <div v-if="!hasTasks" class="p-8 text-center">
                <div class="text-gray-900">No tasks yet.</div>
                <div class="mt-2 text-sm text-gray-600">
                    Add tasks to keep deliverables and next steps clear for your team and client.
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
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="task in tasks.data" :key="task.id">
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ task.title }}
                            </td>
                            <td class="px-6 py-4">
                                <select
                                    v-if="task.can.update"
                                    :value="task.status"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="updateStatus(task.id, $event.target.value)"
                                >
                                    <option v-for="s in reference.statuses" :key="s" :value="s">{{ s }}</option>
                                </select>
                                <div v-else class="text-sm text-gray-900">{{ task.status }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span v-if="task.assignee">{{ task.assignee.name }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span v-if="task.due_date">{{ task.due_date }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <SecondaryButton v-if="task.can.update" type="button" @click="openEdit(task)">
                                        Edit
                                    </SecondaryButton>
                                    <DangerButton v-if="task.can.delete" type="button" @click="confirmDelete(task)">
                                        Delete
                                    </DangerButton>
                                </div>
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

    <Modal :show="editingOpen" @close="closeEdit">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Edit task</h2>

            <form class="mt-4 grid gap-4 sm:grid-cols-12" @submit.prevent="submitEdit">
                <div class="sm:col-span-12">
                    <InputLabel for="edit_task_title" value="Title" />
                    <TextInput
                        id="edit_task_title"
                        v-model="editForm.title"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError class="mt-2" :message="editForm.errors.title" />
                </div>

                <div class="sm:col-span-4">
                    <InputLabel for="edit_task_status" value="Status" />
                    <select
                        id="edit_task_status"
                        v-model="editForm.status"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option v-for="s in reference.statuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                    <InputError class="mt-2" :message="editForm.errors.status" />
                </div>

                <div class="sm:col-span-4">
                    <InputLabel for="edit_task_assignee" value="Assignee" />
                    <select
                        id="edit_task_assignee"
                        v-model="editForm.assigned_to_user_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Unassigned</option>
                        <option v-for="u in reference.assignees" :key="u.id" :value="String(u.id)">
                            {{ u.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="editForm.errors.assigned_to_user_id" />
                </div>

                <div class="sm:col-span-4">
                    <InputLabel for="edit_task_due_date" value="Due date" />
                    <TextInput
                        id="edit_task_due_date"
                        v-model="editForm.due_date"
                        type="date"
                        class="mt-1 block w-full"
                    />
                    <InputError class="mt-2" :message="editForm.errors.due_date" />
                </div>

                <div class="sm:col-span-12 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="closeEdit">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton :disabled="editForm.processing">
                        Save
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>

    <Modal :show="confirmingDelete" @close="closeDelete">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Delete task</h2>
            <p class="mt-1 text-sm text-gray-600">
                This will delete <span class="font-medium">{{ taskToDelete?.title }}</span>.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <SecondaryButton type="button" @click="closeDelete">
                    Cancel
                </SecondaryButton>
                <DangerButton
                    type="button"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="destroy"
                >
                    Delete
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
