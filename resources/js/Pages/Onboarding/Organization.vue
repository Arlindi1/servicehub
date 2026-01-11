<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    userEmail: { type: String, required: true },
});

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('onboarding.organization.store'));
};
</script>

<template>
    <Head title="Create Organization" />

    <OnboardingLayout>
        <h1 class="text-lg font-semibold text-gray-900">
            Create your organization
        </h1>

        <p class="mt-1 text-sm text-gray-600">
            You’re signed in as <span class="font-medium">{{ props.userEmail }}</span>.
        </p>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Organization name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="organization"
                    placeholder="Acme Studio"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton :disabled="form.processing">
                    Continue
                </PrimaryButton>
            </div>
        </form>
    </OnboardingLayout>
</template>

