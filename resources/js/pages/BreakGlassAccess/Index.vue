<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    Clock3,
    Eye,
    FileSearch,
    KeyRound,
    LockKeyhole,
    RotateCcwKey,
    ShieldAlert,
    ShieldCheck,
    Siren,
} from '@lucide/vue';
import { index as breakGlassAccessIndex } from '@/routes/break-glass-access';
import { show as showDocument } from '@/routes/institutional-documents';
import type { BreakGlassAccessCompiler } from '@/types';

defineProps<{ breakGlassAccess: BreakGlassAccessCompiler }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Break-glass Access',
                href: breakGlassAccessIndex(),
            },
        ],
    },
});

const lifecycle = [
    ['Request', 'State the emergency and material harm'],
    ['Authorize', 'Record three independent authorities'],
    ['Activate', 'Verify one named account and actor'],
    ['Observe', 'Monitor and evidence every material action'],
    ['Expire', 'End authority at the absolute boundary'],
    ['Remove', 'Revoke and independently verify permissions'],
    ['Disclose', 'Inform the Client and Responsible Partner'],
    ['Review', 'Assess necessity, scope, outcome, and controls'],
    ['Close', 'Make a separate evidenced decision'],
];

const authorityLayers = [
    {
        title: 'Client Emergency Authority',
        detail: 'The Engagement and specific Client authority permit the Firm to use the emergency path for the bounded system and purpose.',
        icon: ShieldCheck,
    },
    {
        title: 'Firm Emergency Authority',
        detail: 'A competent Firm authority accepts the professional and operational risk of this specific activation.',
        icon: Siren,
    },
    {
        title: 'Independent Security Authority',
        detail: 'An approver other than the actor confirms scope, identity controls, monitoring, and the absolute time boundary.',
        icon: Eye,
    },
];
</script>

<template>
    <Head title="Break-glass Access" />

    <div class="min-h-full bg-stone-50/70 dark:bg-slate-950">
        <div
            class="mx-auto flex max-w-[96rem] flex-col gap-7 px-4 py-6 sm:px-6 lg:px-8"
        >
            <header
                class="grid gap-6 border-b border-slate-200 pb-7 lg:grid-cols-[1.2fr_0.8fr] lg:items-end dark:border-slate-800"
            >
                <div>
                    <div
                        class="flex items-center gap-2 text-amber-700 dark:text-amber-400"
                    >
                        <ShieldAlert class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Emergency privilege gate
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        Emergency access is temporary authority, not standing
                        privilege.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        Break-glass exists only for a defined emergency under an
                        Open Engagement and declared Incident. It names one
                        actor, limits scope, requires independent approval and
                        observation, expires automatically, leaves a complete
                        record, and receives disclosure and review.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-semibold text-slate-500 uppercase"
                            >
                                Activation readiness
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    breakGlassAccess.governing_policies.every(
                                        (policy) => policy.operative,
                                    )
                                        ? 'Emergency control policies are operative'
                                        : 'No Break-glass activation is authorized'
                                }}
                            </p>
                        </div>
                        <BadgeCheck
                            v-if="
                                breakGlassAccess.reports.readiness_gaps
                                    .length === 0
                            "
                            class="size-8 text-teal-600"
                        />
                        <ShieldAlert v-else class="size-8 text-amber-600" />
                    </div>
                    <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ breakGlassAccess.counts.access_records }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Recorded
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{
                                    breakGlassAccess.counts
                                        .active_emergency_authority
                                }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Active
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ breakGlassAccess.counts.awaiting_review }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Review
                            </dt>
                        </div>
                    </dl>
                </div>
            </header>

            <section
                v-if="breakGlassAccess.reports.readiness_gaps.length"
                class="rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30"
            >
                <div class="flex items-start gap-3">
                    <ShieldAlert
                        class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-400"
                    />
                    <div>
                        <h2 class="font-serif text-xl font-semibold">
                            Institutional readiness gaps
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300"
                        >
                            Draft policy cannot create emergency authority. All
                            four governing policies must be Effective, approved,
                            integrity-verified, and evidenced before activation.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-for="policy in breakGlassAccess.governing_policies"
                                :key="policy.key"
                                :href="
                                    showDocument(
                                        `policies/${policy.key}-policy`,
                                    )
                                "
                                class="inline-flex items-center gap-2 rounded-lg border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:text-amber-200 dark:hover:bg-amber-950"
                            >
                                <FileSearch class="size-4" />
                                {{ policy.title }} · {{ policy.status_label }}
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <p
                    class="text-xs font-semibold tracking-[0.18em] text-amber-700 uppercase dark:text-amber-400"
                >
                    Emergency lifecycle
                </p>
                <h2 class="mt-2 font-serif text-2xl font-semibold">
                    Each stage proves a different institutional fact
                </h2>
                <div
                    class="mt-6 grid gap-2 2xl:grid-cols-[repeat(17,minmax(0,auto))] 2xl:items-center"
                >
                    <template
                        v-for="(stage, index) in lifecycle"
                        :key="stage[0]"
                    >
                        <div
                            class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950"
                        >
                            <p class="text-sm font-semibold">{{ stage[0] }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                {{ stage[1] }}
                            </p>
                        </div>
                        <ArrowRight
                            v-if="index < lifecycle.length - 1"
                            class="mx-auto hidden size-4 text-slate-400 2xl:block"
                        />
                    </template>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    v-for="layer in authorityLayers"
                    :key="layer.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <component
                        :is="layer.icon"
                        class="size-5 text-amber-700 dark:text-amber-400"
                    />
                    <h2 class="mt-4 font-serif text-xl font-semibold">
                        {{ layer.title }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        {{ layer.detail }}
                    </p>
                </article>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    class="rounded-2xl border border-teal-200 bg-teal-50/60 p-5 dark:border-teal-950 dark:bg-teal-950/20"
                >
                    <Clock3 class="size-6 text-teal-700 dark:text-teal-400" />
                    <h2 class="mt-4 font-serif text-xl font-semibold">
                        Absolute expiry
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300"
                    >
                        Authority ends at the approved timestamp even if
                        technical removal has not yet been verified. Continued
                        need requires a new record and new approvals.
                    </p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <KeyRound
                        class="size-6 text-amber-700 dark:text-amber-400"
                    />
                    <h2 class="mt-4 font-serif text-xl font-semibold">
                        Credential ≠ authority
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Vault access, authentication, or credential possession
                        proves none of the Engagement, Incident, approval,
                        scope, or time gates.
                    </p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <RotateCcwKey
                        class="size-6 text-amber-700 dark:text-amber-400"
                    />
                    <h2 class="mt-4 font-serif text-xl font-semibold">
                        Removal and rotation
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Post-use cleanup proves permission removal, independent
                        verification, and credential rotation where the custody
                        design requires it.
                    </p>
                </article>
            </section>

            <section
                class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5 sm:p-6 dark:border-rose-950 dark:bg-rose-950/20"
            >
                <div class="flex items-start gap-4">
                    <LockKeyhole
                        class="mt-1 size-6 shrink-0 text-rose-700 dark:text-rose-400"
                    />
                    <div>
                        <h2 class="font-serif text-2xl font-semibold">
                            Secret material is prohibited
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300"
                        >
                            {{ breakGlassAccess.prohibited_content }} Canonical
                            records retain ownership, custody, vault references,
                            rotation facts, and evidence—not the credentials.
                        </p>
                    </div>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-center gap-2">
                    <FileSearch class="size-5 text-slate-500" />
                    <h2 class="font-serif text-2xl font-semibold">
                        Break-glass Access Record standard
                    </h2>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="requirement in breakGlassAccess.record_requirements"
                        :key="requirement.key"
                        class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                    >
                        <p class="text-sm font-semibold">
                            {{ requirement.label }}
                        </p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            {{ requirement.question }}
                        </p>
                    </article>
                </div>
            </section>

            <section
                class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-8 text-center dark:border-slate-700 dark:bg-slate-900/70"
            >
                <ShieldAlert class="mx-auto size-8 text-slate-400" />
                <h2 class="mt-4 font-serif text-2xl font-semibold">
                    {{
                        breakGlassAccess.access_records.length === 0
                            ? 'No Break-glass Access Records yet'
                            : 'Canonical Break-glass Access Records'
                    }}
                </h2>
                <p
                    v-if="breakGlassAccess.access_records.length === 0"
                    class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500"
                >
                    The registry is intentionally empty. The control model is
                    executable without inventing emergencies, credentials, or
                    authority.
                </p>
                <div v-else class="mt-6 grid gap-4 text-left lg:grid-cols-2">
                    <article
                        v-for="record in breakGlassAccess.access_records"
                        :key="record.key"
                        class="rounded-xl border border-slate-200 p-5 dark:border-slate-800"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <p class="font-mono text-xs text-slate-500">
                                {{ record.key }}
                            </p>
                            <span
                                class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-slate-800"
                            >
                                {{ record.lifecycle_status_label }}
                            </span>
                        </div>
                        <h3 class="mt-3 font-serif text-xl font-semibold">
                            {{ record.title }}
                        </h3>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ record.client_name }} ·
                            {{ record.incident_title }} ·
                            {{ record.window_state }}
                        </p>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>
