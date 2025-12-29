<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { route } from '@/lib/utils';
import { Package, Zap, TrendingUp, Activity, ArrowRight, Settings } from 'lucide-vue-next';

defineProps<{
    user: {
        name: string;
        username: string;
        email: string;
    };
    activeSubscription: {
        id: number;
        name: string;
        bid_limit: number;
        pivot: {
            bids_used: number;
        };
    } | null;
    remainingBids: number;
    biddingSettings: {
        auto_bid_enabled: boolean;
    } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('user.dashboard'),
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto rounded-lg p-3 bg-gray-50">
            <!-- Welcome Header - Smaller -->
            <div class="bg-gradient-to-r from-[#006AFF] to-[#29B2FE] rounded-lg p-3 text-white shadow-md">
                <h1 class="text-lg font-bold mb-0.5">Welcome back, {{ user.name }}!</h1>
                <p class="text-xs text-blue-100">Manage your auto-bidding settings</p>
            </div>

            <!-- Dashboard Content -->
            <div class="space-y-3">
                <!-- Stats Cards - Smaller -->
                <div class="grid gap-2 md:grid-cols-3">
                    <Card class="border-l-2 border-l-[#006AFF] hover:shadow transition-shadow p-2">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                            <CardTitle class="text-xs font-medium text-gray-600">Subscription</CardTitle>
                            <div class="p-1 bg-blue-100 rounded">
                                <Package class="h-3.5 w-3.5 text-[#006AFF]" />
                            </div>
                        </CardHeader>
                        <CardContent class="px-2 pb-2">
                            <div v-if="activeSubscription" class="space-y-1">
                                <div class="text-lg font-bold text-gray-900">{{ activeSubscription.name }}</div>
                                <div class="text-[10px] text-gray-500">
                                    {{ activeSubscription.bid_limit }} bids/month
                                </div>
                            </div>
                            <div v-else class="text-xs text-gray-500">
                                No subscription
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-l-2 border-l-green-500 hover:shadow transition-shadow p-2">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                            <CardTitle class="text-xs font-medium text-gray-600">Remaining Bids</CardTitle>
                            <div class="p-1 bg-green-100 rounded">
                                <Zap class="h-3.5 w-3.5 text-green-600" />
                            </div>
                        </CardHeader>
                        <CardContent class="px-2 pb-2">
                            <div class="text-xl font-bold text-gray-900">{{ remainingBids }}</div>
                            <p class="text-[10px] text-gray-500 mt-0.5">Available</p>
                        </CardContent>
                    </Card>

                    <Card class="border-l-2 border-l-purple-500 hover:shadow transition-shadow p-2">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                            <CardTitle class="text-xs font-medium text-gray-600">Auto-Bidding</CardTitle>
                            <div class="p-1 bg-purple-100 rounded">
                                <Activity class="h-3.5 w-3.5 text-purple-600" />
                            </div>
                        </CardHeader>
                        <CardContent class="px-2 pb-2">
                            <div v-if="biddingSettings?.auto_bid_enabled" class="flex items-center gap-1.5">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-semibold text-green-600">Active</span>
                            </div>
                            <div v-else class="text-gray-500">
                                <span class="text-sm font-semibold">Inactive</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Quick Actions - Smaller -->
                <div class="grid gap-2 md:grid-cols-2">
                    <Card class="hover:shadow transition-shadow bg-gradient-to-br from-white to-blue-50 p-3">
                        <CardHeader class="p-2 pb-1">
                            <CardTitle class="flex items-center gap-1.5 text-sm">
                                <Settings class="h-4 w-4 text-[#006AFF]" />
                                Bidding Settings
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-2 pt-0">
                            <p class="text-xs text-gray-600 mb-2">Configure your auto-bidding preferences.</p>
                            <Link :href="route('user.bidding.index')">
                                <Button size="sm" class="w-full h-7 text-xs bg-[#006AFF] hover:bg-[#0056CC]">
                                    Configure
                                    <ArrowRight class="h-3 w-3 ml-1" />
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    <Card class="hover:shadow transition-shadow bg-gradient-to-br from-white to-green-50 p-3">
                        <CardHeader class="p-2 pb-1">
                            <CardTitle class="flex items-center gap-1.5 text-sm">
                                <TrendingUp class="h-4 w-4 text-green-600" />
                                Bidding History
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-2 pt-0">
                            <p class="text-xs text-gray-600 mb-2">View your bidding history and success rate.</p>
                            <Link :href="route('user.bidding.index') + '?tab=bidding'">
                                <Button size="sm" variant="outline" class="w-full h-7 text-xs border-green-500 text-green-600 hover:bg-green-50">
                                    View History
                                    <ArrowRight class="h-3 w-3 ml-1" />
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>

                <!-- Subscription Info - Smaller -->
                <Card v-if="activeSubscription" class="bg-gradient-to-r from-[#006AFF] to-[#29B2FE] text-white border-0 shadow-md p-3">
                    <CardContent class="p-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] text-blue-100 mb-0.5">Current Plan</div>
                                <div class="text-lg font-bold mb-1">{{ activeSubscription.name }}</div>
                                <div class="text-xs text-blue-100">
                                    {{ remainingBids }} of {{ activeSubscription.bid_limit }} bids remaining
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-blue-100 mb-1">Usage</div>
                                <div class="w-24 h-2 bg-white/20 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-white rounded-full transition-all"
                                        :style="{ width: ((activeSubscription.bid_limit - remainingBids) / activeSubscription.bid_limit * 100) + '%' }"
                                    ></div>
                                </div>
                                <div class="text-[10px] text-blue-100 mt-0.5">
                                    {{ activeSubscription.bid_limit - remainingBids }} / {{ activeSubscription.bid_limit }} used
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
