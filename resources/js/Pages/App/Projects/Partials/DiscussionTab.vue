<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    projectId: { type: Number, required: true },
    comments: { type: Object, required: true },
    can: { type: Object, required: true },
});

const orderedComments = computed(() => (props.comments?.data ?? []).slice().reverse());

const displayName = (comment) => {
    if (comment.author?.name) {
        return comment.author.name;
    }

    return comment.author_type === 'client' ? 'Client' : 'Staff';
};

const badgeClass = (comment) => {
    return comment.author_type === 'client'
        ? 'bg-blue-50 text-blue-700 ring-blue-200'
        : 'bg-gray-50 text-gray-700 ring-gray-200';
};

const commentForm = useForm({
    body: '',
});

const submitComment = () => {
    commentForm.post(route('app.projects.comments.store', props.projectId), {
        preserveScroll: true,
        onSuccess: () => commentForm.reset(),
    });
};

const confirmingDelete = ref(false);
const commentToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (comment) => {
    commentToDelete.value = comment;
    confirmingDelete.value = true;
};

const closeDelete = () => {
    commentToDelete.value = null;
    confirmingDelete.value = false;
    deleteForm.reset();
};

const destroy = () => {
    if (!commentToDelete.value) {
        return;
    }

    deleteForm.delete(route('app.comments.destroy', commentToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDelete(),
    });
};
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-lg border border-gray-200 p-5">
            <div class="text-sm font-semibold text-gray-900">Discussion</div>

            <div v-if="orderedComments.length === 0" class="mt-4 text-center">
                <div class="text-gray-900">No comments yet.</div>
                <div class="mt-2 text-sm text-gray-600">
                    Start the thread to keep updates and feedback in one place.
                </div>
            </div>

            <div v-else class="mt-4 space-y-4">
                <div
                    v-for="comment in orderedComments"
                    :key="comment.id"
                    class="rounded-lg border border-gray-200 p-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="text-sm font-medium text-gray-900">
                                {{ displayName(comment) }}
                            </div>
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                :class="badgeClass(comment)"
                            >
                                {{ comment.author_type === 'client' ? 'Client' : 'Staff' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="text-xs text-gray-500">
                                {{ comment.created_at }}
                            </div>

                            <DangerButton
                                v-if="comment.can?.delete"
                                type="button"
                                class="px-2 py-1 text-xs"
                                @click="confirmDelete(comment)"
                            >
                                Delete
                            </DangerButton>
                        </div>
                    </div>

                    <div class="mt-3 whitespace-pre-wrap text-sm text-gray-800">
                        {{ comment.body }}
                    </div>
                </div>
            </div>

            <div v-if="comments.links?.length" class="mt-5">
                <Pagination :links="comments.links" />
            </div>
        </div>

        <div v-if="can.create" class="rounded-lg border border-gray-200 p-5">
            <div class="text-sm font-semibold text-gray-900">Post a comment</div>

            <form class="mt-4 space-y-3" @submit.prevent="submitComment">
                <div>
                    <InputLabel for="comment_body" value="Message" />
                    <textarea
                        id="comment_body"
                        v-model="commentForm.body"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Write an update, ask a question, or share feedback..."
                    />
                    <InputError class="mt-2" :message="commentForm.errors.body" />
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="commentForm.processing">
                        Post
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>

    <Modal :show="confirmingDelete" @close="closeDelete">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Delete comment</h2>
            <p class="mt-1 text-sm text-gray-600">
                This will permanently delete this comment.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <SecondaryButton type="button" @click="closeDelete">
                    Cancel
                </SecondaryButton>
                <DangerButton
                    type="button"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="destroy"
                >
                    Delete
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>

