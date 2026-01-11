<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: { type: Object, required: true },
    updates: { type: Array, required: true },
});

const formatDateTime = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleString();
};
</script>

<template>
    <Head title="Dashboard" />

    <PortalLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600" v-if="$page.props.auth.organization?.name">
                            {{ $page.props.auth.organization.name }}
                        </div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">
                            Welcome, {{ $page.props.auth.user.name }}.
                        </div>
                        <div class="mt-2 text-sm text-gray-600">
                            View project updates, download deliverables, and review invoices.
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        :href="route('portal.projects.index')"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow"
                    >
                        <div class="text-sm font-semibold text-gray-900">My Active Projects</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ stats.active_projects }}</div>
                        <div class="mt-1 text-sm text-gray-600">In progress.</div>
                    </Link>

                    <Link
                        :href="route('portal.invoices.index')"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow"
                    >
                        <div class="text-sm font-semibold text-gray-900">Outstanding Invoices</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ stats.outstanding_invoices }}</div>
                        <div class="mt-1 text-sm text-gray-600">Sent/Overdue.</div>
                    </Link>

                    <Link
                        :href="route('portal.projects.index')"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow"
                    >
                        <div class="text-sm font-semibold text-gray-900">Projects</div>
                        <div class="mt-1 text-sm text-gray-600">Updates, files, and discussion.</div>
                    </Link>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 p-6">
                        <div class="text-sm font-semibold text-gray-900">Recent Updates</div>
                        <div class="mt-1 text-sm text-gray-600">Comments and files from your projects.</div>
                    </div>

                    <div v-if="updates.length === 0" class="p-6 text-sm text-gray-600">
                        No updates yet.
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <Link
                            v-for="item in updates"
                            :key="`${item.type}-${item.id}`"
                            :href="item.url"
                            class="block p-6 transition hover:bg-gray-50"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-gray-900">
                                        {{ item.title }}
                                        <span v-if="item.project_title" class="text-gray-500">·</span>
                                        <span v-if="item.project_title" class="text-gray-700">{{ item.project_title }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-600">
                                        {{ item.message }}
                                    </div>
                                </div>
                                <div class="shrink-0 text-xs text-gray-500">
                                    {{ formatDateTime(item.created_at) }}
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
