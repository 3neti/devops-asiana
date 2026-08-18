<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    DatabaseBackup,
    FileSearch,
    Gauge,
    RotateCcw,
    ShieldAlert,
    Waypoints,
} from '@lucide/vue';
import { index as continuityExercisesIndex } from '@/routes/continuity-exercises';
import { show as showDocument } from '@/routes/institutional-documents';
import type { ContinuityExerciseCompiler } from '@/types';

defineProps<{ continuityExercises: ContinuityExerciseCompiler }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Continuity Exercises',
                href: continuityExercisesIndex(),
            },
        ],
    },
});

const lifecycle = [
    ['Propose', 'Define context, scope, scenario, and risk'],
    ['Approve', 'Authorize exact objectives and safe boundary'],
    ['Schedule', 'Bound the exercise window and participants'],
    ['Exercise', 'Record attributable actions and deviations'],
    ['Observe', 'Measure recovery time and recovery-point age'],
    ['Verify', 'Independently assess evidence and gaps'],
    ['Close', 'Account for gaps, test data, and communication'],
];

const controlDistinctions = [
    {
        title: 'Backup is an input',
        detail: 'Job success and integrity metadata support selection of a recovery point. They do not prove that a usable service can be restored.',
        icon: DatabaseBackup,
    },
    {
        title: 'Objectives are authority',
        detail: 'RTO and RPO are explicit, sourced, approved service commitments—not defaults supplied by the Firm or inferred from tooling.',
        icon: Gauge,
    },
    {
        title: 'Failure creates evidence',
        detail: 'A missed objective remains a useful result. Every material gap must become accountable corrective work before closure.',
        icon: RotateCcw,
    },
];
</script>

<template>
    <Head title="Continuity Exercises" />

    <div class="min-h-full bg-stone-50/70 dark:bg-slate-950">
        <div
            class="mx-auto flex max-w-[96rem] flex-col gap-7 px-4 py-6 sm:px-6 lg:px-8"
        >
            <header
                class="grid gap-6 border-b border-slate-200 pb-7 lg:grid-cols-[1.2fr_0.8fr] lg:items-end dark:border-slate-800"
            >
                <div>
                    <div
                        class="flex items-center gap-2 text-sky-700 dark:text-sky-400"
                    >
                        <Waypoints class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Recovery evidence gate
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        Recoverability is demonstrated through controlled
                        exercise, measured results, and evidence.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        Continuity Exercises bind approved scope, dependencies,
                        recovery objectives, backup and restore execution,
                        observed performance, independent verification, material
                        gaps, Corrective Actions, data disposition, and closure.
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
                                Exercise readiness
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    continuityExercises.reports.readiness_gaps
                                        .length === 0
                                        ? 'Continuity control policies are operative'
                                        : 'No exercise approval is yet operative'
                                }}
                            </p>
                        </div>
                        <BadgeCheck
                            v-if="
                                continuityExercises.reports.readiness_gaps
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
                                    continuityExercises.counts.exercise_records
                                }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Exercises
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{
                                    continuityExercises.counts.objectives_missed
                                }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Missed
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ continuityExercises.counts.unresolved_gaps }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Open gaps
                            </dt>
                        </div>
                    </dl>
                </div>
            </header>

            <section
                v-if="continuityExercises.reports.readiness_gaps.length"
                class="rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30"
            >
                <div class="flex items-start gap-3">
                    <ShieldAlert
                        class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-400"
                    />
                    <div>
                        <h2 class="font-serif text-xl font-semibold">
                            Institutional readiness gaps
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300"
                        >
                            Continuity, authority, and information-security
                            policies must be Effective, approved,
                            integrity-verified, and evidenced before an exercise
                            can receive operative approval.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-for="policy in continuityExercises.governing_policies"
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
                    class="text-xs font-semibold tracking-[0.18em] text-sky-700 uppercase dark:text-sky-400"
                >
                    Exercise lifecycle
                </p>
                <h2 class="mt-2 font-serif text-2xl font-semibold">
                    Plan assumptions become measured institutional facts
                </h2>
                <div
                    class="mt-6 grid gap-2 2xl:grid-cols-[repeat(13,minmax(0,auto))] 2xl:items-center"
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
                    v-for="item in controlDistinctions"
                    :key="item.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <component
                        :is="item.icon"
                        class="size-5 text-sky-700 dark:text-sky-400"
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

            <section class="grid gap-5 xl:grid-cols-[0.78fr_1.22fr]">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2 class="font-serif text-2xl font-semibold">
                        Required exercise record
                    </h2>
                    <div class="mt-5 space-y-3">
                        <div
                            v-for="requirement in continuityExercises.record_requirements"
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
                        Continuity Exercise Register
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        The canonical register is intentionally empty. No Client
                        recovery objective, backup, restore result, or
                        resilience claim is invented.
                    </p>
                    <div
                        v-if="continuityExercises.exercise_records.length === 0"
                        class="mt-6 rounded-xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700"
                    >
                        <DatabaseBackup class="mx-auto size-8 text-slate-400" />
                        <p class="mt-3 font-semibold">
                            No continuity exercises recorded
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            The absence of exercises is visible as institutional
                            state, not disguised as readiness.
                        </p>
                    </div>
                    <div v-else class="mt-6 space-y-4">
                        <article
                            v-for="exercise in continuityExercises.exercise_records"
                            :key="exercise.key"
                            class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold text-sky-700 uppercase dark:text-sky-400"
                                    >
                                        {{ exercise.exercise_type_label }}
                                    </p>
                                    <h3 class="mt-1 font-semibold">
                                        {{ exercise.title }}
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ exercise.objectives_met }} met ·
                                        {{ exercise.objectives_missed }} missed
                                        ·
                                        {{ exercise.unresolved_gaps }}
                                        unresolved gaps
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold dark:bg-slate-800"
                                    >{{ exercise.lifecycle_status_label }}</span
                                >
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
