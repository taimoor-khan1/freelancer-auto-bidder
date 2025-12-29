<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { route } from '@/lib/utils';
import { Users, CreditCard, Package, TrendingUp, Zap, DollarSign, Activity, ArrowUpRight } from 'lucide-vue-next';

defineProps<{
    stats: {
        total_users: number;
        total_subscriptions: number;
        active_subscriptions: number;
        total_bids: number;
        total_revenue: number;
    };
    subscriptions: Array<{
        id: number;
        name: string;
        price: number;
        bid_limit: number;
        is_active: boolean;
    }>;
    recentUsers: Array<{
        id: number;
        name: string;
        email: string;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Overview',
        href: route('admin.dashboard'),
    },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto rounded-lg p-3 bg-gray-50">
            <!-- Header -->
            <div class="mb-2">
                <h1 class="text-xl font-bold text-gray-900">Overview</h1>
                <p class="text-xs text-gray-600 mt-0.5">Platform statistics and recent activity</p>
            </div>

            <!-- Stats Cards - Smaller -->
            <div class="grid gap-2 md:grid-cols-4">
                <Card class="border-l-2 border-l-[#006AFF] hover:shadow transition-shadow p-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                        <CardTitle class="text-xs font-medium text-gray-600">Total Users</CardTitle>
                        <div class="p-1 bg-blue-100 rounded">
                            <Users class="h-3.5 w-3.5 text-[#006AFF]" />
                        </div>
                    </CardHeader>
                    <CardContent class="px-2 pb-2">
                        <div class="text-xl font-bold text-gray-900">{{ stats.total_users }}</div>
                        <p class="text-[10px] text-gray-500 mt-0.5">Registered users</p>
                    </CardContent>
                </Card>
                <Card class="border-l-2 border-l-green-500 hover:shadow transition-shadow p-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                        <CardTitle class="text-xs font-medium text-gray-600">Total Revenue</CardTitle>
                        <div class="p-1 bg-green-100 rounded">
                            <DollarSign class="h-3.5 w-3.5 text-green-600" />
                        </div>
                    </CardHeader>
                    <CardContent class="px-2 pb-2">
                        <div class="text-xl font-bold text-gray-900">${{ stats.total_revenue.toFixed(2) }}</div>
                        <p class="text-[10px] text-gray-500 mt-0.5">Active subscriptions</p>
                    </CardContent>
                </Card>
                <Card class="border-l-2 border-l-purple-500 hover:shadow transition-shadow p-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                        <CardTitle class="text-xs font-medium text-gray-600">Active Subs</CardTitle>
                        <div class="p-1 bg-purple-100 rounded">
                            <Package class="h-3.5 w-3.5 text-purple-600" />
                        </div>
                    </CardHeader>
                    <CardContent class="px-2 pb-2">
                        <div class="text-xl font-bold text-gray-900">{{ stats.active_subscriptions }}</div>
                        <p class="text-[10px] text-gray-500 mt-0.5">/ {{ stats.total_subscriptions }} total</p>
                    </CardContent>
                </Card>
                <Card class="border-l-2 border-l-orange-500 hover:shadow transition-shadow p-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                        <CardTitle class="text-xs font-medium text-gray-600">Total Bids</CardTitle>
                        <div class="p-1 bg-orange-100 rounded">
                            <Zap class="h-3.5 w-3.5 text-orange-600" />
                        </div>
                    </CardHeader>
                    <CardContent class="px-2 pb-2">
                        <div class="text-xl font-bold text-gray-900">{{ stats.total_bids }}</div>
                        <p class="text-[10px] text-gray-500 mt-0.5">Bids submitted</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Overview Content -->
            <div class="grid gap-3 md:grid-cols-2">
                <Card class="hover:shadow transition-shadow p-3">
                    <CardHeader class="flex flex-row items-center justify-between p-2 pb-2">
                        <CardTitle class="flex items-center gap-1.5 text-sm">
                            <Users class="h-4 w-4 text-[#006AFF]" />
                            Recent Users
                        </CardTitle>
                        <Link :href="route('admin.users.index')">
                            <Button size="sm" variant="ghost" class="h-6 px-2 text-xs text-[#006AFF]">
                                View All
                                <ArrowUpRight class="h-3 w-3 ml-0.5" />
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent class="p-2 pt-0">
                        <div class="space-y-1.5">
                            <div
                                v-for="user in recentUsers"
                                :key="user.id"
                                class="flex items-center justify-between p-2 border rounded hover:bg-blue-50 transition-colors text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-gradient-to-br from-[#006AFF] to-[#29B2FE] rounded-full flex items-center justify-center text-white font-semibold text-xs shadow-sm">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 text-xs">{{ user.name }}</div>
                                        <div class="text-[10px] text-gray-500">{{ user.email }}</div>
                                    </div>
                                </div>
                                <div class="text-[10px] text-gray-500">{{ user.created_at }}</div>
                            </div>
                            <Link :href="route('admin.users.index')">
                                <Button size="sm" variant="outline" class="w-full h-7 text-xs border-[#006AFF] text-[#006AFF] hover:bg-[#006AFF] hover:text-white">
                                    View All Users
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <Card class="hover:shadow transition-shadow p-3">
                    <CardHeader class="flex flex-row items-center justify-between p-2 pb-2">
                        <CardTitle class="flex items-center gap-1.5 text-sm">
                            <Package class="h-4 w-4 text-[#006AFF]" />
                            Subscription Plans
                        </CardTitle>
                        <Link :href="route('admin.subscriptions.index')">
                            <Button size="sm" variant="ghost" class="h-6 px-2 text-xs text-[#006AFF]">
                                Manage
                                <ArrowUpRight class="h-3 w-3 ml-0.5" />
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent class="p-2 pt-0">
                        <div class="space-y-2">
                            <div
                                v-for="subscription in subscriptions"
                                :key="subscription.id"
                                class="p-2 border rounded hover:shadow-sm transition-shadow bg-gradient-to-r from-white to-gray-50 text-xs"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <div class="font-semibold text-sm text-gray-900">{{ subscription.name }}</div>
                                    <span
                                        class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                                        :class="subscription.is_active 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ subscription.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-1">
                                    <div>
                                        <div class="text-lg font-bold text-[#006AFF]">${{ subscription.price }}</div>
                                        <div class="text-[10px] text-gray-500">per month</div>
                                    </div>
                                    <div class="h-8 w-px bg-gray-200"></div>
                                    <div>
                                        <div class="text-base font-bold text-gray-900">{{ subscription.bid_limit }}</div>
                                        <div class="text-[10px] text-gray-500">bids</div>
                                    </div>
                                </div>
                            </div>
                            <Link :href="route('admin.subscriptions.index')">
                                <Button size="sm" variant="outline" class="w-full h-7 text-xs border-[#006AFF] text-[#006AFF] hover:bg-[#006AFF] hover:text-white">
                                    Manage Subscriptions
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
