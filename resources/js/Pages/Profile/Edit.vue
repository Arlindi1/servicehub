<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    area: {
        type: String,
        default: 'app',
    },
});

const Layout = computed(() => (props.area === 'portal' ? PortalLayout : AppLayout));
const profileUpdateRoute = computed(() => (props.area === 'portal' ? 'portal.profile.update' : 'app.profile.update'));
const profileDestroyRoute = computed(() => (props.area === 'portal' ? 'portal.profile.destroy' : 'app.profile.destroy'));
</script>

<template>
    <Head title="Profile" />

    <component :is="Layout">
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :profile-update-route="profileUpdateRoute"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <DeleteUserForm
                        :profile-destroy-route="profileDestroyRoute"
                        class="max-w-xl"
                    />
                </div>
            </div>
        </div>
    </component>
</template>
