<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { route } from '@/lib/utils';
import { FolderKanban, Clock, DollarSign, ExternalLink, CheckCircle2, XCircle, AlertCircle, Send, Zap } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    liveProjects: Array<{
        id: number | string;
        title: string;
        description: string | null;
        budget: number | null;
        budget_max: number | null;
        url: string;
        posted_at: string | null;
        status: string;
        has_bid: boolean;
        jobs: Array<any>;
        currency: string;
    }>;
    sentProposals: {
        data: Array<{
            id: number;
            freelancer_project_id: string | null;
            project_title: string | null;
            project_description: string | null;
            project_budget: number | null;
            project_url: string | null;
            project_posted_at: string | null;
            status: string;
            bid_amount: number | null;
            cover_letter: string | null;
            submitted_at: string | null;
            created_at: string;
        }>;
        links?: any;
        meta?: any;
    };
    activeTab: string;
    filters: {
        status: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('user.dashboard'),
    },
    {
        title: 'Projects',
        href: route('user.projects.index'),
    },
];

const activeTab = ref(props.activeTab || 'live');
const statusFilter = ref(props.filters.status || 'all');

function switchTab(tab: string) {
    activeTab.value = tab;
    router.get(route('user.projects.index'), { tab }, {
        preserveState: true,
        replace: true,
    });
}

function applyFilter() {
    router.get(route('user.projects.index'), { 
        tab: activeTab.value,
        status: statusFilter.value === 'all' ? null : statusFilter.value 
    }, {
        preserveState: true,
        replace: true,
    });
}

function getStatusIcon(status: string) {
    switch (status) {
        case 'accepted':
            return CheckCircle2;
        case 'rejected':
            return XCircle;
        case 'pending':
            return AlertCircle;
        default:
            return AlertCircle;
    }
}

function getStatusColor(status: string) {
    switch (status) {
        case 'accepted':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}
</script>

<template>
    <Head title="Projects" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto rounded-lg p-3 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <FolderKanban class="h-5 w-5 text-[#006AFF]" />
                    <h1 class="text-xl font-bold">Projects</h1>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 border-b border-gray-200">
                <button
                    @click="switchTab('live')"
                    class="px-3 py-2 text-xs font-medium border-b-2 transition-colors flex items-center gap-1.5"
                    :class="activeTab === 'live' 
                        ? 'border-[#006AFF] text-[#006AFF]' 
                        : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'"
                >
                    <Zap class="h-3.5 w-3.5" />
                    Live Projects
                </button>
                <button
                    @click="switchTab('proposals')"
                    class="px-3 py-2 text-xs font-medium border-b-2 transition-colors flex items-center gap-1.5"
                    :class="activeTab === 'proposals' 
                        ? 'border-[#006AFF] text-[#006AFF]' 
                        : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'"
                >
                    <Send class="h-3.5 w-3.5" />
                    Sent Proposals
                </button>
            </div>

            <!-- Live Projects Tab -->
            <div v-if="activeTab === 'live'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-600">Showing live projects from Freelancer matching your bidding criteria</p>
                    <Button 
                        size="sm" 
                        variant="outline" 
                        class="h-7 px-3 text-xs"
                        @click="router.reload({ only: ['liveProjects'] })"
                    >
                        Refresh
                    </Button>
                </div>

                <div class="space-y-2">
                    <Card
                        v-for="project in liveProjects"
                        :key="project.id"
                        class="p-3 hover:shadow-md transition-shadow"
                    >
                        <CardContent class="p-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-start gap-2">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h3 class="font-semibold text-sm text-gray-900">
                                                    {{ project.title }}
                                                </h3>
                                                <span
                                                    v-if="project.has_bid"
                                                    class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 font-medium"
                                                >
                                                    Bid Sent
                                                </span>
                                            </div>
                                            <p v-if="project.description" class="text-xs text-gray-600 line-clamp-2 mb-2">
                                                {{ project.description }}
                                            </p>
                                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                                <div v-if="project.budget" class="flex items-center gap-1">
                                                    <DollarSign class="h-3 w-3" />
                                                    <span>
                                                        ${{ project.budget.toFixed(2) }}
                                                        <span v-if="project.budget_max && project.budget_max !== project.budget">
                                                            - ${{ project.budget_max.toFixed(2) }}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div v-if="project.posted_at" class="flex items-center gap-1">
                                                    <Clock class="h-3 w-3" />
                                                    <span>Posted: {{ new Date(project.posted_at).toLocaleString() }}</span>
                                                </div>
                                                <div v-if="project.jobs && project.jobs.length > 0" class="flex items-center gap-1">
                                                    <span class="text-[10px] px-1.5 py-0.5 bg-gray-100 rounded">
                                                        {{ project.jobs.length }} skill(s)
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span
                                        class="text-[10px] px-2 py-1 rounded-full font-medium"
                                        :class="project.status === 'active' 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ project.status }}
                                    </span>
                                    <a
                                        :href="project.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-[#006AFF] hover:text-[#0056CC]"
                                    >
                                        <ExternalLink class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div v-if="liveProjects.length === 0" class="text-center py-8 text-gray-500 text-sm">
                        <p>No live projects found</p>
                        <p class="text-xs text-gray-400 mt-1">Make sure your Freelancer account is connected and bidding settings are configured.</p>
                    </div>
                </div>
            </div>

            <!-- Sent Proposals Tab -->
            <div v-if="activeTab === 'proposals'" class="space-y-3">
                <!-- Filter -->
                <div class="flex gap-2">
                    <select
                        v-model="statusFilter"
                        @change="applyFilter"
                        class="h-8 px-3 text-xs border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#006AFF] focus:border-[#006AFF]"
                    >
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <!-- Proposals List -->
                <div class="space-y-2">
                    <Card
                        v-for="proposal in sentProposals.data"
                        :key="proposal.id"
                        class="p-3 hover:shadow-md transition-shadow"
                    >
                        <CardContent class="p-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-start gap-2">
                                        <component :is="getStatusIcon(proposal.status)" class="h-4 w-4 mt-0.5 flex-shrink-0" :class="{
                                            'text-green-600': proposal.status === 'accepted',
                                            'text-red-600': proposal.status === 'rejected',
                                            'text-yellow-600': proposal.status === 'pending',
                                        }" />
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-sm text-gray-900 mb-1">
                                                {{ proposal.project_title || `Project #${proposal.freelancer_project_id || proposal.id}` }}
                                            </h3>
                                            <p v-if="proposal.project_description" class="text-xs text-gray-600 line-clamp-2 mb-2">
                                                {{ proposal.project_description }}
                                            </p>
                                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                                <div v-if="proposal.project_budget" class="flex items-center gap-1">
                                                    <DollarSign class="h-3 w-3" />
                                                    <span>Budget: ${{ proposal.project_budget.toFixed(2) }}</span>
                                                </div>
                                                <div v-if="proposal.project_posted_at" class="flex items-center gap-1">
                                                    <Clock class="h-3 w-3" />
                                                    <span>Posted: {{ new Date(proposal.project_posted_at).toLocaleString() }}</span>
                                                </div>
                                                <div v-if="proposal.submitted_at" class="flex items-center gap-1">
                                                    <Send class="h-3 w-3" />
                                                    <span>Sent: {{ new Date(proposal.submitted_at).toLocaleString() }}</span>
                                                </div>
                                                <div v-if="proposal.bid_amount" class="font-semibold text-[#006AFF]">
                                                    Your Bid: ${{ proposal.bid_amount.toFixed(2) }}
                                                </div>
                                            </div>
                                            <div v-if="proposal.cover_letter" class="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-600 line-clamp-2">
                                                {{ proposal.cover_letter }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span
                                        class="text-[10px] px-2 py-1 rounded-full font-medium"
                                        :class="getStatusColor(proposal.status)"
                                    >
                                        {{ proposal.status }}
                                    </span>
                                    <a
                                        v-if="proposal.project_url"
                                        :href="proposal.project_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-[#006AFF] hover:text-[#0056CC]"
                                    >
                                        <ExternalLink class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div v-if="sentProposals.data.length === 0" class="text-center py-8 text-gray-500 text-sm">
                        No proposals sent yet
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="sentProposals.links && sentProposals.links.length > 3" class="flex justify-center gap-2">
                    <Link
                        v-for="link in sentProposals.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 text-xs rounded border',
                            link.active
                                ? 'bg-[#006AFF] text-white border-[#006AFF]'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
