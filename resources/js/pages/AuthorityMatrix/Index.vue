<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Ban,
    CircleAlert,
    FileSearch,
    Gavel,
    KeyRound,
    LockKeyhole,
    Scale,
    ShieldCheck,
    UserRoundCheck,
} from '@lucide/vue';
import { index as authorityMatrixIndex } from '@/routes/authority-matrix';
import { show as showDocument } from '@/routes/institutional-documents';
import type {
    AuthorityMatrixCompiler,
    AuthorityResolutionStatus,
} from '@/types';

defineProps<{ authorityMatrix: AuthorityMatrixCompiler }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Authority Matrix',
                href: authorityMatrixIndex(),
            },
        ],
    },
});

const authorityEquation = [
    {
        title: 'Firm Authority',
        detail: 'Who the Firm permits to decide or approve under an operative source and bounded Matrix entry.',
        icon: Gavel,
    },
    {
        title: 'Client Mandate',
        detail: 'What the Client permits the Firm to do under an Engagement or valid instruction.',
        icon: UserRoundCheck,
    },
    {
        title: 'Specific Approval',
        detail: 'Authorization for one action that remains inside both Firm Authority and Client Mandate.',
        icon: ShieldCheck,
    },
];

function label(value: string): string {
    return value.replaceAll('_', ' ');
}

function statusClass(status: AuthorityResolutionStatus): string {
    return {
        effective:
            'border-teal-600/20 bg-teal-50 text-teal-800 dark:bg-teal-950/40 dark:text-teal-300',
        design_only:
            'border-slate-600/20 bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        vacant_holder:
            'border-rose-600/20 bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300',
        pending_activation:
            'border-amber-600/20 bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
        blocked:
            'border-orange-600/20 bg-orange-50 text-orange-800 dark:bg-orange-950/40 dark:text-orange-300',
        conflicted:
            'border-violet-600/20 bg-violet-50 text-violet-800 dark:bg-violet-950/40 dark:text-violet-300',
    }[status];
}
</script>

<template>
    <Head title="Authority Matrix" />

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
                        <Gavel class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Firm Authority compiler
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        Authority is specific, bounded, attributable, and never
                        inferred from title or access.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        The Matrix resolves who may decide or approve for the
                        Firm, under which institutional source, within which
                        boundary, and why the authority is—or is not—currently
                        effective.
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
                                Current authority state
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                No Matrix entry is operative
                            </p>
                        </div>
                        <LockKeyhole class="size-8 text-amber-600" />
                    </div>
                    <dl class="mt-5 grid grid-cols-4 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ authorityMatrix.counts.domains }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Domains
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ authorityMatrix.counts.entries }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Entries
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ authorityMatrix.counts.effective_entries }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Effective
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ authorityMatrix.counts.deferred_decisions }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Deferred
                            </dt>
                        </div>
                    </dl>
                </div>
            </header>

            <section
                class="grid gap-3 lg:grid-cols-[1fr_auto_1fr_auto_1fr_auto_1fr] lg:items-stretch"
            >
                <template
                    v-for="(item, index) in authorityEquation"
                    :key="item.title"
                >
                    <article
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
                    <div
                        class="hidden items-center justify-center text-xl font-semibold text-slate-400 lg:flex"
                    >
                        {{ index < authorityEquation.length - 1 ? '+' : '=' }}
                    </div>
                </template>
                <article
                    class="rounded-2xl border border-indigo-600/20 bg-indigo-50 p-5 dark:bg-indigo-950/30"
                >
                    <KeyRound
                        class="size-5 text-indigo-700 dark:text-indigo-300"
                    />
                    <h2 class="mt-4 font-serif text-xl font-semibold">
                        Permitted Client Action
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-indigo-900/75 dark:text-indigo-200/75"
                    >
                        Possible only when all three gates independently resolve
                        for the same bounded action.
                    </p>
                </article>
            </section>

            <section
                class="rounded-2xl border border-amber-600/20 bg-amber-50 p-5 dark:bg-amber-950/30"
            >
                <div class="flex items-start gap-3">
                    <CircleAlert
                        class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-300"
                    />
                    <div>
                        <p
                            class="font-semibold text-amber-900 dark:text-amber-200"
                        >
                            {{ authorityMatrix.governing_policy.title }}
                            {{ authorityMatrix.governing_policy.version }} is
                            {{ authorityMatrix.governing_policy.status_label }}
                        </p>
                        <p
                            class="mt-1 text-sm leading-6 text-amber-900/80 dark:text-amber-200/80"
                        >
                            Draft policy and Design entries describe the future
                            control surface but cannot authorize a decision or
                            action. Approved constitutional entries also await
                            the unresolved Firm effective date and operative
                            holder records.
                        </p>
                    </div>
                </div>
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
                            Resolved Matrix
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Seven grounded actions, no invented powers
                        </h2>
                    </div>
                    <Link
                        :href="showDocument('domains/authority-matrix')"
                        class="inline-flex items-center gap-2 self-start rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                    >
                        <FileSearch class="size-4" />
                        Domain doctrine
                    </Link>
                </div>

                <div class="mt-6 grid gap-4">
                    <article
                        v-for="entry in authorityMatrix.entries"
                        :key="entry.key"
                        class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                    >
                        <div
                            class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr_0.9fr_auto] lg:items-start"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold">
                                        {{ entry.action_label }}
                                    </h3>
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                                        :class="
                                            statusClass(entry.resolution_status)
                                        "
                                    >
                                        {{ label(entry.resolution_status) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ entry.domain_label }} ·
                                    {{ entry.action_stage }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[10px] font-semibold text-slate-500 uppercase"
                                >
                                    Candidate holder
                                </p>
                                <p class="mt-1 text-sm">
                                    {{
                                        entry.candidate_holder_names.join(
                                            ' · ',
                                        ) || 'Unassigned'
                                    }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ label(entry.holder_rule.type) }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[10px] font-semibold text-slate-500 uppercase"
                                >
                                    Authority source
                                </p>
                                <p class="mt-1 text-sm">
                                    {{ entry.source_label }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{
                                        entry.source_operative
                                            ? 'Operative source'
                                            : 'Source not operative'
                                    }}
                                </p>
                            </div>
                            <div class="flex gap-2 lg:justify-end">
                                <span
                                    v-if="
                                        entry.client_mandate_gate ===
                                        'required_separately'
                                    "
                                    class="rounded-md bg-indigo-50 px-2 py-1 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300"
                                >
                                    Client Mandate required
                                </span>
                            </div>
                        </div>
                        <div
                            class="mt-4 grid gap-3 border-t border-slate-100 pt-4 text-xs text-slate-600 md:grid-cols-3 dark:border-slate-800 dark:text-slate-400"
                        >
                            <p>
                                <span
                                    class="font-semibold text-slate-800 dark:text-slate-200"
                                    >Risk boundary:</span
                                >
                                {{ entry.scope.risk_boundary }}
                            </p>
                            <p>
                                <span
                                    class="font-semibold text-slate-800 dark:text-slate-200"
                                    >Delegation:</span
                                >
                                {{
                                    entry.delegation.permitted
                                        ? 'Explicitly permitted'
                                        : 'Not permitted'
                                }}
                            </p>
                            <p>
                                <span
                                    class="font-semibold text-slate-800 dark:text-slate-200"
                                    >Specific approval:</span
                                >
                                {{ label(entry.specific_approval_gate) }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center gap-2 text-rose-700 dark:text-rose-400"
                    >
                        <Ban class="size-5" />
                        <p
                            class="text-xs font-semibold tracking-[0.18em] uppercase"
                        >
                            No authority exists yet
                        </p>
                    </div>
                    <h2 class="mt-2 font-serif text-2xl font-semibold">
                        Deferred decisions are explicit exclusions
                    </h2>
                    <div class="mt-5 grid gap-3">
                        <article
                            v-for="decision in authorityMatrix.deferred_decisions"
                            :key="decision.key"
                            class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-semibold">
                                        {{ decision.label }}
                                    </h3>
                                    <p
                                        class="mt-1 text-xs leading-5 text-slate-500"
                                    >
                                        {{ decision.reason }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full border border-slate-300 px-2 py-0.5 text-[10px] font-semibold uppercase dark:border-slate-700"
                                >
                                    {{ label(decision.state) }}
                                </span>
                            </div>
                        </article>
                    </div>
                </div>

                <aside class="space-y-5">
                    <div
                        class="rounded-2xl border border-rose-600/20 bg-rose-50 p-5 dark:bg-rose-950/30"
                    >
                        <div
                            class="flex items-center gap-2 text-rose-800 dark:text-rose-300"
                        >
                            <KeyRound class="size-5" />
                            <h2 class="font-semibold">Vacant authority</h2>
                        </div>
                        <p
                            v-for="gap in authorityMatrix.reports.holder_gaps"
                            :key="gap.code"
                            class="mt-3 text-sm leading-6 text-rose-900/80 dark:text-rose-200/80"
                        >
                            {{ gap.message }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-2">
                            <Scale class="size-5 text-indigo-600" />
                            <h2 class="font-serif text-xl font-semibold">
                                Compiler safeguards
                            </h2>
                        </div>
                        <ul
                            class="mt-4 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            <li
                                v-for="principle in authorityMatrix.principles"
                                :key="principle"
                                class="flex gap-2"
                            >
                                <ShieldCheck
                                    class="mt-1 size-4 shrink-0 text-indigo-600"
                                />
                                {{ principle }}
                            </li>
                        </ul>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</template>
