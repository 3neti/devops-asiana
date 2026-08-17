<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    Building2,
    ClipboardCheck,
    FileSearch,
    Fingerprint,
    Scale,
    ShieldAlert,
    UserRoundCheck,
} from '@lucide/vue';
import { index as clientAcceptance } from '@/routes/client-acceptance';
import { show as showDocument } from '@/routes/institutional-documents';
import type {
    ClientAcceptanceCompiler,
    ProspectiveClientProjection,
} from '@/types';

defineProps<{
    acceptance: ClientAcceptanceCompiler;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Client Acceptance',
                href: clientAcceptance(),
            },
        ],
    },
});

const decisionPath = [
    {
        label: 'Prospective Client',
        detail: 'Identity and proposed relationship',
    },
    {
        label: 'Acceptance Review',
        detail: 'Required checks, conflicts, and risk',
    },
    {
        label: 'Explicit Decision',
        detail: 'Authority, conditions, and evidence',
    },
    {
        label: 'Accepted Client',
        detail: 'Eligible for Engagement consideration',
    },
];

function statusClass(
    status: ProspectiveClientProjection['review_status'],
): string {
    return {
        identified:
            'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        under_review:
            'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
        decision_recorded:
            'border-teal-300 bg-teal-50 text-teal-800 dark:border-teal-900 dark:bg-teal-950/40 dark:text-teal-300',
        expired:
            'border-violet-300 bg-violet-50 text-violet-800 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300',
        withdrawn:
            'border-stone-300 bg-stone-100 text-stone-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300',
    }[status];
}
</script>

<template>
    <Head title="Client Acceptance" />

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
                            Professional acceptance gate
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        No Client Without Acceptance.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        The Firm must know whom it may serve, what risks and
                        relationships were considered, who decided, under what
                        authority, and where the evidence is retained.
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
                                Acceptance readiness
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    acceptance.governing_policy.operative
                                        ? 'Governing policy is Effective'
                                        : 'Not ready for operative decisions'
                                }}
                            </p>
                        </div>
                        <BadgeCheck
                            v-if="acceptance.governing_policy.operative"
                            class="size-8 text-teal-600"
                        />
                        <ShieldAlert v-else class="size-8 text-amber-600" />
                    </div>
                    <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ acceptance.counts.prospective_clients }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Prospects
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ acceptance.counts.by_outcome.accepted }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Accepted
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ acceptance.counts.evidence_records }}
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
                v-if="!acceptance.governing_policy.operative"
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
                            {{ acceptance.governing_policy.title }} version
                            {{ acceptance.governing_policy.version }} is
                            {{ acceptance.governing_policy.status_label }}. The
                            compiler will reject any acceptance or rejection
                            decision until the governing policy is Effective.
                        </p>
                        <Link
                            :href="
                                showDocument(
                                    'policies/client-acceptance-policy',
                                )
                            "
                            class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-amber-800 hover:text-amber-950 dark:text-amber-300 dark:hover:text-amber-200"
                        >
                            <FileSearch class="size-4" />
                            Inspect governing policy
                        </Link>
                    </div>
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
                            Decision boundary
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Acceptance permits consideration, not work
                        </h2>
                    </div>
                    <UserRoundCheck class="size-6 text-slate-400" />
                </div>

                <div
                    class="mt-6 grid gap-2 lg:grid-cols-[repeat(7,minmax(0,auto))] lg:items-center"
                >
                    <template
                        v-for="(stage, index) in decisionPath"
                        :key="stage.label"
                    >
                        <div
                            class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950"
                        >
                            <p class="text-sm font-semibold">
                                {{ stage.label }}
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                {{ stage.detail }}
                            </p>
                        </div>
                        <ArrowRight
                            v-if="index < decisionPath.length - 1"
                            class="mx-auto hidden size-4 text-slate-400 lg:block"
                        />
                    </template>
                </div>

                <div
                    class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-400"
                >
                    An approved Engagement remains a separate gate. Acceptance
                    alone creates no scope, Client Mandate, production access,
                    or permission to begin work.
                </div>
            </section>

            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Review standard
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Required acceptance assessments
                        </h2>
                    </div>
                    <p class="hidden text-sm text-slate-500 sm:block">
                        {{ acceptance.required_assessments.length }} required
                        checks
                    </p>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="assessment in acceptance.required_assessments"
                        :key="assessment.key"
                        class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-2">
                            <ClipboardCheck class="size-4 text-teal-600" />
                            <h3 class="text-sm font-semibold">
                                {{ assessment.label }}
                            </h3>
                        </div>
                        <p
                            class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400"
                        >
                            {{ assessment.question }}
                        </p>
                    </article>
                </div>
            </section>

            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Acceptance ledger
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Prospective Clients and decisions
                        </h2>
                    </div>
                    <Building2 class="size-6 text-slate-400" />
                </div>

                <div
                    v-if="acceptance.prospective_clients.length === 0"
                    class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900"
                >
                    <Building2
                        class="mx-auto size-9 text-slate-400 dark:text-slate-600"
                    />
                    <h3 class="mt-4 font-serif text-xl font-semibold">
                        No Prospective Clients are recorded
                    </h3>
                    <p
                        class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500"
                    >
                        The repository contains no invented Client data. A
                        future record must preserve identity, the full review,
                        conflicts and related parties, risk, authority,
                        decision, validity, and evidence.
                    </p>
                </div>

                <div v-else class="mt-5 grid gap-4 lg:grid-cols-2">
                    <article
                        v-for="client in acceptance.prospective_clients"
                        :key="client.key"
                        class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                                >
                                    {{ client.entity_type }} ·
                                    {{ client.jurisdiction }}
                                </p>
                                <h3
                                    class="mt-1 font-serif text-xl font-semibold"
                                >
                                    {{
                                        client.display_name ?? client.legal_name
                                    }}
                                </h3>
                            </div>
                            <span
                                class="rounded-full border px-2.5 py-1 text-[10px] font-semibold tracking-wide uppercase"
                                :class="statusClass(client.review_status)"
                            >
                                {{ client.review_status_label }}
                            </span>
                        </div>
                        <p
                            class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            {{ client.proposed_scope }}
                        </p>
                        <div
                            v-if="client.decision"
                            class="mt-4 rounded-xl bg-slate-50 p-4 dark:bg-slate-950"
                        >
                            <p class="text-sm font-semibold">
                                {{ client.decision.outcome_label }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ client.decision.authority_basis }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-3">
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <Fingerprint class="size-5 text-violet-600" />
                        <h2 class="font-serif text-xl font-semibold">
                            Evidence
                        </h2>
                    </div>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Assessment evidence and decision evidence are distinct.
                        A signed contract or commenced work cannot substitute
                        for the acceptance decision.
                    </p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <Scale class="size-5 text-teal-600" />
                        <h2 class="font-serif text-xl font-semibold">
                            Authority
                        </h2>
                    </div>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Each decision records the decision-maker and authority
                        basis. Authentication, Partner status, origination, or
                        relationship ownership does not imply acceptance
                        authority.
                    </p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <UserRoundCheck class="size-5 text-amber-600" />
                        <h2 class="font-serif text-xl font-semibold">
                            Independence
                        </h2>
                    </div>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        ODTI and 3neti relationships are disclosed related-party
                        facts. Platform affiliation does not override duty to
                        the prospective Client or the Firm.
                    </p>
                </article>
            </section>

            <section
                v-if="
                    acceptance.reports.conflicts.length > 0 ||
                    acceptance.reports.decision_gaps.length > 0 ||
                    acceptance.reports.evidence_gaps.length > 0
                "
                class="rounded-2xl border border-red-300 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/30"
            >
                <div class="flex items-center gap-3">
                    <ShieldAlert
                        class="size-5 text-red-700 dark:text-red-400"
                    />
                    <h2 class="font-serif text-xl font-semibold">
                        Acceptance compiler findings
                    </h2>
                </div>
                <ul class="mt-4 grid gap-2 text-sm">
                    <li
                        v-for="finding in [
                            ...acceptance.reports.conflicts,
                            ...acceptance.reports.decision_gaps,
                            ...acceptance.reports.evidence_gaps,
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
