<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CircleAlert,
    ClipboardCheck,
    FileCheck2,
    Gavel,
    ListChecks,
    ShieldCheck,
} from '@lucide/vue';
import { index as authorityMatrixIndex } from '@/routes/authority-matrix';
import { index as decisionRecordsIndex } from '@/routes/decision-records';
import type { DecisionRecordCompiler } from '@/types';

defineProps<{ decisionRecords: DecisionRecordCompiler }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Decisions & Approvals',
                href: decisionRecordsIndex(),
            },
        ],
    },
});

const stages = [
    { key: 'proposal', icon: ListChecks },
    { key: 'review', icon: ClipboardCheck },
    { key: 'risk', icon: ShieldCheck },
    { key: 'authority', icon: Gavel },
    { key: 'decision', icon: FileCheck2 },
    { key: 'execution', icon: ArrowRight },
    { key: 'verification', icon: ClipboardCheck },
    { key: 'evidence', icon: FileCheck2 },
];
</script>

<template>
    <Head title="Decisions & Approvals" />

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
                        <FileCheck2 class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Institutional record compiler
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        A decision is attributable authority applied to an
                        explicit proposal—not an action inferred after the fact.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        The ledger preserves proposal, review, risk, approval,
                        effective time, execution, verification, and Evidence as
                        separate institutional facts.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <p class="text-xs font-semibold text-slate-500 uppercase">
                        Canonical ledger
                    </p>
                    <p class="mt-2 font-serif text-3xl font-semibold">
                        {{ decisionRecords.counts.decisions }}
                    </p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        recorded Firm decisions
                    </p>
                    <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <p class="font-semibold">
                                {{
                                    decisionRecords.counts.executable_decisions
                                }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Executable
                            </p>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <p class="font-semibold">
                                {{ decisionRecords.counts.executions }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Executed
                            </p>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <p class="font-semibold">
                                {{ decisionRecords.counts.verifications }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Verified
                            </p>
                        </div>
                    </div>
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
                            The record surface is ready; operative
                            decision-making is not.
                        </h2>
                        <p
                            class="mt-1 text-sm leading-6 text-amber-900/80 dark:text-amber-200/80"
                        >
                            Formation facts are not backfilled as decisions.
                            Both governing policies remain Draft and the
                            Authority Matrix currently has no effective entries.
                        </p>
                        <Link
                            :href="authorityMatrixIndex()"
                            class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-amber-950 hover:underline dark:text-amber-100"
                        >
                            Inspect the Authority Matrix
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>
                </div>
            </section>

            <section>
                <div class="mb-4">
                    <p
                        class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                    >
                        Institutional chain
                    </p>
                    <h2 class="mt-2 font-serif text-2xl font-semibold">
                        Everything material remains a distinct record.
                    </h2>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="stage in stages"
                        :key="stage.key"
                        class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <component
                            :is="stage.icon"
                            class="size-5 text-indigo-700 dark:text-indigo-400"
                        />
                        <template
                            v-for="requirement in decisionRecords.record_requirements.filter(
                                (item) => item.key === stage.key,
                            )"
                            :key="requirement.key"
                        >
                            <h3 class="mt-4 font-serif text-xl font-semibold">
                                {{ requirement.label }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                            >
                                {{ requirement.question }}
                            </p>
                        </template>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2 class="font-serif text-2xl font-semibold">
                        Governing sources
                    </h2>
                    <div class="mt-5 space-y-3">
                        <div
                            v-for="policy in decisionRecords.governing_policies"
                            :key="policy.key"
                            class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                        >
                            <div>
                                <p class="font-semibold">{{ policy.title }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ policy.version }} · {{ policy.purpose }}
                                </p>
                            </div>
                            <span
                                class="rounded-full border border-amber-600/20 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-300"
                                >{{ policy.status_label }}</span
                            >
                        </div>
                    </div>
                </article>

                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2 class="font-serif text-2xl font-semibold">
                        Readiness report
                    </h2>
                    <ul class="mt-5 space-y-3">
                        <li
                            v-for="gap in decisionRecords.reports
                                .readiness_gaps"
                            :key="gap.code"
                            class="flex gap-3 rounded-xl bg-slate-50 p-4 text-sm leading-6 dark:bg-slate-950"
                        >
                            <CircleAlert
                                class="mt-0.5 size-4 shrink-0 text-amber-600"
                            />
                            <span>{{ gap.message }}</span>
                        </li>
                    </ul>
                </article>
            </section>

            <section
                class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-8 text-center dark:border-slate-700 dark:bg-slate-900/50"
            >
                <FileCheck2 class="mx-auto size-8 text-slate-400" />
                <h2 class="mt-4 font-serif text-2xl font-semibold">
                    No institutional decisions recorded
                </h2>
                <p
                    class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400"
                >
                    This is truthful current state, not missing sample data.
                    Records enter this ledger only when the Firm makes and
                    evidences an actual decision under operative authority.
                </p>
            </section>
        </div>
    </div>
</template>
