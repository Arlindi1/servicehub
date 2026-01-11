<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientForm from '@/Pages/App/Clients/Partials/ClientForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    notes: '',
});

const submit = () => {
    form.post(route('app.clients.store'));
};
</script>

<template>
    <Head title="New Client" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    New Client
                </h2>

                <Link
                    :href="route('app.clients.index')"
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
                        <ClientForm :form="form" />

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <Link
                                :href="route('app.clients.index')"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>

                            <PrimaryButton :disabled="form.processing">
                                Create Client
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

