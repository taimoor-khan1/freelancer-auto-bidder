<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
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
    SidebarGroup,
    SidebarGroupLabel,
} from '@/components/ui/sidebar';
import { route } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { 
    LayoutGrid, 
    Package, 
    Users, 
    CreditCard, 
    Settings,
    Zap,
    BarChart3,
    FolderKanban,
    Briefcase,
    Bot,
} from 'lucide-vue-next';
import { computed } from 'vue';
import FreelancerLogo from './FreelancerLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.role === 'admin');

// Admin navigation items
const adminNavItems: NavItem[] = [
    {
        title: 'Overview',
        href: route('admin.dashboard'),
        icon: LayoutGrid,
    },
    {
        title: 'Subscriptions',
        href: route('admin.subscriptions.index'),
        icon: Package,
    },
    {
        title: 'Users',
        href: route('admin.users.index'),
        icon: Users,
    },
    {
        title: 'Payments',
        href: route('admin.payments.index'),
        icon: CreditCard,
    },
    {
        title: 'AI Settings',
        href: route('admin.ai-settings.index'),
        icon: Bot,
    },
];

// User navigation items
const userNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: route('user.dashboard'),
        icon: LayoutGrid,
    },
    {
        title: 'Bidding Settings',
        href: route('user.bidding.index'),
        icon: Zap,
    },
    {
        title: 'Projects',
        href: route('user.projects.index'),
        icon: FolderKanban,
    },
    {
        title: 'Portfolio',
        href: route('user.portfolio.index'),
        icon: Briefcase,
    },
    {
        title: 'Bidding History',
        href: route('user.bidding.index') + '?tab=bidding',
        icon: BarChart3,
    },
];

const mainNavItems = computed(() => isAdmin.value ? adminNavItems : userNavItems);

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: '/settings/profile',
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-gray-200 bg-white shadow-sm">
        <SidebarHeader class="bg-white p-4 border-b border-gray-200">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="bg-transparent hover:bg-gray-50 transition-colors">
                        <Link :href="isAdmin ? route('admin.dashboard') : route('user.dashboard')">
                            <div class="flex items-center gap-3">
                                <div class="flex aspect-square size-10 items-center justify-center rounded-lg bg-white shadow-sm border border-gray-200">
                                    <FreelancerLogo :show-text="false" class="h-6 w-6" />
                                </div>
                                <div class="grid flex-1 text-left text-sm min-w-0">
                                    <span class="truncate leading-tight font-bold text-gray-900 text-base"
                                        >Auto-Bidder</span
                                    >
                                    <span class="truncate text-xs text-gray-600 font-medium"
                                        >{{ isAdmin ? 'Admin Panel' : 'User Dashboard' }}</span
                                    >
                                </div>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-3 py-4">
            <SidebarGroup>
                <SidebarGroupLabel class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 px-2">
                    {{ isAdmin ? 'Administration' : 'Navigation' }}
                </SidebarGroupLabel>
                <NavMain :items="mainNavItems" />
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="px-3 border-t border-gray-200 bg-gray-50/50">
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
