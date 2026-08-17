<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    CalendarClock,
    CircleCheckBig,
    FileSearch,
    History,
    ShieldAlert,
    UserRoundCheck,
} from '@lucide/vue';
import { index as correctiveActionsIndex } from '@/routes/corrective-actions';
import { show as showDocument } from '@/routes/institutional-documents';
import type { CorrectiveActionCompiler } from '@/types';

defineProps<{ correctiveActions: CorrectiveActionCompiler }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Corrective Actions', href: correctiveActionsIndex() },
        ],
    },
});

const lifecycle = [
    ['Finding', 'Preserve the source and governing requirement'],
    ['Assign', 'Name one owner under explicit authority'],
    ['Remediate', 'Work to bounded acceptance criteria'],
    ['Claim', 'Owner records completion and evidence'],
    ['Verify', 'An independent person tests the outcome'],
    ['Close', 'Competent authority makes a separate decision'],
];

const separations = [
    {
        title: 'Ownership is singular',
        detail: 'A team may perform the work, but exactly one accountable owner accepts the obligation and due date.',
        icon: UserRoundCheck,
    },
    {
        title: 'Dates retain history',
        detail: 'A revised date never overwrites the original. Every extension states actor, authority, reason, time, and evidence.',
        icon: History,
    },
    {
        title: 'Verification is independent',
        detail: 'The owner may claim completion. Another qualified person verifies the declared acceptance criteria before closure.',
        icon: BadgeCheck,
    },
];
</script>

<template>
    <Head title="Corrective Actions" />

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
                        <CircleCheckBig class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Institutional remediation register
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        Findings remain accountable until independently verified
                        and expressly closed.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        Corrective Actions preserve obligations created by
                        Incidents, failed Changes, emergency-access reviews,
                        Access Reviews, Policy Exceptions, and other evidenced
                        findings. Closing the source never silently closes the
                        remediation.
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
                                Assignment readiness
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    correctiveActions.reports.readiness_gaps
                                        .length === 0
                                        ? 'Base assignment authority is operative'
                                        : 'No corrective assignment is yet authorized'
                                }}
                            </p>
                        </div>
                        <BadgeCheck
                            v-if="
                                correctiveActions.reports.readiness_gaps
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
                                {{
                                    correctiveActions.counts.corrective_actions
                                }}
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
                                {{ correctiveActions.counts.overdue }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Overdue
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ correctiveActions.counts.ready_for_closure }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Closure
                            </dt>
                        </div>
                    </dl>
                </div>
            </header>

            <section
                v-if="correctiveActions.reports.readiness_gaps.length"
                class="rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30"
            >
                <div class="flex items-start gap-3">
                    <ShieldAlert
                        class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-400"
                    />
                    <div>
                        <h2 class="font-serif text-xl font-semibold">
                            Institutional readiness gap
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300"
                        >
                            Assignment and escalation authority must come from
                            an Effective, approved, integrity-verified,
                            evidenced Authority and Delegation Policy.
                            Source-specific policies become mandatory when their
                            records are present.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-for="policy in correctiveActions.governing_policies"
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
                    Accountability lifecycle
                </p>
                <h2 class="mt-2 font-serif text-2xl font-semibold">
                    Each stage proves a different fact
                </h2>
                <div
                    class="mt-6 grid gap-2 xl:grid-cols-[repeat(11,minmax(0,auto))] xl:items-center"
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
                            class="mx-auto hidden size-4 text-slate-400 xl:block"
                        />
                    </template>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    v-for="item in separations"
                    :key="item.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <component
                        :is="item.icon"
                        class="size-5 text-teal-700 dark:text-teal-400"
                    />
                    <h2 class="mt-4 font-serif text-xl font-semibold">
                        {{ item.title }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        {{ item.detail }}
                    </p>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[0.75fr_1.25fr]">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-2">
                        <CalendarClock
                            class="size-5 text-teal-700 dark:text-teal-400"
                        />
                        <h2 class="font-serif text-2xl font-semibold">
                            Required record
                        </h2>
                    </div>
                    <div class="mt-5 space-y-3">
                        <div
                            v-for="requirement in correctiveActions.record_requirements"
                            :key="requirement.key"
                            class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                        >
                            <p class="text-sm font-semibold">
                                {{ requirement.label }}
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                {{ requirement.question }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2 class="font-serif text-2xl font-semibold">
                        Corrective Action Register
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        The canonical register is intentionally empty. Records
                        are added only when a real evidenced finding creates a
                        remediation obligation.
                    </p>
                    <div
                        v-if="correctiveActions.corrective_actions.length === 0"
                        class="mt-6 rounded-xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700"
                    >
                        <CircleCheckBig class="mx-auto size-8 text-slate-400" />
                        <p class="mt-3 font-semibold">
                            No corrective actions recorded
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Absence of records is not a claim that the Firm has
                            no risk; it states that no canonical finding has yet
                            been entered.
                        </p>
                    </div>
                    <div v-else class="mt-6 space-y-4">
                        <article
                            v-for="action in correctiveActions.corrective_actions"
                            :key="action.key"
                            class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold text-teal-700 uppercase dark:text-teal-400"
                                    >
                                        {{ action.source_type_label }}
                                    </p>
                                    <h3 class="mt-1 font-semibold">
                                        {{ action.title }}
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{
                                            action.owner?.name ??
                                            'Owner unassigned'
                                        }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold dark:bg-slate-800"
                                    >{{ action.lifecycle_status_label }}</span
                                >
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section
                class="rounded-2xl border border-teal-200 bg-teal-50/60 p-5 dark:border-teal-950 dark:bg-teal-950/20"
            >
                <p
                    class="text-sm font-semibold text-teal-900 dark:text-teal-200"
                >
                    {{ correctiveActions.boundary }}
                </p>
            </section>
        </div>
    </div>
</template>
