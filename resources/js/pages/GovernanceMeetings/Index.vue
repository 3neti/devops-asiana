<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CircleAlert,
    FileCheck2,
    Scale,
    UsersRound,
    Vote,
} from '@lucide/vue';
import { index as authorityMatrixIndex } from '@/routes/authority-matrix';
import { index as decisionRecordsIndex } from '@/routes/decision-records';
import { index as governanceMeetingsIndex } from '@/routes/governance-meetings';
import type { GovernanceMeetingCompiler } from '@/types';

defineProps<{ governanceMeetings: GovernanceMeetingCompiler }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Governance Meetings',
                href: governanceMeetingsIndex(),
            },
        ],
    },
});

function ruleSummary(rule: {
    state: string;
    required_governance_weight?: number | null;
}): string {
    if (rule.state !== 'resolved') {
        return 'UNRESOLVED';
    }

    return `${rule.required_governance_weight}% governance weight`;
}
</script>

<template>
    <Head title="Governance Meetings" />

    <div class="min-h-full bg-stone-50/70 dark:bg-slate-950">
        <div
            class="mx-auto flex max-w-[96rem] flex-col gap-7 px-4 py-6 sm:px-6 lg:px-8"
        >
            <header
                class="grid gap-6 border-b border-slate-200 pb-7 lg:grid-cols-[1.2fr_0.8fr] lg:items-end dark:border-slate-800"
            >
                <div>
                    <div
                        class="flex items-center gap-2 text-indigo-700 dark:text-indigo-400"
                    >
                        <Vote class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Partnership governance compiler
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        Attendance is not consent. Silence is not a vote.
                        Execution is not approval.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        The Meeting compiler preserves notice, attendance,
                        quorum, conflicts, recusals, votes, abstentions,
                        outcomes, and Evidence while deriving governance weight
                        from Partnership truth.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <p class="text-xs font-semibold text-slate-500 uppercase">
                        Canonical meeting register
                    </p>
                    <p class="mt-2 font-serif text-3xl font-semibold">
                        {{ governanceMeetings.counts.meetings }}
                    </p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        recorded Partnership meetings
                    </p>
                    <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="font-semibold">
                                {{
                                    governanceMeetings.counts.governing_partners
                                }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Partners
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="font-semibold">
                                {{
                                    governanceMeetings.counts.governance_weight
                                }}%
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Weight
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="font-semibold">
                                {{
                                    governanceMeetings.counts
                                        .decision_record_candidates
                                }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Candidates
                            </dt>
                        </div>
                    </dl>
                </div>
            </header>

            <section
                class="rounded-2xl border border-amber-600/20 bg-amber-50 p-5 dark:bg-amber-950/30"
            >
                <div class="flex items-start gap-3">
                    <CircleAlert
                        class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-300"
                    />
                    <div>
                        <h2
                            class="font-semibold text-amber-950 dark:text-amber-100"
                        >
                            No constitutional voting outcome can presently be
                            compiled.
                        </h2>
                        <p
                            class="mt-1 text-sm leading-6 text-amber-900/80 dark:text-amber-200/80"
                        >
                            Ordinary and Reserved Matter quorum and approval
                            thresholds remain unresolved. The 50/50 deadlock
                            mechanism remains unresolved and subject to
                            Philippine counsel review. Both governing policies
                            are Draft, and collective authority is not yet
                            effective.
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <UsersRound
                            class="size-5 text-indigo-700 dark:text-indigo-400"
                        />
                        <h2 class="font-serif text-2xl font-semibold">
                            Governing Partnership
                        </h2>
                    </div>
                    <div class="mt-5 space-y-3">
                        <div
                            v-for="partner in governanceMeetings.governing_partners"
                            :key="partner.key"
                            class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                        >
                            <div>
                                <p class="font-semibold">{{ partner.name }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ partner.partner_status }}
                                </p>
                            </div>
                            <p class="font-serif text-2xl font-semibold">
                                {{ partner.governance_weight }}%
                            </p>
                        </div>
                    </div>
                    <p
                        class="mt-4 text-xs leading-5 text-slate-500 dark:text-slate-400"
                    >
                        These weights are projected from Partnership Formation;
                        Meeting Records cannot override them.
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <Scale
                            class="size-5 text-indigo-700 dark:text-indigo-400"
                        />
                        <h2 class="font-serif text-2xl font-semibold">
                            Constitutional mechanics
                        </h2>
                    </div>
                    <dl
                        class="mt-5 divide-y divide-slate-200 dark:divide-slate-800"
                    >
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-sm">Ordinary quorum</dt>
                            <dd class="text-sm font-semibold text-amber-700">
                                {{
                                    ruleSummary(
                                        governanceMeetings.decision_rules
                                            .ordinary.quorum,
                                    )
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-sm">Ordinary approval</dt>
                            <dd class="text-sm font-semibold text-amber-700">
                                {{
                                    ruleSummary(
                                        governanceMeetings.decision_rules
                                            .ordinary.approval,
                                    )
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-sm">Reserved Matter quorum</dt>
                            <dd class="text-sm font-semibold text-amber-700">
                                {{
                                    ruleSummary(
                                        governanceMeetings.decision_rules
                                            .reserved.quorum,
                                    )
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-sm">Reserved Matter approval</dt>
                            <dd class="text-sm font-semibold text-amber-700">
                                {{
                                    ruleSummary(
                                        governanceMeetings.decision_rules
                                            .reserved.approval,
                                    )
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-sm">50/50 deadlock</dt>
                            <dd class="text-sm font-semibold text-amber-700">
                                {{
                                    governanceMeetings.decision_rules.deadlock.state.toUpperCase()
                                }}
                            </dd>
                        </div>
                    </dl>
                </article>
            </section>

            <section>
                <p
                    class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                >
                    Meeting anatomy
                </p>
                <h2 class="mt-2 font-serif text-2xl font-semibold">
                    Nine distinct control questions
                </h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="requirement in governanceMeetings.meeting_requirements"
                        :key="requirement.key"
                        class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <p
                            class="text-xs font-semibold text-indigo-700 uppercase dark:text-indigo-400"
                        >
                            {{ requirement.label }}
                        </p>
                        <p
                            class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            {{ requirement.question }}
                        </p>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2 class="font-serif text-2xl font-semibold">
                        Reserved Matter catalogue
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Every Reserved agenda item must cite one exact
                        constitutional matter. Classification cannot be supplied
                        by prose alone.
                    </p>
                    <div class="mt-5 grid gap-2 sm:grid-cols-2">
                        <div
                            v-for="matter in governanceMeetings.reserved_matter_catalog"
                            :key="matter.key"
                            class="rounded-xl bg-slate-50 px-4 py-3 text-sm dark:bg-slate-950"
                        >
                            {{ matter.label }}
                        </div>
                    </div>
                </article>

                <article
                    class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-6 dark:border-slate-700 dark:bg-slate-900/50"
                >
                    <FileCheck2 class="size-7 text-slate-400" />
                    <h2 class="mt-4 font-serif text-2xl font-semibold">
                        No meetings recorded
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Formation facts are not reconstructed as meeting
                        minutes. An adopted, fully evidenced outcome may produce
                        a candidate for the Decision Record compiler, but never
                        a silent canonical write.
                    </p>
                    <div
                        class="mt-5 flex flex-wrap gap-4 text-sm font-semibold"
                    >
                        <Link
                            :href="authorityMatrixIndex()"
                            class="inline-flex items-center gap-2 text-indigo-700 hover:underline dark:text-indigo-400"
                        >
                            Authority Matrix <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            :href="decisionRecordsIndex()"
                            class="inline-flex items-center gap-2 text-indigo-700 hover:underline dark:text-indigo-400"
                        >
                            Decision Records <ArrowRight class="size-4" />
                        </Link>
                    </div>
                </article>
            </section>
        </div>
    </div>
</template>
