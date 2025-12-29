<script setup lang="ts">
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();
</script>

<template>
    <SidebarMenu class="space-y-1">
        <SidebarMenuItem v-for="item in items" :key="item.title">
            <SidebarMenuButton
                as-child
                :is-active="urlIsActive(item.href, page.url)"
                :tooltip="item.title"
                class="group relative"
            >
                <Link 
                    :href="item.href" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 hover:bg-[#006AFF]/10 hover:text-[#006AFF] data-[active=true]:bg-[#006AFF]/10 data-[active=true]:text-[#006AFF] data-[active=true]:font-semibold data-[active=true]:shadow-sm"
                >
                    <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
                    <span class="text-sm">{{ item.title }}</span>
                    <span 
                        v-if="urlIsActive(item.href, page.url)"
                        class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-[#006AFF] rounded-r-full"
                    ></span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
