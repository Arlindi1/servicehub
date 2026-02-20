<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
    canDemoLogin: {
        type: Boolean,
        default: false,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const featureCards = [
    {
        title: 'Projects, Tasks, and Files',
        description:
            'Track work from kickoff to delivery with threaded discussion, file uploads, and status-based task management.',
    },
    {
        title: 'Client + Team Access',
        description:
            'Separate staff and client portals with role-based permissions so recruiters can see realistic multi-user behavior.',
    },
    {
        title: 'Invoices + Activity Feed',
        description:
            'Create invoices, export PDFs, and review a full activity trail for comments, uploads, and project updates.',
    },
];

const quickChecks = [
    'No email setup needed for demo access.',
    'One-click login provisions the demo user automatically.',
    'Seeded data makes the app feel complete immediately.',
];
</script>

<template>
    <Head title="ServiceHub" />

    <div class="servicehub-landing relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute -left-24 -top-20 h-72 w-72 rounded-full blur-3xl radial-one"></div>
        <div class="pointer-events-none absolute -right-28 top-40 h-80 w-80 rounded-full blur-3xl radial-two"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-72 texture"></div>

        <div class="relative mx-auto max-w-6xl px-6 pb-14 pt-8 sm:px-8 lg:px-10">
            <header class="fade-up flex items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="grid h-11 w-11 place-items-center rounded-xl logo-shell">
                        <ApplicationLogo class="h-7 w-7 text-cyan-500 fill-current" />
                    </div>
                    <div>
                        <p class="brand-title text-lg leading-tight">ServiceHub</p>
                        <p class="brand-caption text-xs uppercase tracking-[0.2em]">
                            Client Portal Platform
                        </p>
                    </div>
                </div>

                <nav v-if="canLogin" class="flex items-center gap-2">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-100"
                    >
                        Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Log In
                        </Link>

                        <Link
                            v-if="canDemoLogin"
                            :href="route('demo.login')"
                            class="rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-400"
                        >
                            Open Demo
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </header>

            <main class="mt-14 grid gap-10 lg:grid-cols-[1.1fr,0.9fr] lg:items-start">
                <section class="fade-up delay-1">
                    <p class="eyebrow text-xs uppercase tracking-[0.24em] text-cyan-700">
                        Built For Delivery Teams
                    </p>
                    <h1 class="hero-title mt-4 text-4xl leading-tight text-slate-900 sm:text-5xl">
                        Run projects, client communication, and billing in one clean workspace.
                    </h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-600">
                        ServiceHub helps small agencies and freelancers stay organized with shared
                        visibility for staff and clients. This demo includes realistic seeded data
                        for projects, invoices, comments, uploads, and notifications.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            v-if="canDemoLogin"
                            :href="route('demo.login')"
                            class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Continue As Demo
                        </Link>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400"
                        >
                            Use Login Form
                        </Link>
                    </div>

                    <div class="mt-7 rounded-xl border border-cyan-100 bg-cyan-50/60 p-4 text-sm text-cyan-900">
                        <p class="font-semibold">Recruiter Demo Credentials</p>
                        <p class="mt-1">
                            <span class="font-mono">demo@servicehub.test</span> /
                            <span class="font-mono">password</span>
                        </p>
                    </div>
                </section>

                <section class="fade-up delay-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">Live Demo Snapshot</p>
                        <p class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Online
                        </p>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="metric-card rounded-xl p-3">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Projects</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">5</p>
                        </div>
                        <div class="metric-card rounded-xl p-3">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Invoices</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">3</p>
                        </div>
                        <div class="metric-card rounded-xl p-3">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Team</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">6</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="timeline-row rounded-lg border border-slate-200 px-3 py-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                Project
                            </p>
                            <p class="mt-1 text-sm font-medium text-slate-800">Website Redesign</p>
                            <p class="text-sm text-slate-500">In Progress · due in 14 days</p>
                        </div>
                        <div class="timeline-row rounded-lg border border-slate-200 px-3 py-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                Latest Activity
                            </p>
                            <p class="mt-1 text-sm font-medium text-slate-800">Client uploaded a file</p>
                            <p class="text-sm text-slate-500">Notification + audit trail captured</p>
                        </div>
                        <div class="timeline-row rounded-lg border border-slate-200 px-3 py-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                Invoice
                            </p>
                            <p class="mt-1 text-sm font-medium text-slate-800">INV-00002 · Sent</p>
                            <p class="text-sm text-slate-500">PDF export available</p>
                        </div>
                    </div>
                </section>
            </main>

            <section class="mt-14 grid gap-4 md:grid-cols-3">
                <article
                    v-for="(card, idx) in featureCards"
                    :key="card.title"
                    class="fade-up rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    :class="idx === 0 ? 'delay-2' : idx === 1 ? 'delay-3' : 'delay-4'"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">
                        Feature {{ idx + 1 }}
                    </p>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ card.title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ card.description }}</p>
                </article>
            </section>


            <footer class="mt-12 text-center text-xs text-slate-500">
                ServiceHub demo experience · Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}
            </footer>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap');

.servicehub-landing {
    --surface: #f3f7fb;
    --radial-1: #83e0ff;
    --radial-2: #ffd39a;
    --ink: #0f172a;
    background: linear-gradient(165deg, #f6fbff 0%, var(--surface) 45%, #eef2f7 100%);
    color: var(--ink);
    font-family: 'Manrope', 'Segoe UI', sans-serif;
}

.radial-one {
    background: radial-gradient(circle, var(--radial-1) 0%, rgba(131, 224, 255, 0) 70%);
}

.radial-two {
    background: radial-gradient(circle, var(--radial-2) 0%, rgba(255, 211, 154, 0) 72%);
}

.texture {
    opacity: 0.28;
    background-image:
        linear-gradient(rgba(15, 23, 42, 0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(15, 23, 42, 0.08) 1px, transparent 1px);
    background-size: 38px 38px;
    mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0));
}

.brand-title,
.hero-title {
    font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
}

.brand-caption {
    color: #475569;
}

.eyebrow {
    font-weight: 700;
}

.logo-shell {
    background: linear-gradient(150deg, rgba(56, 189, 248, 0.18), rgba(15, 23, 42, 0.08));
    border: 1px solid rgba(15, 23, 42, 0.12);
}

.metric-card {
    border: 1px solid #e2e8f0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.timeline-row {
    background: #ffffff;
}

.fade-up {
    opacity: 0;
    transform: translateY(20px);
    animation: fade-up 0.7s ease forwards;
}

.delay-1 {
    animation-delay: 0.08s;
}

.delay-2 {
    animation-delay: 0.14s;
}

.delay-3 {
    animation-delay: 0.2s;
}

.delay-4 {
    animation-delay: 0.26s;
}

@keyframes fade-up {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
