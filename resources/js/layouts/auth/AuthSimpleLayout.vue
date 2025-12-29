<script setup lang="ts">
import FreelancerLogo from '@/components/FreelancerLogo.vue';
import { route } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-gradient-to-br from-[#006AFF] via-[#29B2FE] to-[#006AFF] p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="route('home')"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-12 w-auto items-center justify-center rounded-md bg-white p-3"
                        >
                            <FreelancerLogo :show-text="true" />
                        </div>
                        <span class="sr-only text-white">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-semibold text-white">{{ title }}</h1>
                        <p class="text-center text-sm text-blue-100">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-xl p-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
