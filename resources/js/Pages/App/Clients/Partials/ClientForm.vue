<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-6">
        <div>
            <InputLabel for="name" value="Name" />
            <TextInput
                id="name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full"
                required
                autofocus
                autocomplete="name"
                placeholder="Client name"
            />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="email" value="Email" />
            <TextInput
                id="email"
                v-model="form.email"
                type="email"
                class="mt-1 block w-full"
                required
                autocomplete="email"
                placeholder="client@example.com"
            />
            <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div>
            <InputLabel for="phone" value="Phone (optional)" />
            <TextInput
                id="phone"
                v-model="form.phone"
                type="text"
                class="mt-1 block w-full"
                autocomplete="tel"
                placeholder="(555) 555-5555"
            />
            <InputError class="mt-2" :message="form.errors.phone" />
        </div>

        <div>
            <InputLabel for="notes" value="Notes (optional)" />
            <textarea
                id="notes"
                v-model="form.notes"
                rows="5"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Anything you want your team to remember..."
            />
            <InputError class="mt-2" :message="form.errors.notes" />
        </div>
    </div>
</template>

