<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { route } from '@/lib/utils';
import { CreditCard, DollarSign, TrendingUp, Search } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    payments: Array<{
        id: number;
        user_name: string;
        user_email: string;
        subscription_name: string;
        amount: number;
        status: string;
        starts_at: string | null;
        ends_at: string | null;
        bids_used: number;
        created_at: string;
    }>;
    stats: {
        total_revenue: number;
        total_payments: number;
        active_payments: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payments',
        href: route('admin.payments.index'),
    },
];

const searchQuery = ref('');

const filteredPayments = computed(() => {
    if (!searchQuery.value) return props.payments;
    const query = searchQuery.value.toLowerCase();
    return props.payments.filter(payment => 
        payment.user_name.toLowerCase().includes(query) ||
        payment.user_email.toLowerCase().includes(query) ||
        payment.subscription_name.toLowerCase().includes(query)
    );
});
</script>

<template>
    <Head title="Payments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto rounded-lg p-3 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <CreditCard class="h-5 w-5 text-[#006AFF]" />
                    <h1 class="text-xl font-bold">Payments</h1>
                </div>
            </div>

            <!-- Stats - Smaller -->
            <div class="grid gap-2 md:grid-cols-3">
                <Card class="p-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                        <CardTitle class="text-xs font-medium text-gray-600">Total Revenue</CardTitle>
                        <div class="p-1 bg-green-100 rounded">
                            <DollarSign class="h-3.5 w-3.5 text-green-600" />
                        </div>
                    </CardHeader>
                    <CardContent class="px-2 pb-2">
                        <div class="text-xl font-bold text-gray-900">${{ stats.total_revenue.toFixed(2) }}</div>
                    </CardContent>
                </Card>
                <Card class="p-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                        <CardTitle class="text-xs font-medium text-gray-600">Total Payments</CardTitle>
                        <div class="p-1 bg-blue-100 rounded">
                            <CreditCard class="h-3.5 w-3.5 text-[#006AFF]" />
                        </div>
                    </CardHeader>
                    <CardContent class="px-2 pb-2">
                        <div class="text-xl font-bold text-gray-900">{{ stats.total_payments }}</div>
                    </CardContent>
                </Card>
                <Card class="p-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 px-2 pt-2">
                        <CardTitle class="text-xs font-medium text-gray-600">Active Payments</CardTitle>
                        <div class="p-1 bg-purple-100 rounded">
                            <TrendingUp class="h-3.5 w-3.5 text-purple-600" />
                        </div>
                    </CardHeader>
                    <CardContent class="px-2 pb-2">
                        <div class="text-xl font-bold text-gray-900">{{ stats.active_payments }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Search - Smaller -->
            <div class="relative">
                <Search class="absolute left-2 top-1/2 transform -translate-y-1/2 h-3.5 w-3.5 text-gray-400" />
                <Input
                    v-model="searchQuery"
                    placeholder="Search payments..."
                    class="pl-8 h-8 text-xs"
                />
            </div>

            <!-- Payments Table - Smaller -->
            <Card class="p-2">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Subscription</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Period</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Bids Used</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="payment in filteredPayments"
                                    :key="payment.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-3 py-2">
                                        <div>
                                            <div class="font-medium text-xs">{{ payment.user_name }}</div>
                                            <div class="text-[10px] text-muted-foreground">{{ payment.user_email }}</div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="text-xs font-medium">{{ payment.subscription_name }}</div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="font-semibold text-[#006AFF] text-xs">${{ payment.amount.toFixed(2) }}</div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            class="text-[10px] px-1.5 py-0.5 rounded"
                                            :class="payment.status === 'Active' 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-gray-100 text-gray-800'"
                                        >
                                            {{ payment.status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="text-xs">
                                            <div v-if="payment.starts_at">{{ payment.starts_at }}</div>
                                            <div v-if="payment.ends_at" class="text-[10px] text-muted-foreground">to {{ payment.ends_at }}</div>
                                            <div v-else class="text-[10px] text-muted-foreground">No end date</div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="text-xs font-medium">{{ payment.bids_used }}</div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="text-[10px] text-muted-foreground">{{ payment.created_at }}</div>
                                    </td>
                                </tr>
                                <tr v-if="filteredPayments.length === 0">
                                    <td colspan="7" class="px-3 py-6 text-center text-muted-foreground text-xs">
                                        No payments found
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
