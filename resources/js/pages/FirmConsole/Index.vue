<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    BadgeCheck,
    BookOpenCheck,
    Braces,
    CircleHelp,
    Landmark,
    Scale,
    ShieldCheck,
} from '@lucide/vue';
import FirmMap from '@/components/FirmMap.vue';
import { dashboard } from '@/routes';
import type {
    FormationCompletion,
    ResolvedPartnership,
    ResolutionState,
} from '@/types';

defineProps<{
    formationCompletion: FormationCompletion;
    partnership: ResolvedPartnership;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Firm Console',
                href: dashboard(),
            },
        ],
    },
});

function stateClass(state: ResolutionState): string {
    return {
        resolved:
            'border-teal-600/20 bg-teal-50 text-teal-800 dark:bg-teal-950/40 dark:text-teal-300',
        unresolved:
            'border-amber-600/20 bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
        counsel_review:
            'border-violet-600/20 bg-violet-50 text-violet-800 dark:bg-violet-950/40 dark:text-violet-300',
        not_yet_ready:
            'border-slate-400/30 bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    }[state];
}
</script>

<template>
    <Head title="Firm Console" />

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
                        <Scale class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Professional Partnership Operating System
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        A system of professional accountability.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        Inspect who is responsible, where authority comes from,
                        which decisions remain open, and what requires
                        counsel—before the Firm accepts institutional risk.
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
                                Compiler state
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    partnership.compiler_status ===
                                    'consistent_with_open_decisions'
                                        ? 'Consistent with open decisions'
                                        : 'Conflict detected'
                                }}
                            </p>
                        </div>
                        <BadgeCheck class="size-8 text-teal-600" />
                    </div>
                    <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <p class="text-xl font-semibold">
                                {{ partnership.reports.decision_gaps.length }}
                            </p>
                            <p
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Open decisions
                            </p>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <p class="text-xl font-semibold">
                                {{ partnership.reports.counsel_review.length }}
                            </p>
                            <p
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Counsel items
                            </p>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <p class="text-xl font-semibold">
                                {{
                                    partnership.reports.responsibility_gaps
                                        .length
                                }}
                            </p>
                            <p
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Coverage gaps
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            <section
                class="rounded-2xl border border-amber-300 bg-amber-50/70 p-5 sm:p-6 dark:border-amber-900 dark:bg-amber-950/20"
            >
                <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <div>
                        <div
                            class="flex items-center gap-2 text-amber-800 dark:text-amber-300"
                        >
                            <Landmark class="size-4" />
                            <p
                                class="text-xs font-semibold tracking-[0.18em] uppercase"
                            >
                                Firm commencement
                            </p>
                        </div>
                        <h2 class="mt-3 font-serif text-2xl font-semibold">
                            Formation facts do not become operating authority by
                            implication.
                        </h2>
                        <p
                            class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            A counsel-confirmed requirement set, exact principal
                            office and effective date, both Founding Partners,
                            separately resolved capital records, and complete
                            Evidence must converge before formation-derived
                            Offices can activate.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-2 self-start text-center">
                        <div
                            class="rounded-xl border border-amber-200 bg-white p-3 dark:border-amber-900 dark:bg-slate-900"
                        >
                            <p class="text-xl font-semibold">
                                {{
                                    formationCompletion.counts
                                        .verified_commencements
                                }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Verified records
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-amber-200 bg-white p-3 dark:border-amber-900 dark:bg-slate-900"
                        >
                            <p class="text-xl font-semibold">
                                {{
                                    formationCompletion.counts
                                        .office_activation_bases
                                }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Activation bases
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-amber-200 bg-white p-3 dark:border-amber-900 dark:bg-slate-900"
                        >
                            <p class="text-xl font-semibold">
                                {{
                                    formationCompletion.firm_commenced
                                        ? 'Commenced'
                                        : 'Not verified'
                                }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Firm state
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-5 grid gap-2 border-t border-amber-200 pt-5 text-xs sm:grid-cols-[1fr_auto_1fr_auto_1fr] sm:items-center dark:border-amber-900"
                >
                    <div
                        class="rounded-lg bg-white/80 p-3 text-center font-semibold dark:bg-slate-900/80"
                    >
                        Resolved Partnership facts
                    </div>
                    <ArrowRight
                        class="mx-auto hidden size-4 text-amber-700 sm:block"
                    />
                    <div
                        class="rounded-lg bg-white/80 p-3 text-center font-semibold dark:bg-slate-900/80"
                    >
                        Counsel-confirmed completion
                    </div>
                    <ArrowRight
                        class="mx-auto hidden size-4 text-amber-700 sm:block"
                    />
                    <div
                        class="rounded-lg bg-white/80 p-3 text-center font-semibold dark:bg-slate-900/80"
                    >
                        Formation-derived office activation
                    </div>
                </div>

                <ul
                    v-if="!formationCompletion.firm_commenced"
                    class="mt-5 grid gap-2 text-sm sm:grid-cols-2"
                >
                    <li
                        v-for="finding in [
                            ...formationCompletion.reports.conflicts,
                            ...formationCompletion.reports.formation_gaps,
                            ...formationCompletion.reports.legal_gaps,
                            ...formationCompletion.reports.capital_gaps,
                            ...formationCompletion.reports.evidence_gaps,
                            ...formationCompletion.reports.counsel_review,
                        ]"
                        :key="`${finding.code}-${finding.message}`"
                        class="rounded-lg bg-white/80 px-3 py-2 text-slate-700 dark:bg-slate-900/80 dark:text-slate-300"
                    >
                        {{ finding.message }}
                    </li>
                </ul>
            </section>

            <FirmMap
                :firm="partnership.formation.firm"
                :partners="partnership.projections.partnership"
                :management="partnership.projections.management"
                :responsibilities="partnership.projections.responsibilities"
                :economics="partnership.projections.economics"
            />

            <section class="grid gap-5 xl:grid-cols-[1.25fr_0.75fr]">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                            >
                                Partnership compiler
                            </p>
                            <h2 class="mt-2 font-serif text-2xl font-semibold">
                                Agreement follows resolved truth
                            </h2>
                        </div>
                        <Braces class="size-6 text-slate-400" />
                    </div>

                    <div
                        class="mt-6 grid gap-2 text-sm md:grid-cols-[1fr_auto_1fr_auto_1fr] md:items-center"
                    >
                        <div
                            class="rounded-xl bg-slate-50 p-4 dark:bg-slate-950"
                        >
                            <p class="font-semibold">Partnership Formation</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Founders, governance, office, and economics
                            </p>
                        </div>
                        <ArrowRight
                            class="mx-auto hidden size-4 text-slate-400 md:block"
                        />
                        <div
                            class="rounded-xl border border-teal-600/30 bg-teal-50 p-4 dark:bg-teal-950/30"
                        >
                            <p class="font-semibold">ResolvedPartnership</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Validation, gaps, conflicts, counsel review
                            </p>
                        </div>
                        <ArrowRight
                            class="mx-auto hidden size-4 text-slate-400 md:block"
                        />
                        <div
                            class="rounded-xl bg-slate-50 p-4 dark:bg-slate-950"
                        >
                            <p class="font-semibold">Agreement Projection</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Future Markdown and HTML drafting output
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3">
                        <article
                            v-for="decision in partnership.decisions"
                            :key="decision.key"
                            class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[1fr_auto] dark:border-slate-800"
                        >
                            <div>
                                <p class="text-sm font-semibold">
                                    {{ decision.label }}
                                </p>
                                <p
                                    class="mt-1 text-sm text-slate-600 dark:text-slate-400"
                                >
                                    {{ decision.statement }}
                                </p>
                            </div>
                            <div
                                class="flex flex-wrap items-start gap-2 sm:justify-end"
                            >
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-semibold tracking-wide uppercase"
                                    :class="
                                        stateClass(decision.institutional_state)
                                    "
                                >
                                    Intent:
                                    {{ decision.institutional_state_label }}
                                </span>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-semibold tracking-wide uppercase"
                                    :class="stateClass(decision.legal_state)"
                                >
                                    Legal: {{ decision.legal_state_label }}
                                </span>
                            </div>
                        </article>
                    </div>
                </div>

                <div class="grid content-start gap-5">
                    <section
                        class="rounded-2xl border border-amber-500/30 bg-amber-50/60 p-5 dark:bg-amber-950/20"
                    >
                        <div class="flex items-center gap-3">
                            <CircleHelp
                                class="size-5 text-amber-700 dark:text-amber-400"
                            />
                            <h2 class="font-serif text-xl font-semibold">
                                Decision gaps
                            </h2>
                        </div>
                        <ul class="mt-4 grid gap-3">
                            <li
                                v-for="gap in partnership.reports.decision_gaps"
                                :key="gap.key"
                                class="rounded-xl border border-amber-500/20 bg-white/70 p-3 dark:bg-slate-950/50"
                            >
                                <p class="text-sm font-semibold">
                                    {{ gap.label }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-slate-600 dark:text-slate-400"
                                >
                                    {{ gap.statement }}
                                </p>
                            </li>
                        </ul>
                    </section>

                    <section
                        class="rounded-2xl border border-violet-500/30 bg-violet-50/60 p-5 dark:bg-violet-950/20"
                    >
                        <div class="flex items-center gap-3">
                            <BookOpenCheck
                                class="size-5 text-violet-700 dark:text-violet-400"
                            />
                            <h2 class="font-serif text-xl font-semibold">
                                Counsel review
                            </h2>
                        </div>
                        <p
                            class="mt-2 text-sm text-slate-600 dark:text-slate-400"
                        >
                            Institutional intent is not a claim of legal
                            validity.
                        </p>
                        <ul class="mt-4 grid gap-2 text-sm">
                            <li
                                v-for="item in partnership.reports
                                    .counsel_review"
                                :key="item.key"
                                class="flex gap-2"
                            >
                                <span
                                    class="mt-2 size-1 rounded-full bg-violet-600"
                                />
                                {{ item.label }}
                            </li>
                        </ul>
                    </section>

                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-3">
                            <ShieldCheck class="size-5 text-teal-600" />
                            <h2 class="font-serif text-xl font-semibold">
                                Authority has three gates
                            </h2>
                        </div>
                        <div class="mt-4 grid gap-3 text-sm">
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950"
                            >
                                <p class="font-semibold">1. Client Mandate</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    What the Client authorizes the Firm to do.
                                </p>
                            </div>
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950"
                            >
                                <p class="font-semibold">2. Firm Authority</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    What the Firm delegates to a person or
                                    office.
                                </p>
                            </div>
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950"
                            >
                                <p class="font-semibold">
                                    3. Specific Approval
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    What authorizes this action, at this risk,
                                    now.
                                </p>
                            </div>
                        </div>
                    </section>

                    <div
                        v-if="partnership.reports.conflicts.length"
                        class="rounded-2xl border border-red-500/30 bg-red-50 p-5 dark:bg-red-950/30"
                    >
                        <div class="flex gap-3">
                            <AlertTriangle class="size-5 text-red-700" />
                            <div>
                                <p class="font-semibold">
                                    Compiler conflicts detected
                                </p>
                                <p
                                    v-for="conflict in partnership.reports
                                        .conflicts"
                                    :key="conflict.code"
                                    class="mt-1 text-sm"
                                >
                                    {{ conflict.message }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <footer
                class="flex flex-col gap-2 border-t border-slate-200 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800"
            >
                <p>{{ partnership.disclaimer }}</p>
                <p class="font-mono">resources/institution/partnership.json</p>
            </footer>
        </div>
    </div>
</template>
