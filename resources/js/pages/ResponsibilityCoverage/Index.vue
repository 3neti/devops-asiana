<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CircleAlert,
    FileSearch,
    KeyRound,
    Network,
    Scale,
    ShieldAlert,
    UserRoundCog,
    UsersRound,
} from '@lucide/vue';
import { show as showDocument } from '@/routes/institutional-documents';
import { index as responsibilityCoverageIndex } from '@/routes/responsibility-coverage';
import type {
    ResponsibilityCoverageCompiler,
    ResponsibilityCoverageStatus,
} from '@/types';

defineProps<{ responsibilityCoverage: ResponsibilityCoverageCompiler }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Responsibility Coverage',
                href: responsibilityCoverageIndex(),
            },
        ],
    },
});

const attachmentDoctrine = [
    {
        title: 'Office-based authority',
        detail: 'Managing Partner authority follows the office and transfers only through a valid office appointment.',
        icon: UserRoundCog,
    },
    {
        title: 'Personal constitutional right',
        detail: 'Founding Partner Reserved Matter participation follows constitutional status and is not inherited through an office.',
        icon: Scale,
    },
    {
        title: 'Delegated authority',
        detail: 'Operational approval requires an explicit delegation. Responsibility, title, access, and prior action are not substitutes.',
        icon: KeyRound,
    },
];

function statusClass(status: ResponsibilityCoverageStatus): string {
    return {
        covered:
            'border-teal-600/20 bg-teal-50 text-teal-800 dark:bg-teal-950/40 dark:text-teal-300',
        vacant: 'border-rose-600/20 bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300',
        pending_activation:
            'border-amber-600/20 bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
        conflicted:
            'border-violet-600/20 bg-violet-50 text-violet-800 dark:bg-violet-950/40 dark:text-violet-300',
    }[status];
}

function statusLabel(status: ResponsibilityCoverageStatus): string {
    return {
        covered: 'Covered',
        vacant: 'Vacant',
        pending_activation: 'Pending activation',
        conflicted: 'Conflicted',
    }[status];
}
</script>

<template>
    <Head title="Responsibility Coverage" />

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
                        <Network class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Institutional coverage map
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        Every required responsibility must resolve to qualified,
                        reviewable human coverage.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        This compiler derives offices, professional
                        responsibilities, and authority requirements from the
                        Partnership and policy register—then exposes vacancies,
                        conflicts, concentration, and succession gaps.
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
                                Coverage state
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                Institutional gaps require review
                            </p>
                        </div>
                        <ShieldAlert class="size-8 text-amber-600" />
                    </div>
                    <dl class="mt-5 grid grid-cols-4 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ responsibilityCoverage.counts.covered }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Covered
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ responsibilityCoverage.counts.vacant }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Vacant
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{
                                    responsibilityCoverage.counts
                                        .succession_gaps
                                }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Succession
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{
                                    responsibilityCoverage.counts
                                        .pending_activation
                                }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Pending
                            </dt>
                        </div>
                    </dl>
                </div>
            </header>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    v-for="item in attachmentDoctrine"
                    :key="item.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <component
                        :is="item.icon"
                        class="size-5 text-indigo-700 dark:text-indigo-400"
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

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-indigo-700 uppercase dark:text-indigo-400"
                        >
                            Canonical requirements
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Coverage resolves from existing institutional truth
                        </h2>
                    </div>
                    <Link
                        :href="showDocument('domains/responsibility-coverage')"
                        class="inline-flex items-center gap-2 self-start rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                    >
                        <FileSearch class="size-4" />
                        Domain doctrine
                    </Link>
                </div>

                <div class="mt-6 grid gap-3">
                    <article
                        v-for="requirement in responsibilityCoverage.requirements"
                        :key="requirement.key"
                        class="grid gap-4 rounded-xl border border-slate-200 p-4 lg:grid-cols-[1.15fr_0.85fr_0.8fr_auto] lg:items-center dark:border-slate-800"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold">
                                    {{ requirement.label }}
                                </p>
                                <span
                                    class="rounded-full border px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                                    :class="
                                        statusClass(requirement.coverage_status)
                                    "
                                >
                                    {{
                                        statusLabel(requirement.coverage_status)
                                    }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500 capitalize">
                                {{ requirement.category }} ·
                                {{ requirement.criticality }} · authority:
                                {{ requirement.authority_attachment }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Current holder(s)
                            </p>
                            <p class="mt-1 text-sm font-medium">
                                {{
                                    requirement.holder_names.join(', ') ||
                                    'No qualified holder recorded'
                                }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Governing source
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-400"
                            >
                                {{ requirement.source_label }} ·
                                {{ requirement.source_status }}
                            </p>
                        </div>
                        <div class="lg:text-right">
                            <p
                                v-if="requirement.succession.required"
                                class="text-xs font-medium"
                                :class="
                                    requirement.alternate_holder_names.length
                                        ? 'text-teal-700 dark:text-teal-400'
                                        : 'text-amber-700 dark:text-amber-400'
                                "
                            >
                                {{
                                    requirement.alternate_holder_names.length
                                        ? 'Alternate recorded'
                                        : 'No alternate'
                                }}
                            </p>
                            <p v-else class="text-xs text-slate-500">
                                No alternate required
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-3">
                <article
                    class="rounded-2xl border border-rose-300 bg-rose-50/70 p-5 dark:border-rose-900 dark:bg-rose-950/20"
                >
                    <div class="flex items-center gap-3">
                        <CircleAlert
                            class="size-5 text-rose-700 dark:text-rose-400"
                        />
                        <h2 class="font-serif text-xl font-semibold">
                            Vacancies
                        </h2>
                    </div>
                    <ul class="mt-4 grid gap-3">
                        <li
                            v-for="gap in responsibilityCoverage.reports
                                .vacancies"
                            :key="gap.requirement_key"
                            class="rounded-xl border border-rose-300/70 bg-white/70 p-3 text-sm dark:border-rose-900 dark:bg-slate-950/40"
                        >
                            {{ gap.message }}
                        </li>
                    </ul>
                </article>

                <article
                    class="rounded-2xl border border-amber-300 bg-amber-50/70 p-5 dark:border-amber-900 dark:bg-amber-950/20"
                >
                    <div class="flex items-center gap-3">
                        <UsersRound
                            class="size-5 text-amber-700 dark:text-amber-400"
                        />
                        <h2 class="font-serif text-xl font-semibold">
                            Continuity of responsibility
                        </h2>
                    </div>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-700 dark:text-slate-300"
                    >
                        {{ responsibilityCoverage.counts.succession_gaps }}
                        covered material responsibilities lack a distinct
                        qualified alternate.
                    </p>
                    <ul class="mt-4 grid gap-2 text-sm">
                        <li
                            v-for="gap in responsibilityCoverage.reports
                                .succession_gaps"
                            :key="gap.requirement_key"
                        >
                            {{ gap.message }}
                        </li>
                    </ul>
                </article>

                <article
                    class="rounded-2xl border border-violet-300 bg-violet-50/70 p-5 dark:border-violet-900 dark:bg-violet-950/20"
                >
                    <div class="flex items-center gap-3">
                        <ShieldAlert
                            class="size-5 text-violet-700 dark:text-violet-400"
                        />
                        <h2 class="font-serif text-xl font-semibold">
                            Concentration review
                        </h2>
                    </div>
                    <p
                        v-for="exposure in responsibilityCoverage.reports
                            .concentration_exposures"
                        :key="exposure.holder_key"
                        class="mt-3 text-sm leading-6 text-slate-700 dark:text-slate-300"
                    >
                        {{ exposure.message }}
                    </p>
                    <p class="mt-4 text-xs leading-5 text-slate-500">
                        This is a continuity signal, not an automatic revocation
                        of authority.
                    </p>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-2">
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <BadgeCheck
                            class="size-5 text-teal-700 dark:text-teal-400"
                        />
                        <h2 class="font-serif text-xl font-semibold">
                            Separation constraints
                        </h2>
                    </div>
                    <div class="mt-4 grid gap-3">
                        <div
                            v-for="constraint in responsibilityCoverage.separation_constraints"
                            :key="constraint.key"
                            class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                        >
                            <p class="text-sm font-semibold capitalize">
                                {{ constraint.status.replace('_', ' ') }}
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                {{ constraint.reason }}
                            </p>
                        </div>
                    </div>
                </article>

                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-3">
                        <FileSearch
                            class="size-5 text-indigo-700 dark:text-indigo-400"
                        />
                        <h2 class="font-serif text-xl font-semibold">
                            Pending policy requirements
                        </h2>
                    </div>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Draft policies disclose future coverage needs but do not
                        create operative authority or live vacancies.
                    </p>
                    <ul class="mt-4 grid gap-2 text-sm">
                        <li
                            v-for="gap in responsibilityCoverage.reports
                                .pending_requirements"
                            :key="gap.requirement_key"
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            {{ gap.message }}
                        </li>
                    </ul>
                </article>
            </section>
        </div>
    </div>
</template>
