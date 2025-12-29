<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { route } from '@/lib/utils';
import FreelancerLogo from '@/components/FreelancerLogo.vue';
import { computed } from 'vue';

const page = usePage();

const props = withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const flashMessage = computed(() => page.props.flash?.success || page.props.flash?.error);
const flashType = computed(() => {
    if (page.props.flash?.success) return 'success';
    if (page.props.flash?.error) return 'error';
    return null;
});
</script>

<template>
    <Head title="Welcome - Freelancer Auto-Bidding">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="min-h-screen bg-gradient-to-br from-[#006AFF] via-[#29B2FE] to-[#006AFF] flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-6xl">
            <!-- Header -->
            <header class="mb-8 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <FreelancerLogo :show-text="true" text-color="white" />
                    <span class="text-white text-xl font-bold">Auto-Bidder</span>
                </div>
                <nav class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="px-4 py-2 text-white hover:text-blue-100 transition-colors"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                            :href="route('login')"
                            class="px-4 py-2 text-white hover:text-blue-100 transition-colors"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                            :href="route('register')"
                            class="px-4 py-2 bg-white text-[#006AFF] rounded-md hover:bg-blue-50 transition-colors font-medium"
                    >
                            Sign Up
                    </Link>
                </template>
            </nav>
        </header>

            <!-- Flash Messages -->
            <div
                v-if="flashMessage"
                class="mb-6 rounded-lg p-4 shadow-lg"
                :class="flashType === 'success' 
                    ? 'bg-green-500 text-white' 
                    : 'bg-red-500 text-white'"
            >
                <div class="flex items-center gap-2">
                    <svg v-if="flashType === 'success'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-medium">{{ flashMessage }}</span>
                </div>
            </div>

            <!-- Main Content -->
            <main class="grid lg:grid-cols-2 gap-8 items-center">
                <!-- Left Content -->
                <div class="text-white space-y-6">
                    <h1 class="text-5xl font-bold leading-tight">
                        Automate Your Freelancer Bidding
                    </h1>
                    <p class="text-xl text-blue-100 leading-relaxed">
                        Save time and win more projects with AI-powered auto-bidding. 
                        Set your preferences once and let our system bid on projects that match your criteria.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <Link
                            :href="route('freelancer.redirect')"
                            class="px-8 py-4 bg-white text-[#006AFF] rounded-lg hover:bg-blue-50 transition-colors font-semibold text-lg text-center shadow-lg"
                        >
                            Login with Freelancer
                        </Link>
                        <Link
                            :href="route('login')"
                            class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg hover:bg-white/10 transition-colors font-semibold text-lg text-center"
                        >
                            Login with Email
                        </Link>
                    </div>
                    <p class="text-sm text-blue-100 mt-2">
                        New users? Click "Login with Freelancer" to automatically create your account and get started!
                    </p>
                    <div class="pt-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-blue-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-blue-100">AI-powered project matching</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-blue-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-blue-100">Customizable bidding settings</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-blue-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                            <span class="text-blue-100">Track your bidding history</span>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Illustration -->
                <div class="hidden lg:block">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
                        <div class="space-y-4">
                            <div class="bg-white rounded-lg p-6 shadow-lg">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-12 h-12 bg-[#006AFF] rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-800">Auto-Bidding Active</div>
                                        <div class="text-sm text-gray-500">5 bids submitted today</div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Remaining Bids</span>
                                        <span class="font-semibold text-[#006AFF]">45 / 50</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-[#006AFF] h-2 rounded-full" style="width: 90%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white rounded-lg p-4 shadow-lg">
                                    <div class="text-2xl font-bold text-[#006AFF]">12</div>
                                    <div class="text-sm text-gray-600">Projects Matched</div>
                                </div>
                                <div class="bg-white rounded-lg p-4 shadow-lg">
                                    <div class="text-2xl font-bold text-[#006AFF]">8</div>
                                    <div class="text-sm text-gray-600">Bids Accepted</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
