<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    projectId: { type: Number, required: true },
    files: { type: Object, required: true },
    reference: { type: Object, required: true },
    can: { type: Object, required: true },
});

const uploadForm = useForm({
    file_type: 'Client Upload',
    file: null,
});

const fileInput = ref(null);

const onFileChange = (event) => {
    uploadForm.file = event.target.files?.[0] ?? null;
};

const submitUpload = () => {
    uploadForm.post(route('portal.projects.files.store', props.projectId), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
            uploadForm.file_type = 'Client Upload';
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const hasFiles = computed(() => (props.files?.data?.length ?? 0) > 0);

const formatBytes = (bytes) => {
    const size = Number(bytes ?? 0);
    if (!Number.isFinite(size) || size <= 0) return '-';
    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
    const value = size / Math.pow(1024, i);
    return `${value.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
};
</script>

<template>
    <div class="space-y-6">
        <div v-if="can.create" class="rounded-lg border border-gray-200 p-5">
            <div class="text-sm font-semibold text-gray-900">Upload client file</div>

            <form class="mt-4 grid gap-4 sm:grid-cols-12" @submit.prevent="submitUpload">
                <div class="sm:col-span-4">
                    <InputLabel for="upload_file_type" value="File type" />
                    <select
                        id="upload_file_type"
                        v-model="uploadForm.file_type"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        disabled
                    >
                        <option v-for="t in reference.types" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <InputError class="mt-2" :message="uploadForm.errors.file_type" />
                </div>

                <div class="sm:col-span-8">
                    <InputLabel for="upload_file" value="File" />
                    <input
                        id="upload_file"
                        ref="fileInput"
                        type="file"
                        accept=".pdf,.png,.jpg,.jpeg,.docx,.xlsx,.zip"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="onFileChange"
                    />
                    <div class="mt-1 text-xs text-gray-500">
                        Max 20MB. Allowed: PDF, PNG, JPG, DOCX, XLSX, ZIP.
                    </div>
                    <InputError class="mt-2" :message="uploadForm.errors.file" />
                </div>

                <div class="sm:col-span-12 flex items-center justify-end">
                    <PrimaryButton :disabled="uploadForm.processing || !uploadForm.file">
                        Upload
                    </PrimaryButton>
                </div>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200">
            <div v-if="!hasFiles" class="p-8 text-center">
                <div class="text-gray-900">No files yet.</div>
                <div class="mt-2 text-sm text-gray-600">
                    Deliverables and your uploads will appear here.
                </div>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Uploader
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Uploaded
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Size
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="file in files.data" :key="file.id">
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ file.original_name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ file.file_type }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span v-if="file.uploader">
                                    {{ file.uploader.name }} ({{ file.uploader_type }})
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span v-if="file.created_at">{{ file.created_at }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ formatBytes(file.size) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a
                                    :href="route('files.download', file.id)"
                                    class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                                >
                                    Download
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="files.links?.length" class="border-t border-gray-100 p-5">
                <Pagination :links="files.links" />
            </div>
        </div>
    </div>
</template>

