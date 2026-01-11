<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProjectForm from '@/Pages/App/Projects/Partials/ProjectForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    project: { type: Object, required: true },
    clients: { type: Array, required: true },
    staffUsers: { type: Array, required: true },
    reference: { type: Object, required: true },
});

const form = useForm({
    title: props.project.title ?? '',
    description: props.project.description ?? '',
    client_id: props.project.client_id ?? '',
    status: props.project.status ?? 'Draft',
    priority: props.project.priority ?? 'medium',
    due_date: props.project.due_date ?? '',
    staff_ids: props.project.staff_ids ?? [],
});

const submit = () => {
    form.put(route('app.projects.update', props.project.id));
};
</script>

<template>
    <Head :title="`Edit: ${project.title}`" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit Project
                </h2>

                <Link
                    :href="route('app.projects.show', project.id)"
                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    Back
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form class="p-6" @submit.prevent="submit">
                        <ProjectForm
                            :form="form"
                            :clients="clients"
                            :staff-users="staffUsers"
                            :statuses="reference.statuses"
                            :priorities="reference.priorities"
                        />

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <Link
                                :href="route('app.projects.show', project.id)"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton :disabled="form.processing">
                                Save Changes
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

