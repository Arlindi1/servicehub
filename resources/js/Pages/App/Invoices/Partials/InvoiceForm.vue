<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    clients: { type: Array, required: true },
    projects: { type: Array, required: true },
    submitLabel: { type: String, default: 'Save' },
});

const filteredProjects = computed(() => {
    const clientId = props.form.client_id ? Number(props.form.client_id) : null;
    if (!clientId) {
        return [];
    }

    return props.projects.filter((p) => p.client_id === clientId);
});

watch(
    () => props.form.client_id,
    () => {
        const projectId = props.form.project_id ? Number(props.form.project_id) : null;
        if (!projectId) {
            return;
        }

        const isValid = filteredProjects.value.some((p) => p.id === projectId);
        if (!isValid) {
            props.form.project_id = '';
        }
    },
);

const addItem = () => {
    props.form.items.push({
        description: '',
        quantity: 1,
        unit_price: 0,
    });
};

const removeItem = (index) => {
    if (props.form.items.length <= 1) {
        return;
    }

    props.form.items.splice(index, 1);
};

const cents = (value) => {
    const n = Number(value ?? 0);
    return Number.isFinite(n) ? Math.trunc(n) : 0;
};

const money = (centsValue) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents(centsValue) / 100);
};

const lineTotal = (item) => {
    return cents(item.quantity) * cents(item.unit_price);
};

const subtotal = computed(() => props.form.items.reduce((sum, item) => sum + lineTotal(item), 0));
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-lg border border-gray-200 p-5">
            <div class="grid gap-4 sm:grid-cols-12">
                <div class="sm:col-span-6">
                    <InputLabel for="invoice_client_id" value="Client" />
                    <select
                        id="invoice_client_id"
                        v-model="form.client_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="">Select a client</option>
                        <option v-for="client in clients" :key="client.id" :value="String(client.id)">
                            {{ client.name }} <span v-if="client.email">({{ client.email }})</span>
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.client_id" />
                </div>

                <div class="sm:col-span-6">
                    <InputLabel for="invoice_project_id" value="Project (optional)" />
                    <select
                        id="invoice_project_id"
                        v-model="form.project_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :disabled="!form.client_id"
                    >
                        <option value="">No project</option>
                        <option v-for="project in filteredProjects" :key="project.id" :value="String(project.id)">
                            {{ project.title }}
                        </option>
                    </select>
                    <div class="mt-1 text-xs text-gray-500" v-if="form.client_id && filteredProjects.length === 0">
                        No projects found for this client.
                    </div>
                    <InputError class="mt-2" :message="form.errors.project_id" />
                </div>

                <div class="sm:col-span-6">
                    <InputLabel for="invoice_due_at" value="Due date" />
                    <TextInput id="invoice_due_at" v-model="form.due_at" type="date" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.due_at" />
                </div>

                <div class="sm:col-span-12">
                    <InputLabel for="invoice_notes" value="Notes (optional)" />
                    <textarea
                        id="invoice_notes"
                        v-model="form.notes"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Payment instructions, scope notes, or message to the client..."
                    />
                    <InputError class="mt-2" :message="form.errors.notes" />
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-5">
                <div class="text-sm font-semibold text-gray-900">Line items</div>
                <SecondaryButton type="button" @click="addItem">
                    Add item
                </SecondaryButton>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Description
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Qty
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Unit (cents)
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Line total
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="(item, index) in form.items" :key="index">
                            <td class="px-6 py-4">
                                <TextInput
                                    v-model="item.description"
                                    type="text"
                                    class="block w-full"
                                    placeholder="e.g. Design milestone"
                                />
                                <InputError class="mt-2" :message="form.errors[`items.${index}.description`]" />
                            </td>
                            <td class="px-6 py-4">
                                <TextInput v-model.number="item.quantity" type="number" min="1" class="block w-24" />
                                <InputError class="mt-2" :message="form.errors[`items.${index}.quantity`]" />
                            </td>
                            <td class="px-6 py-4">
                                <TextInput v-model.number="item.unit_price" type="number" min="0" class="block w-32" />
                                <InputError class="mt-2" :message="form.errors[`items.${index}.unit_price`]" />
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ money(lineTotal(item)) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <SecondaryButton type="button" @click="removeItem(index)" :disabled="form.items.length <= 1">
                                    Remove
                                </SecondaryButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 p-5">
                <div class="flex justify-end">
                    <div class="w-full max-w-sm space-y-1 text-sm">
                        <div class="flex items-center justify-between text-gray-700">
                            <div>Subtotal</div>
                            <div class="font-medium text-gray-900">{{ money(subtotal) }}</div>
                        </div>
                        <div class="flex items-center justify-between text-gray-700">
                            <div>Total</div>
                            <div class="font-semibold text-gray-900">{{ money(subtotal) }}</div>
                        </div>
                        <div class="text-xs text-gray-500">
                            Totals are recomputed on the server.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <PrimaryButton :disabled="form.processing">
                {{ submitLabel }}
            </PrimaryButton>
        </div>
    </div>
</template>

