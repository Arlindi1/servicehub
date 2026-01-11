<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: { type: Object, required: true },
    recentActivity: { type: Array, required: true },
});

const formatDateTime = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleString();
};

const subjectHint = (activity) => {
    const description = activity?.description ?? {};
    return (
        description.number ||
        description.title ||
        description.original_name ||
        description.name ||
        description.project_title ||
        ''
    );
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
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
                            Welcome back, {{ $page.props.auth.user.name }}.
                        </div>
                        <div class="mt-2 text-sm text-gray-600">
                            Use the navigation to manage projects, clients, and invoices.
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        :href="route('app.projects.index', { status: 'Active' })"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow"
                    >
                        <div class="text-sm font-semibold text-gray-900">Active Projects</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ stats.active_projects }}</div>
                        <div class="mt-1 text-sm text-gray-600">Currently in progress.</div>
                    </Link>

                    <Link
                        :href="route('app.projects.index', { status: 'Waiting on Client' })"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow"
                    >
                        <div class="text-sm font-semibold text-gray-900">Waiting on Client</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ stats.waiting_on_client }}</div>
                        <div class="mt-1 text-sm text-gray-600">Blocked by feedback.</div>
                    </Link>

                    <Link
                        :href="route('app.projects.index')"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow"
                    >
                        <div class="text-sm font-semibold text-gray-900">Tasks Due Soon</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ stats.tasks_due_soon }}</div>
                        <div class="mt-1 text-sm text-gray-600">Next 7 days.</div>
                    </Link>

                    <Link
                        :href="route('app.invoices.index')"
                        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow"
                    >
                        <div class="text-sm font-semibold text-gray-900">Invoices</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ stats.draft_invoices + stats.sent_invoices }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600">
                            Draft: {{ stats.draft_invoices }} · Sent/Overdue: {{ stats.sent_invoices }}
                        </div>
                    </Link>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 p-6">
                        <div class="text-sm font-semibold text-gray-900">Recent Activity</div>
                        <div class="mt-1 text-sm text-gray-600">Latest updates across your organization.</div>
                    </div>

                    <div v-if="recentActivity.length === 0" class="p-6 text-sm text-gray-600">
                        No activity yet.
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="item in recentActivity" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 p-6">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-gray-900">
                                    {{ item.actor?.name ?? 'System' }}
                                    <span class="text-gray-500">·</span>
                                    <span class="text-gray-700">{{ item.event }}</span>
                                    <span v-if="subjectHint(item)" class="text-gray-500">·</span>
                                    <span v-if="subjectHint(item)" class="text-gray-700">{{ subjectHint(item) }}</span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ formatDateTime(item.created_at) }}
                                </div>
                            </div>
                            <div class="shrink-0">
                                <Link :href="route('app.activity.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    View log
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
