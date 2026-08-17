<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { InstitutionalNavigationGroup } from '@/types';

defineProps<{
    groups: InstitutionalNavigationGroup[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="flex flex-col gap-1">
        <SidebarGroup
            v-for="group in groups"
            :key="group.key"
            class="px-2 py-1"
        >
            <SidebarGroupLabel>{{ group.title }}</SidebarGroupLabel>
            <SidebarGroupContent>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="document in group.documents"
                        :key="document.key"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentUrl(document.href)"
                            :tooltip="document.title"
                        >
                            <Link :href="document.href" prefetch>
                                <FileText />
                                <span>{{ document.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    </div>
</template>
