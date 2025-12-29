<template>
    <div :class="className" class="flex items-center gap-2">
        <svg
            viewBox="0 0 120 30"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            class="h-8 w-auto"
        >
            <!-- Freelancer logo - "F" letter -->
            <path
                d="M8 4H4V26H8V16H16V12H8V4Z"
                fill="#006AFF"
            />
            <path
                d="M22 4H18V26H22V4Z"
                fill="#006AFF"
            />
            <path
                d="M36 4H32V14H42V18H32V26H36V18H46V14H36V4Z"
                fill="#006AFF"
            />
            <path
                d="M60 4H56V14H66V18H56V22H66V26H56V22H46V18H56V14H46V10H60V4Z"
                fill="#006AFF"
            />
            <path
                d="M80 4H76V14H86V18H76V26H80V18H90V14H80V4Z"
                fill="#006AFF"
            />
            <path
                d="M104 4H100V14H110V18H100V22H110V26H100V22H90V18H100V14H90V10H104V4Z"
                fill="#006AFF"
            />
        </svg>
        <span v-if="showText" :class="textColorClass" class="font-bold text-xl">freelancer</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    className?: string;
    showText?: boolean;
    textColor?: string;
}>(), {
    showText: false,
    textColor: '#006AFF',
});

const textColorClass = computed(() => {
    if (props.textColor === 'white') {
        return 'text-white';
    }
    return 'text-[#006AFF]';
});
</script>
