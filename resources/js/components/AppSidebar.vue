<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    Landmark,
    ScrollText,
    UserRoundCheck,
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
import { index as clientAcceptance } from '@/routes/client-acceptance';
import { index as engagements } from '@/routes/engagements';
import { index as policyRegistry } from '@/routes/policy-registry';
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
