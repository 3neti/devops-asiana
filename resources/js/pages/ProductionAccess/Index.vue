<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    CircleOff,
    FileCheck2,
    FileSearch,
    Fingerprint,
    KeyRound,
    LockKeyhole,
    ShieldAlert,
    UserRoundCheck,
} from '@lucide/vue';
import { show as showDocument } from '@/routes/institutional-documents';
import { index as productionAccessIndex } from '@/routes/production-access';
import type { ProductionAccessCompiler } from '@/types';

defineProps<{
    productionAccess: ProductionAccessCompiler;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Production Access',
                href: productionAccessIndex(),
            },
        ],
    },
});

const lifecycle = [
    ['Request', 'Named person, purpose, and least-privilege scope'],
    ['Review', 'Identity, risk, mandate, and prerequisites'],
    ['Approve', 'Client and Firm authorities decide independently'],
    ['Provision', 'Approved permissions are technically assigned'],
    ['Verify', 'Observed access is compared with approved scope'],
    ['Activate', 'Use is permitted only while every gate holds'],
    ['Review / Revoke', 'Authority expires or is explicitly withdrawn'],
];

const authorityLayers = [
    {
        title: 'Client Mandate',
        icon: UserRoundCheck,
        detail: 'The Engagement records what the Client permits the Firm to do for identified systems and environments.',
    },
    {
        title: 'Firm Authority',
        icon: FileCheck2,
        detail: 'The Authority Matrix identifies who may approve or execute access on behalf of the Firm.',
    },
    {
        title: 'Specific Grant',
        icon: KeyRound,
        detail: 'A named, scoped, time-bounded record connects one person to both layers of authority.',
    },
];
</script>

<template>
    <Head title="Production Access" />

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
                        <LockKeyhole class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Institutional authority gate
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        No Access Without Authority.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        Technical possession is not permission. Production
                        access becomes usable only when a named person, an Open
                        Engagement, Client Mandate, Firm approvals, bounded
                        scope, provisioning, independent verification, current
                        validity, and evidence all agree.
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
                                Activation readiness
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    productionAccess.governing_policies.every(
                                        (policy) => policy.operative,
                                    )
                                        ? 'Required policies are Effective'
                                        : 'No access may be activated'
                                }}
                            </p>
                        </div>
                        <BadgeCheck
                            v-if="
                                productionAccess.governing_policies.every(
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
                                {{ productionAccess.counts.access_grants }}
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
                                {{ productionAccess.counts.active_authority }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Usable
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ productionAccess.counts.evidence_records }}
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
                v-if="productionAccess.reports.readiness_gaps.length"
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
                            Draft policy expresses intent but does not create
                            operative access authority. Every required policy
                            must be Effective, approved, integrity-verified, and
                            evidenced.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-for="policy in productionAccess.governing_policies"
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
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Access lifecycle
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Approval, provisioning, verification, and activation
                            are separate facts
                        </h2>
                    </div>
                    <Fingerprint class="size-6 text-slate-400" />
                </div>
                <div
                    class="mt-6 grid gap-2 xl:grid-cols-[repeat(13,minmax(0,auto))] xl:items-center"
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
                            class="mx-auto hidden size-4 text-slate-400 xl:block"
                        />
                    </template>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    v-for="layer in authorityLayers"
                    :key="layer.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <component
                        :is="layer.icon"
                        class="size-5 text-teal-700 dark:text-teal-400"
                    />
                    <h2 class="mt-4 font-serif text-xl font-semibold">
                        {{ layer.title }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        {{ layer.detail }}
                    </p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.4fr_0.6fr]">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <p
                        class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                    >
                        Grant standard
                    </p>
                    <h2 class="mt-2 font-serif text-2xl font-semibold">
                        Questions every Access Grant must answer
                    </h2>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <article
                            v-for="requirement in productionAccess.grant_requirements"
                            :key="requirement.key"
                            class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                        >
                            <p class="text-sm font-semibold">
                                {{ requirement.label }}
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                {{ requirement.question }}
                            </p>
                        </article>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <article
                        class="rounded-2xl border border-red-200 bg-red-50 p-5 dark:border-red-950 dark:bg-red-950/25"
                    >
                        <CircleOff
                            class="size-5 text-red-700 dark:text-red-400"
                        />
                        <h2 class="mt-3 font-serif text-xl font-semibold">
                            Secrets never live here
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300"
                        >
                            The repository records ownership, custody, vault
                            reference, rotation, and evidence—never passwords,
                            tokens, private keys, recovery codes, or credential
                            values.
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-950 dark:bg-amber-950/25"
                    >
                        <ShieldAlert
                            class="size-5 text-amber-700 dark:text-amber-400"
                        />
                        <h2 class="mt-3 font-serif text-xl font-semibold">
                            Privileged and emergency access
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300"
                        >
                            Privileged grants require independent approval.
                            {{ productionAccess.boundary }}
                        </p>
                    </article>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400"
                        >
                            Canonical register
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Production Access Grants
                        </h2>
                    </div>
                    <KeyRound class="size-6 text-slate-400" />
                </div>
                <div
                    v-if="productionAccess.access_grants.length === 0"
                    class="mt-5 rounded-xl border border-dashed border-slate-300 px-5 py-8 text-center dark:border-slate-700"
                >
                    <p class="font-semibold">
                        No Production Access Grants are recorded.
                    </p>
                    <p
                        class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500"
                    >
                        This is the correct current state: no canonical
                        Engagement is Open and the governing policies remain
                        Draft. The Console does not invent operational
                        authority.
                    </p>
                </div>
                <div v-else class="mt-5 grid gap-3">
                    <article
                        v-for="grant in productionAccess.access_grants"
                        :key="grant.key"
                        class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <p class="font-semibold">{{ grant.title }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ grant.actor?.name }} ·
                                    {{ grant.client_name }} ·
                                    {{ grant.scope?.system }} /
                                    {{ grant.scope?.environment }}
                                </p>
                            </div>
                            <span
                                class="rounded-full border px-2.5 py-1 text-xs font-semibold"
                                :class="
                                    grant.may_use_access
                                        ? 'border-teal-300 bg-teal-50 text-teal-800'
                                        : 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                "
                                >{{ grant.lifecycle_status_label }}</span
                            >
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>
