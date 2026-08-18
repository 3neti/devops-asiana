<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    CircleCheckBig,
    ContactRound,
    DatabaseBackup,
    FileCheck2,
    GitPullRequestArrow,
    Gavel,
    KeyRound,
    Landmark,
    ScrollText,
    ShieldAlert,
    Siren,
    UserRoundCheck,
    UsersRound,
    Vote,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import InstitutionalDocumentNav from '@/components/InstitutionalDocumentNav.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as authorityMatrix } from '@/routes/authority-matrix';
import { index as breakGlassAccess } from '@/routes/break-glass-access';
import { index as changes } from '@/routes/changes';
import { index as clientAcceptance } from '@/routes/client-acceptance';
import { index as continuityExercises } from '@/routes/continuity-exercises';
import { index as correctiveActions } from '@/routes/corrective-actions';
import { index as decisionRecords } from '@/routes/decision-records';
import { index as engagements } from '@/routes/engagements';
import { index as governanceMeetings } from '@/routes/governance-meetings';
import { index as identityAndRoles } from '@/routes/identity-and-roles';
import { index as incidents } from '@/routes/incidents';
import { index as policyRegistry } from '@/routes/policy-registry';
import { index as productionAccess } from '@/routes/production-access';
import { index as responsibilityCoverage } from '@/routes/responsibility-coverage';
import type { InstitutionalNavigationGroup, NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Firm Map',
        href: dashboard(),
        icon: Landmark,
    },
    {
        title: 'Policy Register',
        href: policyRegistry(),
        icon: ScrollText,
    },
    {
        title: 'Client Acceptance',
        href: clientAcceptance(),
        icon: UserRoundCheck,
    },
    {
        title: 'Engagements',
        href: engagements(),
        icon: BriefcaseBusiness,
    },
    {
        title: 'Production Access',
        href: productionAccess(),
        icon: KeyRound,
    },
    {
        title: 'Break-glass Access',
        href: breakGlassAccess(),
        icon: ShieldAlert,
    },
    {
        title: 'Changes',
        href: changes(),
        icon: GitPullRequestArrow,
    },
    {
        title: 'Incidents',
        href: incidents(),
        icon: Siren,
    },
    {
        title: 'Corrective Actions',
        href: correctiveActions(),
        icon: CircleCheckBig,
    },
    {
        title: 'Continuity Exercises',
        href: continuityExercises(),
        icon: DatabaseBackup,
    },
    {
        title: 'Responsibility Coverage',
        href: responsibilityCoverage(),
        icon: UsersRound,
    },
    {
        title: 'Identity & Roles',
        href: identityAndRoles(),
        icon: ContactRound,
    },
    {
        title: 'Authority Matrix',
        href: authorityMatrix(),
        icon: Gavel,
    },
    {
        title: 'Decisions & Approvals',
        href: decisionRecords(),
        icon: FileCheck2,
    },
    {
        title: 'Governance Meetings',
        href: governanceMeetings(),
        icon: Vote,
    },
];

const page = usePage();
const institutionalNavigation = page.props
    .institutionalNavigation as InstitutionalNavigationGroup[];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain label="Console" :items="mainNavItems" />
            <InstitutionalDocumentNav :groups="institutionalNavigation" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser v-if="page.props.auth.user" />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
