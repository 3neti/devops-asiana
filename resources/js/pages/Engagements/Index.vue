<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    BriefcaseBusiness,
    ClipboardCheck,
    FileCheck2,
    FileSearch,
    Fingerprint,
    KeyRound,
    Scale,
    ShieldAlert,
    UserRoundCog,
} from '@lucide/vue';
import { index as engagementsIndex } from '@/routes/engagements';
import { show as showDocument } from '@/routes/institutional-documents';
import type {
    ClientMandateCompiler,
    EngagementCompiler,
    EngagementProjection,
    MatterCompiler,
} from '@/types';

defineProps<{
    engagements: EngagementCompiler;
    clientMandates: ClientMandateCompiler;
    matters: MatterCompiler;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Engagements',
                href: engagementsIndex(),
            },
        ],
    },
});

const openingPath = [
    { label: 'Accepted Client', detail: 'Current acceptance decision' },
    { label: 'Defined Engagement', detail: 'Scope, terms, and boundaries' },
    { label: 'Responsible Partner', detail: 'Exactly one accountable Partner' },
    { label: 'Client Mandate', detail: 'Client grants bounded authority' },
    { label: 'Firm Approval', detail: 'Decision under delegated authority' },
    { label: 'Opening Record', detail: 'Prerequisites independently verified' },
    { label: 'Client Work', detail: 'Permitted only while every gate holds' },
];

const authorityLayers = [
    {
        title: 'Client Mandate',
        icon: KeyRound,
        detail: 'What the Client authorizes the Firm to do, for identified systems and environments, within a defined period.',
    },
    {
        title: 'Firm Authority',
        icon: Scale,
        detail: 'What the Firm permits an office or professional to decide or perform under the Authority Matrix.',
    },
    {
        title: 'Specific Approval',
        icon: FileCheck2,
        detail: 'Authorization for a particular action inside both the Client Mandate and the actor’s Firm Authority.',
    },
];

function statusClass(
    status: EngagementProjection['operational_status'],
): string {
    return {
        pending:
            'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        approved_not_open:
            'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300',
        open_engagement:
            'border-teal-300 bg-teal-50 text-teal-800 dark:border-teal-900 dark:bg-teal-950/40 dark:text-teal-300',
        blocked_opening:
            'border-red-300 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300',
        suspended:
            'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
        closed: 'border-stone-300 bg-stone-100 text-stone-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300',
    }[status];
}
</script>

<template>
    <Head title="Engagements" />

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
                        <BriefcaseBusiness class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Professional responsibility gate
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        No Client Work Without Engagement.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        An Engagement is the controlling unit of professional
                        responsibility. It binds accepted Client, scope,
                        Responsible Partner, mandate, Firm approval, operating
                        boundaries, and evidence before work may begin.
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
                                Opening readiness
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    engagements.governing_policies.every(
                                        (policy) => policy.operative,
                                    )
                                        ? 'Required policies are Effective'
                                        : 'Not ready to open Engagements'
                                }}
                            </p>
                        </div>
                        <BadgeCheck
                            v-if="
                                engagements.governing_policies.every(
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
                                {{ engagements.counts.engagements }}
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
                                {{ engagements.counts.open_for_client_work }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Work enabled
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ engagements.counts.evidence_records }}
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
                class="rounded-2xl border border-indigo-600/20 bg-indigo-50/60 p-5 sm:p-6 dark:bg-indigo-950/20"
            >
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-indigo-700 uppercase dark:text-indigo-300"
                        >
                            Client mandate and authority compiler
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Firm Authority is not Client authorization
                        </h2>
                        <p
                            class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            A bounded Client action requires an Open Engagement,
                            a current mandate for the exact system and
                            environment, effective Firm Authority for the named
                            actor, separate Specific Approval, and Evidence.
                        </p>
                    </div>
                    <dl class="grid shrink-0 grid-cols-2 gap-2 text-center">
                        <div
                            class="rounded-lg bg-white px-4 py-3 dark:bg-slate-900"
                        >
                            <dd class="text-xl font-semibold">
                                {{ clientMandates.counts.action_requests }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Requests
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-white px-4 py-3 dark:bg-slate-900"
                        >
                            <dd
                                class="text-xl font-semibold text-indigo-700 dark:text-indigo-300"
                            >
                                {{ clientMandates.counts.permitted_actions }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Permitted
                            </dt>
                        </div>
                    </dl>
                </div>
            </section>

            <section
                class="rounded-2xl border border-amber-600/20 bg-amber-50/60 p-5 sm:p-6 dark:bg-amber-950/20"
            >
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-amber-700 uppercase dark:text-amber-300"
                        >
                            Matter accountability compiler
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Client, Engagement, and Matter remain distinct
                        </h2>
                        <p
                            class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            A Matter is bounded professional work inside an
                            Engagement. It preserves exactly one Responsible
                            Partner, explicit scope, risk ownership and
                            acceptance, escalation, and Evidence without
                            becoming a generic ticket.
                        </p>
                    </div>
                    <dl class="grid shrink-0 grid-cols-2 gap-2 text-center">
                        <div
                            class="rounded-lg bg-white px-4 py-3 dark:bg-slate-900"
                        >
                            <dd class="text-xl font-semibold">
                                {{ matters.counts.matters }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Matters
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-white px-4 py-3 dark:bg-slate-900"
                        >
                            <dd
                                class="text-xl font-semibold text-amber-700 dark:text-amber-300"
                            >
                                {{ matters.counts.active_matters }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Accountable
                            </dt>
                        </div>
                    </dl>
                </div>
            </section>

            <section
                v-if="engagements.reports.readiness_gaps.length > 0"
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
                            The compiler will not recognize an Open Engagement
                            while a required governing policy is not Effective.
                            Draft publication does not create operative
                            authority.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                :href="
                                    showDocument('policies/engagement-policy')
                                "
                                class="inline-flex items-center gap-2 rounded-lg border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:text-amber-200 dark:hover:bg-amber-950"
                            >
                                <FileSearch class="size-4" />
                                Engagement Policy
                            </Link>
                            <Link
                                :href="
                                    showDocument(
                                        'policies/authority-and-delegation-policy',
                                    )
                                "
                                class="inline-flex items-center gap-2 rounded-lg border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:text-amber-200 dark:hover:bg-amber-950"
                            >
                                <FileSearch class="size-4" />
                                Authority Policy
                            </Link>
                        </div>
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
                            Opening sequence
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Approval and opening are separate facts
                        </h2>
                    </div>
                    <Fingerprint class="size-6 text-slate-400" />
                </div>

                <div
                    class="mt-6 grid gap-2 xl:grid-cols-[repeat(13,minmax(0,auto))] xl:items-center"
                >
                    <template
                        v-for="(stage, index) in openingPath"
                        :key="stage.label"
                    >
                        <div
                            class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950"
                        >
                            <p class="text-sm font-semibold">
                                {{ stage.label }}
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                {{ stage.detail }}
                            </p>
                        </div>
                        <ArrowRight
                            v-if="index < openingPath.length - 1"
                            class="mx-auto hidden size-4 text-slate-400 xl:block"
                        />
                    </template>
                </div>

                <div
                    class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-400"
                >
                    Technical access, commenced work, a signed document, or an
                    Opening Record cannot silently substitute for Client
                    Acceptance, Engagement Approval, or Responsible Partner
                    accountability.
                </div>
            </section>

            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Opening standard
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Required institutional gates
                        </h2>
                    </div>
                    <p class="hidden text-sm text-slate-500 sm:block">
                        {{ engagements.opening_requirements.length }} required
                        determinations
                    </p>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="requirement in engagements.opening_requirements"
                        :key="requirement.key"
                        class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-2">
                            <ClipboardCheck class="size-4 text-teal-600" />
                            <h3 class="text-sm font-semibold">
                                {{ requirement.label }}
                            </h3>
                        </div>
                        <p
                            class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400"
                        >
                            {{ requirement.question }}
                        </p>
                    </article>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    v-for="layer in authorityLayers"
                    :key="layer.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <component
                            :is="layer.icon"
                            class="size-5 text-violet-600"
                        />
                        <h2 class="font-serif text-xl font-semibold">
                            {{ layer.title }}
                        </h2>
                    </div>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        {{ layer.detail }}
                    </p>
                </article>
            </section>

            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Engagement register
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Professional responsibility records
                        </h2>
                    </div>
                    <UserRoundCog class="size-6 text-slate-400" />
                </div>

                <div
                    v-if="engagements.engagements.length === 0"
                    class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900"
                >
                    <BriefcaseBusiness
                        class="mx-auto size-9 text-slate-400 dark:text-slate-600"
                    />
                    <h3 class="mt-4 font-serif text-xl font-semibold">
                        No Engagements are recorded
                    </h3>
                    <p
                        class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500"
                    >
                        The repository contains no invented Client work. An
                        Engagement may be added only after a real Client is
                        accepted and its scope, singular Responsible Partner,
                        mandate, terms, approval, opening verification, and
                        Evidence Records can be stated truthfully.
                    </p>
                </div>

                <div v-else class="mt-5 grid gap-4 lg:grid-cols-2">
                    <article
                        v-for="engagement in engagements.engagements"
                        :key="engagement.key"
                        class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                                >
                                    {{ engagement.key }}
                                </p>
                                <h3
                                    class="mt-1 font-serif text-xl font-semibold"
                                >
                                    {{ engagement.title }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{
                                        engagement.client_name ??
                                        'Client acceptance unresolved'
                                    }}
                                </p>
                            </div>
                            <span
                                class="rounded-full border px-2.5 py-1 text-[10px] font-semibold tracking-wide uppercase"
                                :class="
                                    statusClass(engagement.operational_status)
                                "
                            >
                                {{ engagement.lifecycle_status_label }}
                            </span>
                        </div>
                        <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950"
                            >
                                <dt class="text-xs text-slate-500">
                                    Responsible Partner
                                </dt>
                                <dd class="mt-1 text-sm font-semibold">
                                    {{
                                        engagement.responsible_partner
                                            ?.partner_name ?? 'Unresolved'
                                    }}
                                </dd>
                            </div>
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950"
                            >
                                <dt class="text-xs text-slate-500">
                                    Client work
                                </dt>
                                <dd class="mt-1 text-sm font-semibold">
                                    {{
                                        engagement.may_perform_client_work
                                            ? 'Permitted within scope'
                                            : 'Not permitted'
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </article>
                </div>
            </section>

            <section
                v-if="
                    engagements.reports.conflicts.length > 0 ||
                    engagements.reports.decision_gaps.length > 0 ||
                    engagements.reports.evidence_gaps.length > 0
                "
                class="rounded-2xl border border-red-300 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/30"
            >
                <div class="flex items-center gap-3">
                    <ShieldAlert
                        class="size-5 text-red-700 dark:text-red-400"
                    />
                    <h2 class="font-serif text-xl font-semibold">
                        Engagement compiler findings
                    </h2>
                </div>
                <ul class="mt-4 grid gap-2 text-sm">
                    <li
                        v-for="finding in [
                            ...engagements.reports.conflicts,
                            ...engagements.reports.decision_gaps,
                            ...engagements.reports.evidence_gaps,
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
