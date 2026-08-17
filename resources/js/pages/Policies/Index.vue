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
    Scale,
    ShieldAlert,
} from '@lucide/vue';
import { show as showDocument } from '@/routes/institutional-documents';
import { index as policyRegistry } from '@/routes/policy-registry';
import type {
    PolicyLifecycleStatus,
    PolicyProjection,
    PolicyRegistry,
} from '@/types';

defineProps<{
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
