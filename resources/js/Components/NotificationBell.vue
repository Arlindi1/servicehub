<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const notifications = computed(() => usePage().props.notifications);
const unreadCount = computed(() => notifications.value?.unread_count ?? 0);
const items = computed(() => notifications.value?.items ?? []);

const formatDateTime = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleString();
};

const markAllAsRead = () => {
    router.patch(route('app.notifications.readAll'), {}, { preserveScroll: true, preserveState: true });
};

const markAsRead = (notificationId) => {
    router.patch(route('app.notifications.read', notificationId), {}, { preserveScroll: true, preserveState: true });
};
</script>

<template>
    <Dropdown align="right" width="96">
        <template #trigger>
            <button
                type="button"
                class="relative inline-flex items-center justify-center rounded-md border border-transparent bg-white p-2 text-gray-500 transition hover:text-gray-700 focus:outline-none"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path
                        d="M10 2a6 6 0 00-6 6v2.586l-.707.707A1 1 0 004 13h12a1 1 0 00.707-1.707L16 10.586V8a6 6 0 00-6-6z"
                    />
                    <path d="M10 18a2 2 0 001.995-1.85L12 16H8a2 2 0 001.85 1.995L10 18z" />
                </svg>

                <span
                    v-if="unreadCount > 0"
                    class="absolute -right-0.5 -top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </button>
        </template>

        <template #content>
            <div class="flex items-center justify-between gap-2 px-4 py-3">
                <div class="text-sm font-semibold text-gray-900">Notifications</div>
                <SecondaryButton v-if="unreadCount > 0" type="button" class="text-xs" @click="markAllAsRead">
                    Mark all as read
                </SecondaryButton>
            </div>

            <div class="max-h-96 overflow-y-auto border-t border-gray-100">
                <div v-if="items.length === 0" class="px-4 py-6 text-sm text-gray-600">
                    No notifications yet.
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div
                        v-for="notification in items"
                        :key="notification.id"
                        class="flex gap-3 px-4 py-3"
                    >
                        <div class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="notification.read_at ? 'bg-gray-300' : 'bg-indigo-500'" />

                        <div class="min-w-0 flex-1">
                            <Link
                                :href="notification.data?.url ?? route('app.dashboard')"
                                class="block text-sm font-medium text-gray-900 hover:text-indigo-700"
                            >
                                {{ notification.data?.title ?? 'Notification' }}
                            </Link>
                            <div class="mt-0.5 text-sm text-gray-600">
                                {{ notification.data?.message ?? '' }}
                            </div>
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <div class="text-xs text-gray-500">
                                    {{ formatDateTime(notification.created_at) }}
                                </div>
                                <button
                                    v-if="!notification.read_at"
                                    type="button"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-900"
                                    @click="markAsRead(notification.id)"
                                >
                                    Mark as read
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

