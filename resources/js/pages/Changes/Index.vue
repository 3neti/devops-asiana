<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    FileCheck2,
    FileSearch,
    GitPullRequestArrow,
    History,
    KeyRound,
    RotateCcw,
    ShieldAlert,
    Siren,
    Wrench,
} from '@lucide/vue';
import { index as changesIndex } from '@/routes/changes';
import { show as showDocument } from '@/routes/institutional-documents';
import type { ChangeCompiler } from '@/types';

defineProps<{
    changes: ChangeCompiler;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Changes',
                href: changesIndex(),
            },
        ],
    },
});

const lifecycle = [
    ['Request', 'Record the need and desired outcome'],
    ['Classify', 'Standard, Normal, or Emergency'],
    ['Review', 'Assess plan, risk, dependencies, and recovery'],
    ['Approve', 'Required authorities decide explicitly'],
    ['Schedule', 'Bound the execution and communication window'],
    ['Execute', 'Named actor performs only the approved plan'],
    ['Verify', 'Compare observed state with expected outcomes'],
    ['Close', 'Preserve outcome, review, follow-up, and evidence'],
];

const authorityLayers = [
    {
        title: 'Client Mandate',
        icon: FileCheck2,
        detail: 'The Open Engagement must permit the system, environment, and kind of action.',
    },
    {
        title: 'Active Access Grant',
        icon: KeyRound,
        detail: 'The named executor must hold current, matching technical access authority.',
    },
    {
        title: 'Specific Change Approval',
        icon: GitPullRequestArrow,
        detail: 'The particular plan, risk, recovery, and execution window require their own authority.',
    },
];

const changeClasses = [
    {
        title: 'Standard Change',
        icon: History,
        detail: 'Repeatable and low risk, under a current pre-authorized definition whose eligibility is confirmed for this execution.',
    },
    {
        title: 'Normal Change',
        icon: Wrench,
        detail: 'Receives specific technical review and risk-proportionate Client and Firm approval before scheduling.',
    },
    {
        title: 'Emergency Change',
        icon: Siren,
        detail: 'Uses expedited authority only when delay increases material harm; disclosure, evidence, and retrospective review remain mandatory.',
    },
];
</script>

<template>
    <Head title="Changes" />

    <div class="min-h-full bg-stone-50/70 dark:bg-slate-950">
        <div
            class="mx-auto flex max-w-[96rem] flex-col gap-7 px-4 py-6 sm:px-6 lg:px-8"
        >
            <header
                class="grid gap-6 border-b border-slate-200 pb-7 lg:grid-cols-[1.2fr_0.8fr] lg:items-end dark:border-slate-800"
            >
                <div>
                    <div
                        class="flex items-center gap-2 text-teal-700 dark:text-teal-400"
                    >
                        <GitPullRequestArrow class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Production execution gate
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        No Ticket, No Change.
                    </h1>
                    <p
                        class="mt-3 font-serif text-xl font-semibold text-teal-800 dark:text-teal-300"
                    >
                        No Production Change Without Recovery.
                    </p>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        A deployment is not its own authority. The Firm must
                        connect every production alteration to an Open
                        Engagement, Client Mandate, specific approvals, a named
                        authorized executor, a viable recovery path, independent
                        verification, and durable evidence.
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
                                Execution readiness
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    changes.governing_policies.every(
                                        (policy) => policy.operative,
                                    )
                                        ? 'Required policies are Effective'
                                        : 'No Change may be authorized'
                                }}
                            </p>
                        </div>
                        <BadgeCheck
                            v-if="
                                changes.governing_policies.every(
                                    (policy) => policy.operative,
                                )
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
                                {{ changes.counts.change_records }}
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
                                {{ changes.counts.executable_authority }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Executable
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ changes.counts.evidence_records }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Evidence
                            </dt>
                        </div>
                    </dl>
                </div>
            </header>

            <section
                v-if="changes.reports.readiness_gaps.length"
                class="rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30"
            >
                <div class="flex items-start gap-3">
                    <ShieldAlert
                        class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-400"
                    />
                    <div class="min-w-0 flex-1">
                        <h2 class="font-serif text-xl font-semibold">
                            Institutional readiness gaps
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300"
                        >
                            Draft policy does not authorize production
                            execution. Every required policy must be Effective,
                            approved, integrity-verified, and evidenced.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-for="policy in changes.governing_policies"
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
                    class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                >
                    Change lifecycle
                </p>
                <h2 class="mt-2 font-serif text-2xl font-semibold">
                    Execution cannot collapse the decisions that precede or
                    follow it
                </h2>
                <div
                    class="mt-6 grid gap-2 2xl:grid-cols-[repeat(15,minmax(0,auto))] 2xl:items-center"
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
                        class="size-5 text-teal-700 dark:text-teal-400"
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

            <section
                class="rounded-2xl border border-teal-200 bg-teal-50/60 p-5 sm:p-6 dark:border-teal-950 dark:bg-teal-950/20"
            >
                <div class="grid gap-5 lg:grid-cols-[auto_1fr] lg:items-start">
                    <div
                        class="rounded-xl bg-white p-4 text-teal-700 shadow-sm dark:bg-slate-900 dark:text-teal-400"
                    >
                        <RotateCcw class="size-7" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Recovery doctrine
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Recovery readiness is a precondition, not an
                            afterthought
                        </h2>
                        <p
                            class="mt-3 max-w-4xl text-sm leading-6 text-slate-700 dark:text-slate-300"
                        >
                            The record must define a viable strategy, steps,
                            triggers, owner, estimated recovery time, and a
                            confirmed recovery point before production
                            execution. An irreversible plan cannot pass the
                            ordinary Change gate merely because someone approved
                            deployment.
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    v-for="changeClass in changeClasses"
                    :key="changeClass.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <component
                        :is="changeClass.icon"
                        class="size-5 text-slate-500"
                    />
                    <h2 class="mt-4 font-serif text-xl font-semibold">
                        {{ changeClass.title }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        {{ changeClass.detail }}
                    </p>
                </article>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <p
                    class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                >
                    Change standard
                </p>
                <h2 class="mt-2 font-serif text-2xl font-semibold">
                    Questions every Change Record must answer
                </h2>
                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="requirement in changes.record_requirements"
                        :key="requirement.key"
                        class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                    >
                        <p class="text-sm font-semibold">
                            {{ requirement.label }}
                        </p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            {{ requirement.question }}
                        </p>
                    </article>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Canonical register
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Production Change Records
                        </h2>
                    </div>
                    <GitPullRequestArrow class="size-6 text-slate-400" />
                </div>
                <div
                    v-if="changes.change_records.length === 0"
                    class="mt-5 rounded-xl border border-dashed border-slate-300 px-5 py-8 text-center dark:border-slate-700"
                >
                    <p class="font-semibold">
                        No Production Change Records are recorded.
                    </p>
                    <p
                        class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500"
                    >
                        This is the correct current state: no canonical
                        Engagement is Open, no Production Access Grant is
                        Active, and the governing policies remain Draft. The
                        Console does not manufacture execution authority.
                    </p>
                </div>
                <div v-else class="mt-5 grid gap-3">
                    <article
                        v-for="change in changes.change_records"
                        :key="change.key"
                        class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <p class="font-semibold">{{ change.title }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ change.change_type_label }} ·
                                    {{ change.executor?.name }} ·
                                    {{ change.client_name }} ·
                                    {{ change.scope?.system }} /
                                    {{ change.scope?.environment }}
                                </p>
                            </div>
                            <span
                                class="rounded-full border px-2.5 py-1 text-xs font-semibold"
                                :class="
                                    change.may_execute_change
                                        ? 'border-teal-300 bg-teal-50 text-teal-800'
                                        : 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                "
                                >{{ change.lifecycle_status_label }}</span
                            >
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>
