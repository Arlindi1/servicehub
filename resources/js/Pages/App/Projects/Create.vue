<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProjectForm from '@/Pages/App/Projects/Partials/ProjectForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    clients: { type: Array, required: true },
    staffUsers: { type: Array, required: true },
    reference: { type: Object, required: true },
    prefillClientId: { type: Number, default: null },
});

const form = useForm({
    title: '',
    description: '',
    client_id: props.prefillClientId ?? '',
    status: 'Draft',
    priority: 'medium',
    due_date: '',
    staff_ids: [],
});

const submit = () => {
    form.post(route('app.projects.store'));
};
</script>

<template>
    <Head title="New Project" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    New Project
                </h2>

                <Link
                    :href="route('app.projects.index')"
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
                                :href="route('app.projects.index')"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton :disabled="form.processing">
                                Create Project
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
