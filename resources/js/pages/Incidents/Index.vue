<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    BellRing,
    ClipboardCheck,
    FileSearch,
    MessagesSquare,
    RadioTower,
    ShieldAlert,
    Siren,
    UserRoundCog,
} from '@lucide/vue';
import { index as incidentsIndex } from '@/routes/incidents';
import { show as showDocument } from '@/routes/institutional-documents';
import type { IncidentCompiler } from '@/types';

defineProps<{ incidents: IncidentCompiler }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Incidents', href: incidentsIndex() }],
    },
});

const lifecycle = [
    ['Detect', 'Record the observed event or alert'],
    ['Declare', 'Make classification and severity explicit'],
    ['Command', 'Name accountable response roles'],
    ['Contain', 'Limit harm and preserve evidence'],
    ['Recover', 'Restore a known-safe service state'],
    ['Verify', 'Confirm restoration and monitor stability'],
    ['Review', 'Learn without blame and assign actions'],
    ['Close', 'Record a separate authorized decision'],
];

const commandRoles = [
    {
        title: 'Incident Commander',
        detail: 'Coordinates objectives, decisions, owners, timeline, and response cadence.',
        icon: Siren,
    },
    {
        title: 'Responsible Partner',
        detail: 'Retains professional and Client accountability; evaluates material escalation.',
        icon: UserRoundCog,
    },
    {
        title: 'Technical Lead',
        detail: 'Directs diagnosis, containment, recovery, verification, and technical evidence.',
        icon: RadioTower,
    },
    {
        title: 'Communication Owner',
        detail: 'Issues timely, factual, authorized updates as verified facts change.',
        icon: MessagesSquare,
    },
];

const notificationAudiences = ['Client', 'Legal', 'Regulatory', 'Insurer'];
</script>

<template>
    <Head title="Incidents" />

    <div class="min-h-full bg-stone-50/70 dark:bg-slate-950">
        <div
            class="mx-auto flex max-w-[96rem] flex-col gap-7 px-4 py-6 sm:px-6 lg:px-8"
        >
            <header
                class="grid gap-6 border-b border-slate-200 pb-7 lg:grid-cols-[1.2fr_0.8fr] lg:items-end dark:border-slate-800"
            >
                <div>
                    <div
                        class="flex items-center gap-2 text-rose-700 dark:text-rose-400"
                    >
                        <Siren class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Professional response record
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        No Incident Without Disclosure.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        An alert is not a declaration, restored service is not
                        closure, and technical action is not professional
                        accountability. Every material response must identify
                        command, authority, impact, disclosure, verification,
                        review, corrective action, and evidence.
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
                                Declaration readiness
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    incidents.governing_policies
                                        .filter(
                                            (policy) =>
                                                policy.required_for_declaration,
                                        )
                                        .every((policy) => policy.operative)
                                        ? 'Base response policy is operative'
                                        : 'Institutional declaration gate is not ready'
                                }}
                            </p>
                        </div>
                        <BadgeCheck
                            v-if="incidents.reports.readiness_gaps.length === 0"
                            class="size-8 text-teal-600"
                        />
                        <ShieldAlert v-else class="size-8 text-amber-600" />
                    </div>
                    <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ incidents.counts.incident_records }}
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
                                {{ incidents.counts.active_response }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Active
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ incidents.counts.awaiting_closure }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Closeable
                            </dt>
                        </div>
                    </dl>
                </div>
            </header>

            <section
                v-if="incidents.reports.readiness_gaps.length"
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
                            Draft policy does not create operational authority.
                            Security and continuity policies also become
                            mandatory when the Incident type or major
                            classification makes them applicable.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-for="policy in incidents.governing_policies"
                                :key="policy.key"
                                :href="
                                    showDocument(
                                        `policies/${policy.key}-policy`,
                                    )
                                "
                                class="inline-flex items-center gap-2 rounded-lg border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:text-amber-200 dark:hover:bg-amber-950"
                            >
                                <FileSearch class="size-4" />
                                {{ policy.title }} · {{ policy.status_label }} ·
                                {{ policy.applies_to }}
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <p
                    class="text-xs font-semibold tracking-[0.18em] text-rose-700 uppercase dark:text-rose-400"
                >
                    Response lifecycle
                </p>
                <h2 class="mt-2 font-serif text-2xl font-semibold">
                    Operational state and institutional decision remain distinct
                </h2>
                <div
                    class="mt-6 grid gap-2 2xl:grid-cols-[repeat(15,minmax(0,auto))] 2xl:items-center"
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

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="role in commandRoles"
                    :key="role.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <component
                        :is="role.icon"
                        class="size-5 text-rose-700 dark:text-rose-400"
                    />
                    <h2 class="mt-4 font-serif text-xl font-semibold">
                        {{ role.title }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        {{ role.detail }}
                    </p>
                </article>
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <article
                    class="rounded-2xl border border-teal-200 bg-teal-50/60 p-5 sm:p-6 dark:border-teal-950 dark:bg-teal-950/20"
                >
                    <ClipboardCheck
                        class="size-6 text-teal-700 dark:text-teal-400"
                    />
                    <h2 class="mt-4 font-serif text-2xl font-semibold">
                        Restoration ≠ Closure
                    </h2>
                    <p
                        class="mt-3 text-sm leading-6 text-slate-700 dark:text-slate-300"
                    >
                        Restoration records the recovered technical state,
                        independent verification, and stability observation.
                        Closure separately requires final notification
                        decisions, the required blameless review, owned
                        corrective actions, complete evidence, and competent
                        authority.
                    </p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <BellRing class="size-6 text-rose-700 dark:text-rose-400" />
                    <h2 class="mt-4 font-serif text-2xl font-semibold">
                        Every audience receives a decision
                    </h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            v-for="audience in notificationAudiences"
                            :key="audience"
                            class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold dark:border-slate-700"
                        >
                            {{ audience }}
                        </span>
                    </div>
                    <p
                        class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Notified, not required, or pending must be recorded by
                        an authorized decision-maker with reason and evidence.
                        Client impact cannot be closed as “not required.”
                    </p>
                </article>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-center gap-2">
                    <FileSearch class="size-5 text-slate-500" />
                    <h2 class="font-serif text-2xl font-semibold">
                        Incident Record standard
                    </h2>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="requirement in incidents.record_requirements"
                        :key="requirement.key"
                        class="rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                    >
                        <p class="text-sm font-semibold">
                            {{ requirement.label }}
                        </p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            {{ requirement.question }}
                        </p>
                    </article>
                </div>
            </section>

            <section
                class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-8 text-center dark:border-slate-700 dark:bg-slate-900/70"
            >
                <Siren class="mx-auto size-8 text-slate-400" />
                <h2 class="mt-4 font-serif text-2xl font-semibold">
                    {{
                        incidents.incident_records.length === 0
                            ? 'No Incident Records yet'
                            : 'Canonical Incident Records'
                    }}
                </h2>
                <p
                    v-if="incidents.incident_records.length === 0"
                    class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500"
                >
                    The empty registry is intentional. The compiler exposes the
                    control model and policy readiness without fabricating
                    operational history.
                </p>
                <div v-else class="mt-6 grid gap-4 text-left lg:grid-cols-2">
                    <article
                        v-for="incident in incidents.incident_records"
                        :key="incident.key"
                        class="rounded-xl border border-slate-200 p-5 dark:border-slate-800"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <p class="font-mono text-xs text-slate-500">
                                {{ incident.key }}
                            </p>
                            <span
                                class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-slate-800"
                            >
                                {{ incident.lifecycle_status_label }}
                            </span>
                        </div>
                        <h3 class="mt-3 font-serif text-xl font-semibold">
                            {{ incident.title }}
                        </h3>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ incident.client_name }} ·
                            {{ incident.severity }} ·
                            {{ incident.incident_type_label }}
                        </p>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>
