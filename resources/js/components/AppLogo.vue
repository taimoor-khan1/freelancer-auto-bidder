<script setup lang="ts">
import FreelancerLogo from '@/components/FreelancerLogo.vue';
</script>

<template>
    <div class="flex items-center gap-2">
        <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-[#006AFF]">
            <FreelancerLogo class="size-5" />
        </div>
        <div class="ml-1 grid flex-1 text-left text-sm">
            <span class="mb-0.5 truncate leading-tight font-semibold text-white dark:text-white"
                >Freelancer Auto-Bidder</span
            >
        </div>
    </div>
</template>
