<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    members: { type: Array, required: true },
    reference: { type: Object, required: true },
    can: { type: Object, required: true },
});

const flashSuccess = computed(() => usePage().props.flash?.success);
const flashError = computed(() => usePage().props.flash?.error);
const flashResetLink = computed(() => usePage().props.flash?.reset_link);

const hasStaffMembers = computed(() => props.members.some((m) => m.role === 'Staff'));

const createOpen = ref(false);

const createForm = useForm({
    name: '',
    email: '',
    set_password_later: true,
    password: '',
    password_confirmation: '',
});

const openCreate = () => {
    createOpen.value = true;
};

const closeCreate = () => {
    createOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = () => {
    createForm.post(route('app.team.store'), {
        preserveScroll: true,
        onSuccess: () => closeCreate(),
    });
};

const setRole = (memberId, role) => {
    router.patch(
        route('app.team.role.update', memberId),
        { role },
        { preserveScroll: true, preserveState: true },
    );
};

const setActive = (memberId, isActive) => {
    if (!isActive) {
        const member = props.members.find((m) => m.id === memberId);
        const name = member?.name ?? 'this user';

        if (!confirm(`Deactivate ${name}? They will be signed out immediately.`)) {
            return;
        }
    }

    router.patch(
        route('app.team.active.update', memberId),
        { is_active: isActive },
        { preserveScroll: true, preserveState: true },
    );
};
</script>

<template>
    <Head title="Team" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Team
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="flashSuccess" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ flashSuccess }}
                </div>

                <div v-if="flashError" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ flashError }}
                </div>

                <div v-if="flashResetLink" class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                    <div class="font-semibold">Password setup link</div>
                    <div class="mt-1 break-all">
                        <a :href="flashResetLink" class="text-indigo-700 underline">
                            {{ flashResetLink }}
                        </a>
                    </div>
                    <div class="mt-1 text-xs text-indigo-700">
                        Share this link privately with the new team member so they can set their password.
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-6">
                        <div>
                            <div class="text-base font-semibold text-gray-900">Team members</div>
                            <div class="mt-1 text-sm text-gray-600">
                                Manage access for staff and client users in your organization.
                            </div>
                        </div>

                        <PrimaryButton v-if="can.create" type="button" @click="openCreate">
                            Create Staff
                        </PrimaryButton>
                    </div>

                    <div class="p-6">
                        <div v-if="members.length === 0" class="text-center">
                            <div class="text-gray-900">
                                No team members found.
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                Add staff to help manage projects and client delivery.
                            </div>
                        </div>

                        <div v-else>
                            <div v-if="!hasStaffMembers" class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                                No staff members yet. Create your first staff account to collaborate on projects.
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Name
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Email
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Role
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Status
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Created
                                            </th>
                                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-for="member in members" :key="member.id">
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <div class="font-medium text-gray-900">{{ member.name }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                {{ member.email }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                <div v-if="member.can.update">
                                                    <select
                                                        :value="member.role"
                                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        @change="setRole(member.id, $event.target.value)"
                                                    >
                                                        <option v-for="role in reference.roles" :key="role" :value="role">
                                                            {{ role }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div v-else>
                                                    {{ member.role ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                    :class="member.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                                >
                                                    {{ member.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                <span v-if="member.created_at">{{ member.created_at }}</span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                <div class="flex justify-end gap-2">
                                                    <DangerButton
                                                        v-if="member.can.update && member.is_active"
                                                        type="button"
                                                        @click="setActive(member.id, false)"
                                                    >
                                                        Deactivate
                                                    </DangerButton>
                                                    <SecondaryButton
                                                        v-if="member.can.update && !member.is_active"
                                                        type="button"
                                                        @click="setActive(member.id, true)"
                                                    >
                                                        Reactivate
                                                    </SecondaryButton>
                                                    <span v-if="!member.can.update" class="text-gray-400">
                                                        -
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <Modal :show="createOpen" @close="closeCreate">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Create staff member</h2>
            <p class="mt-1 text-sm text-gray-600">
                Add a staff user to your organization. You can set a password now or share a password setup link.
            </p>

            <form class="mt-6 grid gap-4 sm:grid-cols-12" @submit.prevent="submitCreate">
                <div class="sm:col-span-12">
                    <InputLabel for="team_name" value="Name" />
                    <TextInput id="team_name" v-model="createForm.name" type="text" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="createForm.errors.name" />
                </div>

                <div class="sm:col-span-12">
                    <InputLabel for="team_email" value="Email" />
                    <TextInput id="team_email" v-model="createForm.email" type="email" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="createForm.errors.email" />
                </div>

                <div class="sm:col-span-12">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <Checkbox v-model:checked="createForm.set_password_later" />
                        <span>Set password later (generate a setup link)</span>
                    </label>
                </div>

                <div v-if="!createForm.set_password_later" class="sm:col-span-6">
                    <InputLabel for="team_password" value="Password" />
                    <TextInput id="team_password" v-model="createForm.password" type="password" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="createForm.errors.password" />
                </div>

                <div v-if="!createForm.set_password_later" class="sm:col-span-6">
                    <InputLabel for="team_password_confirmation" value="Confirm password" />
                    <TextInput
                        id="team_password_confirmation"
                        v-model="createForm.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                    />
                </div>

                <div class="sm:col-span-12 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="closeCreate">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton :disabled="createForm.processing">
                        Create
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
