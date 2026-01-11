<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    form: { type: Object, required: true },
    clients: { type: Array, required: true },
    staffUsers: { type: Array, required: true },
    statuses: { type: Array, required: true },
    priorities: { type: Array, required: true },
});
</script>

<template>
    <div class="space-y-6">
        <div>
            <InputLabel for="title" value="Title" />
            <TextInput
                id="title"
                v-model="form.title"
                type="text"
                class="mt-1 block w-full"
                required
                autofocus
                autocomplete="off"
                placeholder="Project title"
            />
            <InputError class="mt-2" :message="form.errors.title" />
        </div>

        <div>
            <InputLabel for="description" value="Description (optional)" />
            <textarea
                id="description"
                v-model="form.description"
                rows="5"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Scope, deliverables, notes..."
            />
            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="client_id" value="Client" />
                <select
                    id="client_id"
                    v-model="form.client_id"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="" disabled>Select a client</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">
                        {{ client.name }} ({{ client.email }})
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.client_id" />
            </div>

            <div>
                <InputLabel for="due_date" value="Due date (optional)" />
                <TextInput
                    id="due_date"
                    v-model="form.due_date"
                    type="date"
                    class="mt-1 block w-full"
                />
                <InputError class="mt-2" :message="form.errors.due_date" />
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="status" value="Status" />
                <select
                    id="status"
                    v-model="form.status"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.status" />
            </div>

            <div>
                <InputLabel for="priority" value="Priority" />
                <select
                    id="priority"
                    v-model="form.priority"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option v-for="p in priorities" :key="p" :value="p">
                        {{ p.charAt(0).toUpperCase() + p.slice(1) }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.priority" />
            </div>
        </div>

        <div>
            <InputLabel value="Assigned staff (optional)" />

            <div class="mt-2 max-h-56 space-y-2 overflow-auto rounded-lg border border-gray-200 p-3">
                <label v-for="user in staffUsers" :key="user.id" class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        :value="user.id"
                        v-model="form.staff_ids"
                    />
                    <span class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900">{{ user.name }}</span>
                        <span class="text-xs text-gray-600">{{ user.email }}</span>
                    </span>
                </label>
            </div>

            <InputError class="mt-2" :message="form.errors.staff_ids" />
        </div>
    </div>
</template>

