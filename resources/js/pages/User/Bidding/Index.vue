<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from '@/lib/utils';

const props = defineProps<{
    biddingSettings: {
        id: number;
        countries: string[] | null;
        technologies: string[] | null;
        categories: string[] | null;
        min_budget: number | null;
        max_budget: number | null;
        budget_type: string | null;
        bidding_times: any[] | null;
        max_bid_amount: number | null;
        cover_letter_template: string | null;
        auto_bid_enabled: boolean;
    } | null;
    activeSubscription: {
        id: number;
        name: string;
        bid_limit: number;
    } | null;
    remainingBids: number;
    biddingJobs: Array<{
        id: number;
        freelancer_project_id: string | null;
        status: string;
        bid_amount: number | null;
        created_at: string;
    }>;
    countries: Array<{
        code: string;
        name: string;
    }>;
    technologies: Array<{
        id: number;
        name: string;
    }>;
    categories: Array<{
        id: number;
        name: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bidding',
        href: route('user.bidding.index'),
    },
];

const activeTab = ref('settings');

const settingsForm = useForm({
    countries: props.biddingSettings?.countries || [],
    technologies: props.biddingSettings?.technologies || [],
    categories: props.biddingSettings?.categories || [],
    min_budget: props.biddingSettings?.min_budget || null,
    max_budget: props.biddingSettings?.max_budget || null,
    budget_type: props.biddingSettings?.budget_type || 'both',
    bidding_times: props.biddingSettings?.bidding_times || [],
    max_bid_amount: props.biddingSettings?.max_bid_amount || null,
    cover_letter_template: props.biddingSettings?.cover_letter_template || '',
    auto_bid_enabled: props.biddingSettings?.auto_bid_enabled || false,
});

// Use data from Freelancer API
const countries = computed(() => props.countries || []);
const technologies = computed(() => props.technologies || []);
const categories = computed(() => props.categories || []);

function submitSettings() {
    settingsForm.post(route('user.bidding.settings'), {
        onSuccess: () => {
            // Show success message
        },
    });
}

function startBidding() {
    router.post(route('user.bidding.start'), {}, {
        onSuccess: () => {
            // Refresh page to update status
            router.reload();
        },
        onError: (errors) => {
            alert(errors.error || 'Failed to start bidding');
        },
    });
}

function stopBidding() {
    router.post(route('user.bidding.stop'), {}, {
        onSuccess: () => {
            router.reload();
        },
    });
}
</script>

<template>
    <Head title="Bidding Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Subscription Info -->
            <Card v-if="activeSubscription">
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">{{ activeSubscription.name }}</div>
                            <div class="text-sm text-muted-foreground">
                                Remaining Bids: {{ remainingBids }} / {{ activeSubscription.bid_limit }}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-if="!biddingSettings?.auto_bid_enabled"
                                size="sm"
                                @click="startBidding"
                                :disabled="remainingBids <= 0"
                            >
                                Start Auto-Bidding
                            </Button>
                            <Button
                                v-else
                                size="sm"
                                variant="destructive"
                                @click="stopBidding"
                            >
                                Stop Auto-Bidding
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabs -->
            <div class="flex gap-2 border-b">
                <button
                    v-for="tab in ['settings', 'country', 'tech', 'timing', 'budget', 'bidding']"
                    :key="tab"
                    @click="activeTab = tab"
                    class="px-3 py-2 text-sm font-medium border-b-2 transition-colors"
                    :class="activeTab === tab 
                        ? 'border-blue-600 text-blue-600' 
                        : 'border-transparent text-gray-600 hover:text-gray-900'"
                >
                    {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
                </button>
            </div>

            <!-- Settings Tab -->
            <Card v-if="activeTab === 'settings'">
                <CardHeader>
                    <CardTitle>General Settings</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitSettings" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="cover_letter">Cover Letter Template</Label>
                            <textarea
                                id="cover_letter"
                                v-model="settingsForm.cover_letter_template"
                                class="w-full min-h-[100px] p-2 border rounded"
                                placeholder="Enter your cover letter template..."
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="max_bid_amount">Max Bid Amount ($)</Label>
                            <Input
                                id="max_bid_amount"
                                type="number"
                                v-model="settingsForm.max_bid_amount"
                            />
                        </div>
                        <Button type="submit" :disabled="settingsForm.processing" size="sm">
                            Save Settings
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Country Tab -->
            <Card v-if="activeTab === 'country'">
                <CardHeader>
                    <CardTitle>Select Countries</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitSettings" class="space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <label
                                v-for="country in countries"
                                :key="country.code"
                                class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-gray-50"
                            >
                                <input
                                    type="checkbox"
                                    :value="country.code"
                                    v-model="settingsForm.countries"
                                    class="rounded"
                                />
                                <span class="text-sm">{{ country.name }} ({{ country.code }})</span>
                            </label>
                        </div>
                        <Button type="submit" :disabled="settingsForm.processing" size="sm">
                            Save Countries
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Tech Tab -->
            <Card v-if="activeTab === 'tech'">
                <CardHeader>
                    <CardTitle>Select Technologies</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitSettings" class="space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <label
                                v-for="tech in technologies"
                                :key="tech.id"
                                class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-gray-50"
                            >
                                <input
                                    type="checkbox"
                                    :value="tech.name"
                                    v-model="settingsForm.technologies"
                                    class="rounded"
                                />
                                <span class="text-sm">{{ tech.name }}</span>
                            </label>
                        </div>
                        <Button type="submit" :disabled="settingsForm.processing" size="sm">
                            Save Technologies
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Timing Tab -->
            <Card v-if="activeTab === 'timing'">
                <CardHeader>
                    <CardTitle>Bidding Times</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitSettings" class="space-y-4">
                        <div class="space-y-2">
                            <Label>Select Time Ranges (24-hour format)</Label>
                            <div class="space-y-2">
                                <div class="flex gap-2 items-center">
                                    <Input type="time" placeholder="Start" class="w-32" />
                                    <span>to</span>
                                    <Input type="time" placeholder="End" class="w-32" />
                                    <Button size="sm" variant="outline">Add</Button>
                                </div>
                            </div>
                        </div>
                        <Button type="submit" :disabled="settingsForm.processing" size="sm">
                            Save Timing
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Budget Tab -->
            <Card v-if="activeTab === 'budget'">
                <CardHeader>
                    <CardTitle>Budget Settings</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitSettings" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="min_budget">Min Budget ($)</Label>
                            <Input
                                id="min_budget"
                                type="number"
                                step="0.01"
                                v-model="settingsForm.min_budget"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="max_budget">Max Budget ($)</Label>
                            <Input
                                id="max_budget"
                                type="number"
                                step="0.01"
                                v-model="settingsForm.max_budget"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="budget_type">Budget Type</Label>
                            <select
                                id="budget_type"
                                v-model="settingsForm.budget_type"
                                class="w-full p-2 border rounded"
                            >
                                <option value="fixed">Fixed</option>
                                <option value="hourly">Hourly</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <Button type="submit" :disabled="settingsForm.processing" size="sm">
                            Save Budget
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Bidding History Tab -->
            <Card v-if="activeTab === 'bidding'">
                <CardHeader>
                    <CardTitle>Bidding History</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div
                            v-for="job in biddingJobs"
                            :key="job.id"
                            class="flex items-center justify-between p-3 border rounded"
                        >
                            <div>
                                <div class="font-medium">Project #{{ job.freelancer_project_id || job.id }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ new Date(job.created_at).toLocaleString() }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div
                                    class="text-xs px-2 py-1 rounded"
                                    :class="{
                                        'bg-green-100 text-green-800': job.status === 'accepted',
                                        'bg-yellow-100 text-yellow-800': job.status === 'pending',
                                        'bg-red-100 text-red-800': job.status === 'rejected',
                                    }"
                                >
                                    {{ job.status }}
                                </div>
                                <div v-if="job.bid_amount" class="text-sm mt-1">
                                    ${{ job.bid_amount }}
                                </div>
                            </div>
                        </div>
                        <div v-if="biddingJobs.length === 0" class="text-center text-muted-foreground py-8">
                            No bidding history yet
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

