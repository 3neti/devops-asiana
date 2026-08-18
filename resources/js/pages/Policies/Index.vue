<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Archive,
    ArrowRight,
    BadgeCheck,
    BookOpen,
    FileCheck2,
    FileClock,
    Fingerprint,
    Landmark,
    Scale,
    ShieldAlert,
} from '@lucide/vue';
import { index as decisionRecordsIndex } from '@/routes/decision-records';
import { show as showDocument } from '@/routes/institutional-documents';
import { index as policyRegistry } from '@/routes/policy-registry';
import type {
    FormationBootstrap,
    PolicyLifecycleStatus,
    PolicyProjection,
    PolicyRegistry,
} from '@/types';

defineProps<{
    formationBootstrap: FormationBootstrap;
    registry: PolicyRegistry;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Policy Register',
                href: policyRegistry(),
            },
        ],
    },
});

const lifecycle: Array<{
    status: PolicyLifecycleStatus;
    label: string;
}> = [
    { status: 'draft', label: 'Draft' },
    { status: 'under_review', label: 'Under Review' },
    { status: 'approved', label: 'Approved' },
    { status: 'effective', label: 'Effective' },
    { status: 'superseded', label: 'Superseded' },
    { status: 'retired', label: 'Retired' },
];

function documentKey(policy: PolicyProjection): string {
    return policy.current.document_path
        .replace(/^docs\//, '')
        .replace(/\.md$/, '');
}

function statusClass(status: PolicyProjection['current_status']): string {
    return {
        draft: 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        under_review:
            'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
        approved:
            'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300',
        effective:
            'border-teal-300 bg-teal-50 text-teal-800 dark:border-teal-900 dark:bg-teal-950/40 dark:text-teal-300',
        superseded:
            'border-violet-300 bg-violet-50 text-violet-800 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300',
        retired:
            'border-stone-300 bg-stone-100 text-stone-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300',
        invalid:
            'border-red-300 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300',
    }[status];
}
</script>

<template>
    <Head title="Policy Register" />

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
                            Institutional control plane
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        Policy is authority with a history.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        The register separates drafting, approval,
                        effectiveness, exceptions, and evidence. A committed
                        document is not thereby approved or operative.
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
                                Registry compiler
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    registry.compiler_status === 'consistent'
                                        ? 'Structurally consistent'
                                        : registry.compiler_status ===
                                            'consistent_with_gaps'
                                          ? 'Consistent with control gaps'
                                          : 'Conflict detected'
                                }}
                            </p>
                        </div>
                        <BadgeCheck
                            v-if="
                                registry.compiler_status !== 'conflict_detected'
                            "
                            class="size-8 text-teal-600"
                        />
                        <ShieldAlert v-else class="size-8 text-red-600" />
                    </div>
                    <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <p class="text-xl font-semibold">
                                {{ registry.counts.policies }}
                            </p>
                            <p
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Policies
                            </p>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <p class="text-xl font-semibold">
                                {{ registry.counts.by_status.effective }}
                            </p>
                            <p
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Effective
                            </p>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <p class="text-xl font-semibold">
                                {{ registry.counts.exceptions }}
                            </p>
                            <p
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Exceptions
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            <section
                class="rounded-2xl border border-violet-200 bg-violet-50/60 p-5 sm:p-6 dark:border-violet-900 dark:bg-violet-950/20"
            >
                <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                    <div>
                        <div
                            class="flex items-center gap-2 text-violet-700 dark:text-violet-300"
                        >
                            <Landmark class="size-4" />
                            <p
                                class="text-xs font-semibold tracking-[0.18em] uppercase"
                            >
                                Constitutional bootstrap
                            </p>
                        </div>
                        <h2 class="mt-3 font-serif text-2xl font-semibold">
                            Formation may approve the first control policies,
                            but cannot activate them silently.
                        </h2>
                        <p
                            class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            A counsel-confirmed Partnership Agreement, the
                            resolved formation date, unanimous evidenced
                            Founding Partner consent, and exact controlled
                            policy content must converge first. Publication and
                            activation remain separate records in this register.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-2 self-start text-center">
                        <div
                            class="rounded-xl border border-violet-200 bg-white p-3 dark:border-violet-900 dark:bg-slate-900"
                        >
                            <p class="text-xl font-semibold">
                                {{ formationBootstrap.counts.ratifications }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Ratifications
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-violet-200 bg-white p-3 dark:border-violet-900 dark:bg-slate-900"
                        >
                            <p class="text-xl font-semibold">
                                {{
                                    formationBootstrap.counts
                                        .policy_approval_bases
                                }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Approval bases
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-violet-200 bg-white p-3 dark:border-violet-900 dark:bg-slate-900"
                        >
                            <p class="text-xl font-semibold">
                                {{
                                    formationBootstrap.compiler_status ===
                                    'consistent'
                                        ? 'Ready'
                                        : formationBootstrap.compiler_status ===
                                            'consistent_with_gaps'
                                          ? 'Open'
                                          : 'Conflict'
                                }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Formation state
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="
                        formationBootstrap.reports.formation_gaps.length > 0 ||
                        formationBootstrap.reports.consent_gaps.length > 0 ||
                        formationBootstrap.reports.conflicts.length > 0 ||
                        formationBootstrap.reports.evidence_gaps.length > 0 ||
                        formationBootstrap.reports.counsel_review.length > 0
                    "
                    class="mt-5 border-t border-violet-200 pt-4 dark:border-violet-900"
                >
                    <p
                        class="text-xs font-semibold tracking-wide text-violet-800 uppercase dark:text-violet-300"
                    >
                        Decisions still required
                    </p>
                    <ul
                        class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2 dark:text-slate-300"
                    >
                        <li
                            v-for="finding in [
                                ...formationBootstrap.reports.conflicts,
                                ...formationBootstrap.reports.formation_gaps,
                                ...formationBootstrap.reports.consent_gaps,
                                ...formationBootstrap.reports.evidence_gaps,
                                ...formationBootstrap.reports.counsel_review,
                            ]"
                            :key="`${finding.code}-${finding.message}`"
                            class="rounded-lg bg-white/80 px-3 py-2 dark:bg-slate-900/80"
                        >
                            {{ finding.message }}
                        </li>
                    </ul>
                </div>
            </section>

            <section
                class="rounded-2xl border border-teal-200 bg-teal-50/60 p-5 sm:p-6 dark:border-teal-900 dark:bg-teal-950/20"
            >
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center">
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-300"
                        >
                            Policy authority chain
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Approval, publication, and activation are
                            independent records.
                        </h2>
                    </div>
                    <div class="grid flex-[1.4] gap-2 sm:grid-cols-4">
                        <div
                            class="rounded-xl border border-teal-200 bg-white p-3 dark:border-teal-900 dark:bg-slate-900"
                        >
                            <p class="text-lg font-semibold">
                                {{
                                    registry.counts
                                        .available_decision_candidates
                                }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Decision candidates
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-teal-200 bg-white p-3 dark:border-teal-900 dark:bg-slate-900"
                        >
                            <p class="text-lg font-semibold">
                                {{ registry.counts.approval_admissions }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Admissions
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-teal-200 bg-white p-3 dark:border-teal-900 dark:bg-slate-900"
                        >
                            <p class="text-lg font-semibold">
                                {{ registry.counts.publications }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Publications
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-teal-200 bg-white p-3 dark:border-teal-900 dark:bg-slate-900"
                        >
                            <p class="text-lg font-semibold">
                                {{ registry.counts.activations }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                Activations
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="mt-5 flex flex-col gap-3 border-t border-teal-200 pt-4 text-sm leading-6 text-slate-600 sm:flex-row sm:items-center sm:justify-between dark:border-teal-900 dark:text-slate-400"
                >
                    <p>
                        An effective Firm Decision requires exact admission.
                        Only the two allowlisted initial policies may instead
                        cite verified Formation Ratification. Neither path,
                        publication, nor approval alone makes policy operative.
                    </p>
                    <Link
                        :href="decisionRecordsIndex()"
                        class="inline-flex shrink-0 items-center gap-2 font-semibold text-teal-800 hover:underline dark:text-teal-300"
                    >
                        Inspect Decision Records <ArrowRight class="size-4" />
                    </Link>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Controlled lifecycle
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Approval and effectiveness are separate decisions
                        </h2>
                    </div>
                    <FileClock class="size-6 text-slate-400" />
                </div>

                <div
                    class="mt-6 grid gap-2 md:grid-cols-[repeat(11,minmax(0,auto))] md:items-center"
                >
                    <template
                        v-for="(stage, index) in lifecycle"
                        :key="stage.status"
                    >
                        <div
                            class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center dark:border-slate-800 dark:bg-slate-950"
                        >
                            <p class="text-sm font-semibold">
                                {{ stage.label }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ registry.counts.by_status[stage.status] }}
                                current
                            </p>
                        </div>
                        <ArrowRight
                            v-if="index < lifecycle.length - 1"
                            class="mx-auto hidden size-4 text-slate-400 md:block"
                        />
                    </template>
                </div>
            </section>

            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Canonical register
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Current policy versions
                        </h2>
                    </div>
                    <p class="hidden text-sm text-slate-500 sm:block">
                        Source content remains in the repository.
                    </p>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <article
                        v-for="policy in registry.policies"
                        :key="policy.key"
                        class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                                >
                                    Version {{ policy.current_version }}
                                </p>
                                <h3
                                    class="mt-1 font-serif text-xl font-semibold text-slate-950 dark:text-white"
                                >
                                    {{ policy.title }}
                                </h3>
                            </div>
                            <span
                                class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-semibold tracking-wide uppercase"
                                :class="statusClass(policy.current_status)"
                            >
                                {{ policy.current_status_label }}
                            </span>
                        </div>

                        <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-slate-500">Owner</dt>
                                <dd class="mt-1 font-medium">
                                    {{ policy.owner }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">
                                    Approving authority
                                </dt>
                                <dd class="mt-1 font-medium">
                                    {{ policy.approving_authority }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">
                                    Content control
                                </dt>
                                <dd class="mt-1 font-medium">
                                    {{
                                        policy.current.content_integrity ===
                                        'mutable_draft'
                                            ? 'Mutable while Draft'
                                            : policy.current.content_integrity
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Review</dt>
                                <dd class="mt-1 font-medium">
                                    {{ policy.current.review_frequency }}
                                </dd>
                            </div>
                        </dl>

                        <div
                            class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-4 dark:border-slate-800"
                        >
                            <p class="text-xs text-slate-500">
                                {{ policy.versions.length }} retained version
                            </p>
                            <Link
                                :href="showDocument(documentKey(policy))"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-teal-700 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-300"
                            >
                                <BookOpen class="size-4" />
                                Inspect source
                            </Link>
                        </div>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-3">
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <ShieldAlert class="size-5 text-amber-600" />
                        <h2 class="font-serif text-xl font-semibold">
                            Policy exceptions
                        </h2>
                    </div>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        {{
                            registry.counts.exceptions === 0
                                ? 'No exceptions are recorded. Absence of a record is not permission to bypass policy.'
                                : `${registry.counts.exceptions} explicit exception records are indexed.`
                        }}
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <Fingerprint class="size-5 text-violet-600" />
                        <h2 class="font-serif text-xl font-semibold">
                            Evidence linkage
                        </h2>
                    </div>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        {{ registry.counts.evidence_records }} evidence records
                        currently support approvals and exceptions. Execution
                        evidence can never substitute for approval evidence.
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <Archive class="size-5 text-teal-600" />
                        <h2 class="font-serif text-xl font-semibold">
                            Historical integrity
                        </h2>
                    </div>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Supersession retains the prior version. Once review
                        begins, the registered digest detects silent content
                        changes.
                    </p>
                </article>
            </section>

            <section
                v-if="
                    registry.reports.conflicts.length > 0 ||
                    registry.reports.lifecycle_gaps.length > 0 ||
                    registry.reports.evidence_gaps.length > 0
                "
                class="rounded-2xl border border-red-300 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/30"
            >
                <div class="flex items-center gap-3">
                    <FileCheck2 class="size-5 text-red-700 dark:text-red-400" />
                    <h2 class="font-serif text-xl font-semibold">
                        Compiler findings
                    </h2>
                </div>
                <ul class="mt-4 grid gap-2 text-sm">
                    <li
                        v-for="finding in [
                            ...registry.reports.conflicts,
                            ...registry.reports.lifecycle_gaps,
                            ...registry.reports.evidence_gaps,
                        ]"
                        :key="`${finding.code}-${finding.message}`"
                    >
                        <span class="font-semibold">{{ finding.code }}:</span>
                        {{ finding.message }}
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
