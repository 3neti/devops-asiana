<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CircleAlert,
    ContactRound,
    FileSearch,
    Fingerprint,
    KeyRound,
    ShieldCheck,
    UserRoundCog,
} from '@lucide/vue';
import { index as identityAndRolesIndex } from '@/routes/identity-and-roles';
import { show as showDocument } from '@/routes/institutional-documents';
import type {
    IdentityAndRoleCompiler,
    RoleActivationCompiler,
    RoleTransitionCompiler,
    ResponsibilityCoverageStatus,
} from '@/types';

const props = defineProps<{
    identityAndRoles: IdentityAndRoleCompiler;
    roleActivations: RoleActivationCompiler;
    roleTransitions: RoleTransitionCompiler;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Identity & Roles',
                href: identityAndRolesIndex(),
            },
        ],
    },
});

const boundaries = [
    {
        title: 'Identity is not login',
        detail: 'A recognized person may have no Console account, and an account never creates institutional authority.',
        icon: Fingerprint,
    },
    {
        title: 'Role is not authority',
        detail: 'Professional responsibility identifies accountable work. Only an office or bounded delegation can carry Firm authority here.',
        icon: UserRoundCog,
    },
    {
        title: 'Approval is not activation',
        detail: 'An approved assignment remains non-operative until its effective time, evidence, and lifecycle conditions are satisfied.',
        icon: ShieldCheck,
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

function label(value: string): string {
    return value.replaceAll('_', ' ');
}

function activationAdmitted(assignmentKey: string): boolean {
    return props.roleActivations.assignment_activation_admissions.some(
        (admission) => admission.assignment_key === assignmentKey,
    );
}
</script>

<template>
    <Head title="Identity & Roles" />

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
                        <ContactRound class="size-4" />
                        <p
                            class="text-xs font-semibold tracking-[0.22em] uppercase"
                        >
                            Institutional identity compiler
                        </p>
                    </div>
                    <h1
                        class="mt-4 max-w-4xl font-serif text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl dark:text-stone-50"
                    >
                        People hold roles. Roles carry responsibilities.
                        Authority remains separately provable.
                    </h1>
                    <p
                        class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-400"
                    >
                        This register reconciles named people and role
                        assignments with Partnership formation and
                        Responsibility Coverage without treating status, access,
                        or prior conduct as authority.
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
                                Current institutional state
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    roleActivations.counts.pending_assignments >
                                    0
                                        ? 'Approved assignments await assumption'
                                        : 'Founding assignments admitted'
                                }}
                            </p>
                        </div>
                        <CircleAlert class="size-8 text-amber-600" />
                    </div>
                    <dl class="mt-5 grid grid-cols-4 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ identityAndRoles.counts.identities }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                People
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ identityAndRoles.counts.roles }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Roles
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ identityAndRoles.counts.assignments }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Assigned
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{
                                    identityAndRoles.counts.authority_effective
                                }}
                            </dd>
                            <dt
                                class="text-[10px] tracking-wide text-slate-500 uppercase"
                            >
                                Authority
                            </dt>
                        </div>
                    </dl>
                </div>
            </header>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    v-for="item in boundaries"
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
                class="rounded-2xl border border-indigo-600/20 bg-indigo-50/70 p-5 sm:p-6 dark:bg-indigo-950/20"
            >
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-indigo-700 uppercase dark:text-indigo-300"
                        >
                            Founding role activation compiler
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Commencement establishes eligibility. Each holder
                            still assumes one exact assignment.
                        </h2>
                        <p
                            class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            Acceptance, independent verification, activation,
                            and their Evidence remain distinct. An admitted
                            professional responsibility does not itself create
                            Firm Authority.
                        </p>
                    </div>
                    <dl class="grid shrink-0 grid-cols-3 gap-2 text-center">
                        <div
                            class="rounded-lg bg-white px-4 py-3 dark:bg-slate-900"
                        >
                            <dd class="text-xl font-semibold">
                                {{
                                    roleActivations.counts.candidate_assignments
                                }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Candidates
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-white px-4 py-3 dark:bg-slate-900"
                        >
                            <dd
                                class="text-xl font-semibold text-teal-700 dark:text-teal-300"
                            >
                                {{
                                    roleActivations.counts.admitted_activations
                                }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Admitted
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-white px-4 py-3 dark:bg-slate-900"
                        >
                            <dd
                                class="text-xl font-semibold text-amber-700 dark:text-amber-300"
                            >
                                {{ roleActivations.counts.pending_assignments }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Pending
                            </dt>
                        </div>
                    </dl>
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
                            Role transition compiler
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Lifecycle changes preserve history and expose
                            vacancies
                        </h2>
                        <p
                            class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            Suspension, resignation, removal, revocation, and
                            ending are separately evidenced transitions. A
                            successor is never inferred from the outgoing
                            holder.
                        </p>
                    </div>
                    <dl class="grid shrink-0 grid-cols-3 gap-2 text-center">
                        <div
                            class="rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-950"
                        >
                            <dd class="text-xl font-semibold">
                                {{ roleTransitions.counts.transition_records }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Recorded
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-950"
                        >
                            <dd
                                class="text-xl font-semibold text-amber-700 dark:text-amber-300"
                            >
                                {{
                                    roleTransitions.counts.effective_transitions
                                }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Effective
                            </dt>
                        </div>
                        <div
                            class="rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-950"
                        >
                            <dd
                                class="text-xl font-semibold text-rose-700 dark:text-rose-300"
                            >
                                {{ roleTransitions.counts.vacancies }}
                            </dd>
                            <dt class="text-[10px] text-slate-500 uppercase">
                                Vacancies
                            </dt>
                        </div>
                    </dl>
                </div>
                <div
                    v-if="roleTransitions.counts.vacancies > 0"
                    class="mt-5 grid gap-3 md:grid-cols-2"
                >
                    <article
                        v-for="vacancy in roleTransitions.vacancies"
                        :key="vacancy.key"
                        class="rounded-xl border border-rose-600/20 bg-rose-50/70 p-4 dark:bg-rose-950/20"
                    >
                        <p
                            class="text-xs font-semibold tracking-wide text-rose-700 uppercase dark:text-rose-300"
                        >
                            Vacancy requires separate successor admission
                        </p>
                        <p class="mt-2 text-sm font-semibold">
                            {{ vacancy.role_key }}
                        </p>
                        <p
                            class="mt-1 text-xs text-slate-600 dark:text-slate-400"
                        >
                            Effective {{ vacancy.effective_at }} ·
                            {{ label(vacancy.successor_status) }}
                        </p>
                    </article>
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
                            Identity register
                        </p>
                        <h2 class="mt-2 font-serif text-2xl font-semibold">
                            Known people, without inferred relationships
                        </h2>
                    </div>
                    <Link
                        :href="
                            showDocument(
                                'domains/identity-and-role-assignments',
                            )
                        "
                        class="inline-flex items-center gap-2 self-start rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                    >
                        <FileSearch class="size-4" />
                        Domain doctrine
                    </Link>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <article
                        v-for="identity in identityAndRoles.identities"
                        :key="identity.key"
                        class="rounded-xl border border-slate-200 p-5 dark:border-slate-800"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-serif text-xl font-semibold">
                                    {{ identity.display_name }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ identity.partner_status }}
                                </p>
                            </div>
                            <BadgeCheck class="size-5 text-teal-600" />
                        </div>
                        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 uppercase">
                                    Identity
                                </dt>
                                <dd class="mt-1 capitalize">
                                    {{ identity.lifecycle_status }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase">
                                    Employment
                                </dt>
                                <dd class="mt-1 capitalize">
                                    {{ identity.employment_relationship.state }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase">
                                    Console login
                                </dt>
                                <dd class="mt-1">
                                    {{
                                        identity.authentication_bound
                                            ? 'Bound'
                                            : 'Not bound'
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase">
                                    System accounts
                                </dt>
                                <dd class="mt-1">
                                    {{ identity.system_account_keys.length }}
                                </dd>
                            </div>
                        </dl>
                    </article>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <p
                    class="text-xs font-semibold tracking-[0.18em] text-indigo-700 uppercase dark:text-indigo-400"
                >
                    Role register
                </p>
                <h2 class="mt-2 font-serif text-2xl font-semibold">
                    Coverage reconciled to institutional requirements
                </h2>
                <div class="mt-6 grid gap-3">
                    <article
                        v-for="role in identityAndRoles.roles"
                        :key="role.key"
                        class="grid gap-4 rounded-xl border border-slate-200 p-4 lg:grid-cols-[1.2fr_0.9fr_0.9fr_auto] lg:items-center dark:border-slate-800"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-semibold">
                                    {{ role.name }}
                                </h3>
                                <span
                                    class="rounded-full border px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                                    :class="statusClass(role.coverage_status)"
                                    >{{ label(role.coverage_status) }}</span
                                >
                            </div>
                            <p class="mt-1 text-xs text-slate-500 capitalize">
                                {{ label(role.category) }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-semibold text-slate-500 uppercase"
                            >
                                Recorded holders
                            </p>
                            <p class="mt-1 text-sm">
                                {{
                                    role.recorded_holder_names.join(' · ') ||
                                    'Unassigned'
                                }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-semibold text-slate-500 uppercase"
                            >
                                Authority attachment
                            </p>
                            <p class="mt-1 text-sm capitalize">
                                {{ label(role.authority_attachment) }}
                            </p>
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs text-slate-500"
                        >
                            <KeyRound class="size-4" />{{
                                role.operative_assignment_count
                            }}
                            operative
                        </div>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <p
                        class="text-xs font-semibold tracking-[0.18em] text-indigo-700 uppercase dark:text-indigo-400"
                    >
                        Assignment ledger
                    </p>
                    <h2 class="mt-2 font-serif text-2xl font-semibold">
                        Approved records are preserved before they become
                        operative
                    </h2>
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[48rem] text-left text-sm">
                            <thead
                                class="border-b border-slate-200 text-xs text-slate-500 uppercase dark:border-slate-800"
                            >
                                <tr>
                                    <th class="pb-3">Person</th>
                                    <th class="pb-3">Role</th>
                                    <th class="pb-3">Basis</th>
                                    <th class="pb-3">Lifecycle</th>
                                    <th class="pb-3">Operational state</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 dark:divide-slate-800"
                            >
                                <tr
                                    v-for="assignment in identityAndRoles.assignments"
                                    :key="assignment.key"
                                >
                                    <td class="py-4 font-medium">
                                        {{ assignment.identity_name }}
                                    </td>
                                    <td class="py-4">
                                        {{ assignment.role_name }}
                                    </td>
                                    <td class="py-4 capitalize">
                                        {{ assignment.basis.type }}
                                    </td>
                                    <td class="py-4">
                                        {{ assignment.lifecycle_status_label }}
                                    </td>
                                    <td
                                        class="py-4 text-amber-700 capitalize dark:text-amber-400"
                                    >
                                        <span
                                            :class="
                                                activationAdmitted(
                                                    assignment.key,
                                                )
                                                    ? 'text-teal-700 dark:text-teal-300'
                                                    : ''
                                            "
                                            >{{
                                                label(
                                                    assignment.operational_status,
                                                )
                                            }}</span
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="space-y-5">
                    <div
                        class="rounded-2xl border border-amber-600/20 bg-amber-50 p-5 dark:bg-amber-950/30"
                    >
                        <div
                            class="flex items-center gap-2 text-amber-800 dark:text-amber-300"
                        >
                            <CircleAlert class="size-5" />
                            <h2 class="font-semibold">Activation gap</h2>
                        </div>
                        <p
                            v-for="gap in identityAndRoles.reports
                                .activation_gaps"
                            :key="gap.code"
                            class="mt-3 text-sm leading-6 text-amber-900/80 dark:text-amber-200/80"
                        >
                            {{ gap.message }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <h2 class="font-serif text-xl font-semibold">
                            Compiler safeguards
                        </h2>
                        <ul
                            class="mt-4 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            <li
                                v-for="principle in identityAndRoles.principles"
                                :key="principle"
                                class="flex gap-2"
                            >
                                <ShieldCheck
                                    class="mt-1 size-4 shrink-0 text-indigo-600"
                                />{{ principle }}
                            </li>
                        </ul>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</template>
