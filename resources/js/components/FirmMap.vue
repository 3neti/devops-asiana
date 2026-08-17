<script setup lang="ts">
import {
    BriefcaseBusiness,
    Building2,
    CheckCircle2,
    Landmark,
    Scale,
    ShieldAlert,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import type {
    EconomicProjection,
    FoundingPartner,
    ManagementOffice,
    ResponsibilityAssignment,
} from '@/types';

type Projection =
    'partnership' | 'management' | 'responsibilities' | 'economics';

const props = defineProps<{
    firm: {
        name: string;
        jurisdiction: string;
        legal_form: string;
        legal_status: string;
    };
    partners: FoundingPartner[];
    management: ManagementOffice[];
    responsibilities: ResponsibilityAssignment[];
    economics: EconomicProjection;
}>();

const activeProjection = ref<Projection>('partnership');
const selectedPartnerKey = ref(props.partners[0]?.key ?? '');

const selectedPartner = computed(
    () =>
        props.partners.find(
            (partner) => partner.key === selectedPartnerKey.value,
        ) ?? props.partners[0],
);

const projections: Array<{ key: Projection; label: string }> = [
    { key: 'partnership', label: 'Partnership' },
    { key: 'management', label: 'Management' },
    { key: 'responsibilities', label: 'Responsibilities' },
    { key: 'economics', label: 'Economics' },
];

function selectPartner(partner: FoundingPartner): void {
    selectedPartnerKey.value = partner.key;
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-[#fbfaf6] shadow-sm dark:border-slate-800 dark:bg-slate-950"
    >
        <header
            class="flex flex-col gap-6 border-b border-slate-200 px-5 py-5 sm:px-7 lg:flex-row lg:items-end lg:justify-between dark:border-slate-800"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-950 text-stone-50 dark:bg-stone-100 dark:text-slate-950"
                >
                    <Landmark class="size-5" />
                </div>
                <div class="grid gap-1">
                    <p
                        class="text-xs font-semibold tracking-[0.2em] text-teal-700 uppercase dark:text-teal-400"
                    >
                        Institutional projection
                    </p>
                    <h2
                        class="font-serif text-2xl font-semibold tracking-tight text-slate-950 dark:text-stone-50"
                    >
                        Firm Map
                    </h2>
                    <p
                        class="max-w-2xl text-sm text-slate-600 dark:text-slate-400"
                    >
                        One institutional truth, viewed through partnership,
                        management, responsibility, and economics.
                    </p>
                </div>
            </div>

            <div
                role="tablist"
                aria-label="Firm Map projection"
                class="grid grid-cols-2 gap-1 rounded-xl border border-slate-200 bg-white p-1 sm:flex dark:border-slate-700 dark:bg-slate-900"
            >
                <button
                    v-for="projection in projections"
                    :key="projection.key"
                    type="button"
                    role="tab"
                    :aria-selected="activeProjection === projection.key"
                    class="rounded-lg px-3 py-2 text-xs font-semibold transition-colors"
                    :class="
                        activeProjection === projection.key
                            ? 'bg-slate-950 text-white shadow-sm dark:bg-stone-100 dark:text-slate-950'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white'
                    "
                    @click="activeProjection = projection.key"
                >
                    {{ projection.label }}
                </button>
            </div>
        </header>

        <div class="p-5 sm:p-7">
            <div v-if="activeProjection === 'partnership'" class="grid gap-8">
                <div
                    class="mx-auto flex max-w-xl flex-col items-center text-center"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded-full border border-teal-600/30 bg-teal-50 text-teal-800 dark:bg-teal-950 dark:text-teal-300"
                    >
                        <Building2 class="size-5" />
                    </div>
                    <p
                        class="mt-3 font-serif text-xl font-semibold text-slate-950 dark:text-stone-50"
                    >
                        {{ firm.name }}
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ firm.jurisdiction }} · {{ firm.legal_form }}
                    </p>
                    <div class="h-8 w-px bg-slate-300 dark:bg-slate-700" />
                    <div class="h-px w-1/2 bg-slate-300 dark:bg-slate-700" />
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <button
                        v-for="partner in partners"
                        :key="partner.key"
                        type="button"
                        class="group rounded-2xl border bg-white p-5 text-left transition-all hover:-translate-y-0.5 hover:shadow-md dark:bg-slate-900"
                        :class="
                            selectedPartnerKey === partner.key
                                ? 'border-teal-600 ring-2 ring-teal-600/10 dark:border-teal-500'
                                : 'border-slate-200 dark:border-slate-800'
                        "
                        @click="selectPartner(partner)"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex size-10 items-center justify-center rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    <UserRound class="size-5" />
                                </div>
                                <div>
                                    <p
                                        class="font-serif text-lg font-semibold text-slate-950 dark:text-stone-50"
                                    >
                                        {{ partner.name }}
                                    </p>
                                    <p
                                        class="text-xs font-semibold tracking-wide text-teal-700 uppercase dark:text-teal-400"
                                    >
                                        {{ partner.partner_status }}
                                    </p>
                                </div>
                            </div>
                            <CheckCircle2
                                v-if="selectedPartnerKey === partner.key"
                                class="size-5 text-teal-600"
                            />
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950"
                            >
                                <p class="text-xs text-slate-500">Governance</p>
                                <p class="mt-1 text-xl font-semibold">
                                    {{ partner.governance_weight }}%
                                </p>
                            </div>
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950"
                            >
                                <p class="text-xs text-slate-500">Economics</p>
                                <p class="mt-1 text-xl font-semibold">
                                    {{ partner.economic_allocation }}%
                                </p>
                            </div>
                        </div>
                        <p
                            class="mt-4 text-sm text-slate-600 dark:text-slate-400"
                        >
                            {{ partner.operational_posture }}
                        </p>
                    </button>
                </div>

                <div
                    class="mx-auto flex w-full max-w-xl flex-col items-center text-center"
                >
                    <div class="h-7 w-px bg-slate-300 dark:bg-slate-700" />
                    <div
                        class="w-full rounded-2xl border border-dashed border-amber-500/50 bg-amber-50/70 p-5 dark:bg-amber-950/20"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-amber-800 uppercase dark:text-amber-300"
                        >
                            Institutional participant — not a Partner
                        </p>
                        <p
                            class="mt-2 font-serif text-lg font-semibold text-slate-950 dark:text-stone-50"
                        >
                            {{ economics.firm_allocation.label }} ·
                            {{ economics.firm_allocation.percentage }}%
                        </p>
                        <p
                            class="mt-1 text-sm text-slate-600 dark:text-slate-400"
                        >
                            A portion of every Engagement Contribution builds
                            the Firm.
                        </p>
                    </div>
                </div>

                <aside
                    v-if="selectedPartner"
                    class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-5 lg:grid-cols-[0.8fr_1.2fr] dark:border-slate-800 dark:bg-slate-900"
                >
                    <div>
                        <p
                            class="text-xs font-semibold text-slate-500 uppercase"
                        >
                            Selected Partner
                        </p>
                        <h3
                            class="mt-2 font-serif text-xl font-semibold text-slate-950 dark:text-stone-50"
                        >
                            {{ selectedPartner.name }}
                        </h3>
                        <p
                            class="mt-1 text-sm text-teal-700 dark:text-teal-400"
                        >
                            {{ selectedPartner.partner_status }}
                            <template v-if="selectedPartner.offices.length">
                                · Managing Partner
                            </template>
                        </p>
                        <dl class="mt-5 grid gap-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">
                                    Governance weight
                                </dt>
                                <dd class="font-semibold">
                                    {{ selectedPartner.governance_weight }}%
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">
                                    Economic allocation
                                </dt>
                                <dd class="font-semibold">
                                    {{ selectedPartner.economic_allocation }}%
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Operational role</dt>
                                <dd class="max-w-48 text-right font-semibold">
                                    {{ selectedPartner.operational_posture }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <p
                                class="text-xs font-semibold text-slate-500 uppercase"
                            >
                                Primary responsibilities
                            </p>
                            <ul class="mt-3 grid gap-2 text-sm">
                                <li
                                    v-for="responsibility in selectedPartner.primary_responsibilities"
                                    :key="responsibility"
                                    class="flex gap-2 text-slate-700 dark:text-slate-300"
                                >
                                    <span
                                        class="mt-2 size-1 rounded-full bg-teal-600"
                                    />
                                    {{ responsibility }}
                                </li>
                            </ul>
                        </div>
                        <div>
                            <p
                                class="text-xs font-semibold text-slate-500 uppercase"
                            >
                                Authority & constitutional rights
                            </p>
                            <ul class="mt-3 grid gap-2 text-sm">
                                <li
                                    v-for="right in selectedPartner.constitutional_rights"
                                    :key="right"
                                    class="flex gap-2 text-slate-700 dark:text-slate-300"
                                >
                                    <Scale
                                        class="mt-0.5 size-4 shrink-0 text-amber-700"
                                    />
                                    {{ right }}
                                </li>
                                <li
                                    v-if="selectedPartner.offices.length"
                                    class="flex gap-2 text-slate-700 dark:text-slate-300"
                                >
                                    <BriefcaseBusiness
                                        class="mt-0.5 size-4 shrink-0 text-teal-700"
                                    />
                                    Managing Partner authority derives from the
                                    office, subject to the Authority Matrix and
                                    Reserved Matters.
                                </li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>

            <div
                v-else-if="activeProjection === 'management'"
                class="grid gap-5"
            >
                <div
                    v-for="office in management"
                    :key="office.key"
                    class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 lg:grid-cols-[0.8fr_1.2fr] dark:border-slate-800 dark:bg-slate-900"
                >
                    <div>
                        <p
                            class="text-xs font-semibold text-teal-700 uppercase"
                        >
                            Office-based authority
                        </p>
                        <h3 class="mt-2 font-serif text-2xl font-semibold">
                            {{ office.name }}
                        </h3>
                        <p class="mt-3 text-sm text-slate-500">
                            Current holder
                        </p>
                        <p class="font-semibold">{{ office.holder_name }}</p>
                        <p
                            class="mt-4 text-sm text-slate-600 dark:text-slate-400"
                        >
                            {{ office.authority_basis }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-slate-500 uppercase"
                        >
                            Responsibilities carried by the office
                        </p>
                        <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                            <li
                                v-for="responsibility in office.responsibilities"
                                :key="responsibility"
                                class="rounded-xl bg-slate-50 px-4 py-3 text-sm dark:bg-slate-950"
                            >
                                {{ responsibility }}
                            </li>
                        </ul>
                    </div>
                </div>
                <p
                    class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-400"
                >
                    Anaïs manages the Firm. Lester and Anaïs govern the
                    Partnership. Office authority can transfer without rewriting
                    a person's constitutional status.
                </p>
            </div>

            <div
                v-else-if="activeProjection === 'responsibilities'"
                class="grid gap-3 md:grid-cols-2"
            >
                <div
                    v-for="responsibility in responsibilities"
                    :key="responsibility.key"
                    class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div>
                        <p class="text-sm font-semibold">
                            {{ responsibility.label }}
                        </p>
                        <p
                            class="mt-1 text-xs"
                            :class="
                                responsibility.status === 'assigned'
                                    ? 'text-slate-500'
                                    : 'font-semibold text-amber-700 dark:text-amber-400'
                            "
                        >
                            {{
                                responsibility.holder_names.join(' + ') ||
                                'Unassigned responsibility'
                            }}
                        </p>
                    </div>
                    <ShieldAlert
                        v-if="responsibility.status === 'unassigned'"
                        class="size-5 shrink-0 text-amber-600"
                    />
                    <CheckCircle2
                        v-else
                        class="size-5 shrink-0 text-teal-600"
                    />
                </div>
            </div>

            <div v-else class="grid gap-6">
                <div>
                    <p class="text-xs font-semibold text-teal-700 uppercase">
                        Allocation basis
                    </p>
                    <h3 class="mt-2 font-serif text-2xl font-semibold">
                        {{ economics.basis }}
                    </h3>
                    <p
                        class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400"
                    >
                        {{ economics.basis_definition }}
                    </p>
                </div>
                <div
                    class="flex h-12 overflow-hidden rounded-xl text-xs font-bold text-white shadow-inner"
                    aria-label="Engagement Contribution allocation"
                >
                    <div
                        class="flex items-center justify-center bg-slate-800"
                        :style="{
                            width: `${economics.partner_allocations[0]?.percentage}%`,
                        }"
                    >
                        {{ economics.partner_allocations[0]?.percentage }}%
                    </div>
                    <div
                        class="flex items-center justify-center bg-teal-700"
                        :style="{
                            width: `${economics.partner_allocations[1]?.percentage}%`,
                        }"
                    >
                        {{ economics.partner_allocations[1]?.percentage }}%
                    </div>
                    <div
                        class="flex items-center justify-center bg-amber-600"
                        :style="{
                            width: `${economics.firm_allocation.percentage}%`,
                        }"
                    >
                        {{ economics.firm_allocation.percentage }}%
                    </div>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <div
                        v-for="allocation in economics.partner_allocations"
                        :key="allocation.key"
                        class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <p class="text-sm font-semibold">
                            {{ allocation.name }}
                        </p>
                        <p class="mt-2 text-3xl font-semibold">
                            {{ allocation.percentage }}%
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Partner allocation
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-amber-500/40 bg-amber-50/70 p-4 dark:bg-amber-950/20"
                    >
                        <p class="text-sm font-semibold">
                            {{ economics.firm_allocation.label }}
                        </p>
                        <p class="mt-2 text-3xl font-semibold">
                            {{ economics.firm_allocation.percentage }}%
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Institutional allocation — not capital and not a
                            Partner
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
