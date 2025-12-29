<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { route } from '@/lib/utils';
import { Users, Search, Filter, CheckCircle2, XCircle, Zap } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    users: Array<{
        id: number;
        name: string;
        username: string;
        email: string;
        created_at: string;
        has_active_subscription: boolean;
        active_subscription: string | null;
        subscription_bid_limit: number;
        bids_used: number;
        remaining_bids: number;
        total_bids: number;
    }>;
    stats: {
        total_users: number;
        users_with_active_subscription: number;
        total_remaining_bids: number;
    };
    filters: {
        subscription_status: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: route('admin.users.index'),
    },
];

const searchQuery = ref('');
const subscriptionFilter = ref(props.filters.subscription_status || 'all');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function deleteUser(id: number) {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(route('admin.users.destroy', { id }));
    }
}

function applyFilters() {
    router.get(route('admin.users.index'), {
        search: searchQuery.value,
        subscription_status: subscriptionFilter.value === 'all' ? null : subscriptionFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function handleSearchInput() {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
}

const filteredUsers = computed(() => {
    let result = props.users;
    
    // Client-side search (if needed as fallback)
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(user => 
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            (user.username && user.username.toLowerCase().includes(query))
        );
    }
    
    return result;
});
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto rounded-lg p-3 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Users class="h-5 w-5 text-[#006AFF]" />
                    <h1 class="text-xl font-bold">Users</h1>
                </div>
            </div>

            <!-- Stats Cards - Smaller -->
            <div class="grid gap-2 md:grid-cols-3">
                <Card class="p-2 border-l-2 border-l-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs text-gray-600 mb-0.5">Total Users</div>
                            <div class="text-xl font-bold text-gray-900">{{ stats.total_users }}</div>
                        </div>
                        <Users class="h-8 w-8 text-blue-500 opacity-50" />
                    </div>
                </Card>
                <Card class="p-2 border-l-2 border-l-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs text-gray-600 mb-0.5">Active Subscriptions</div>
                            <div class="text-xl font-bold text-gray-900">{{ stats.users_with_active_subscription }}</div>
                        </div>
                        <CheckCircle2 class="h-8 w-8 text-green-500 opacity-50" />
                    </div>
                </Card>
                <Card class="p-2 border-l-2 border-l-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs text-gray-600 mb-0.5">Total Remaining Bids</div>
                            <div class="text-xl font-bold text-gray-900">{{ stats.total_remaining_bids }}</div>
                        </div>
                        <Zap class="h-8 w-8 text-orange-500 opacity-50" />
                    </div>
                </Card>
            </div>

            <!-- Filters - Smaller -->
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <Search class="absolute left-2 top-1/2 transform -translate-y-1/2 h-3.5 w-3.5 text-gray-400" />
                    <Input
                        v-model="searchQuery"
                        @input="handleSearchInput"
                        placeholder="Search users..."
                        class="pl-8 h-8 text-xs"
                    />
                </div>
                <div class="relative">
                    <Filter class="absolute left-2 top-1/2 transform -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" />
                    <select
                        v-model="subscriptionFilter"
                        @change="applyFilters"
                        class="h-8 w-40 pl-8 pr-3 text-xs border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#006AFF] focus:border-[#006AFF] appearance-none cursor-pointer"
                    >
                        <option value="all">All Users</option>
                        <option value="active">Active Subscription</option>
                        <option value="inactive">No Subscription</option>
                    </select>
                    <div class="absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none">
                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Users Table - Enhanced -->
            <Card class="p-2">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Subscription Status</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Bids</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Usage</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Joined</th>
                                    <th class="px-3 py-2 text-right text-[10px] font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="user in filteredUsers"
                                    :key="user.id"
                                    class="hover:bg-gray-50 transition-colors"
                                    :class="user.has_active_subscription ? 'bg-green-50/30' : ''"
                                >
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 bg-gradient-to-br from-[#006AFF] to-[#29B2FE] rounded-full flex items-center justify-center text-white font-semibold text-xs shadow-sm">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-xs">{{ user.name }}</div>
                                                <div class="text-[10px] text-muted-foreground">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div v-if="user.has_active_subscription" class="space-y-1">
                                            <div class="flex items-center gap-1">
                                                <CheckCircle2 class="h-3 w-3 text-green-600" />
                                                <span class="font-semibold text-green-700 text-xs">{{ user.active_subscription }}</span>
                                            </div>
                                            <div class="text-[10px] text-gray-600">
                                                {{ user.subscription_bid_limit }} bids/month
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center gap-1">
                                            <XCircle class="h-3 w-3 text-gray-400" />
                                            <span class="text-[10px] text-gray-500">No subscription</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div v-if="user.has_active_subscription" class="space-y-0.5">
                                            <div class="flex items-center gap-1">
                                                <Zap class="h-3 w-3 text-orange-500" />
                                                <span class="font-bold text-orange-600 text-xs">{{ user.remaining_bids }}</span>
                                                <span class="text-[10px] text-gray-500">remaining</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500">
                                                {{ user.bids_used }} used
                                            </div>
                                        </div>
                                        <span v-else class="text-[10px] text-gray-400">-</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div v-if="user.has_active_subscription && user.subscription_bid_limit > 0" class="space-y-1">
                                            <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                <div 
                                                    class="h-full rounded-full transition-all"
                                                    :class="user.remaining_bids > user.subscription_bid_limit * 0.3 ? 'bg-green-500' : user.remaining_bids > 0 ? 'bg-yellow-500' : 'bg-red-500'"
                                                    :style="{ width: ((user.remaining_bids / user.subscription_bid_limit) * 100) + '%' }"
                                                ></div>
                                            </div>
                                            <div class="text-[10px] text-gray-600">
                                                {{ Math.round((user.remaining_bids / user.subscription_bid_limit) * 100) }}% left
                                            </div>
                                        </div>
                                        <span v-else class="text-[10px] text-gray-400">-</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="text-[10px] text-muted-foreground">{{ user.created_at }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="h-6 px-2 text-xs"
                                                @click="router.visit(route('admin.users.edit', { id: user.id }))"
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                class="h-6 px-2 text-xs"
                                                @click="deleteUser(user.id)"
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="6" class="px-3 py-6 text-center text-muted-foreground text-xs">
                                        No users found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
