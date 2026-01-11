<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap gap-1" aria-label="Pagination">
        <template v-for="(link, index) in links" :key="index">
            <span
                v-if="!link.url"
                class="rounded-md px-3 py-2 text-sm text-gray-400"
                v-html="link.label"
            />

            <Link
                v-else
                :href="link.url"
                class="rounded-md border px-3 py-2 text-sm"
                :class="
                    link.active
                        ? 'border-gray-900 bg-gray-900 text-white'
                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                "
                preserve-scroll
                v-html="link.label"
            />
        </template>
    </nav>
</template>

