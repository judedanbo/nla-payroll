<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    status: 'pending' | 'processing' | 'completed' | 'failed';
    totalRecords: number;
    successfulRecords: number;
    failedRecords: number;
}

const props = defineProps<Props>();

const progressPercentage = computed(() => {
    if (props.totalRecords === 0) return 0;
    const processed = props.successfulRecords + props.failedRecords;
    return Math.round((processed / props.totalRecords) * 100);
});

const successRate = computed(() => {
    const processed = props.successfulRecords + props.failedRecords;
    if (processed === 0) return 0;
    return Math.round((props.successfulRecords / processed) * 100);
});

const statusColor = computed(() => {
    switch (props.status) {
        case 'completed':
            return 'text-green-600 dark:text-green-400';
        case 'processing':
            return 'text-blue-600 dark:text-blue-400';
        case 'failed':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
});

const statusIcon = computed(() => {
    switch (props.status) {
        case 'completed':
            return 'check-circle';
        case 'processing':
            return 'spinner';
        case 'failed':
            return 'x-circle';
        default:
            return 'clock';
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Status Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <!-- Status Icon -->
                <div :class="[statusColor, 'flex items-center']">
                    <!-- Spinner for processing -->
                    <svg
                        v-if="statusIcon === 'spinner'"
                        class="animate-spin h-8 w-8"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>

                    <!-- Check Circle for completed -->
                    <svg
                        v-else-if="statusIcon === 'check-circle'"
                        class="h-8 w-8"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>

                    <!-- X Circle for failed -->
                    <svg
                        v-else-if="statusIcon === 'x-circle'"
                        class="h-8 w-8"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>

                    <!-- Clock for pending -->
                    <svg
                        v-else
                        class="h-8 w-8"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>

                <div>
                    <h3 class="text-xl font-semibold">
                        {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        {{ status === 'processing' ? 'Import in progress...' : '' }}
                        {{ status === 'completed' ? 'Import completed successfully' : '' }}
                        {{ status === 'failed' ? 'Import failed' : '' }}
                        {{ status === 'pending' ? 'Waiting to start...' : '' }}
                    </p>
                </div>
            </div>

            <div class="text-right">
                <div class="text-3xl font-bold">{{ progressPercentage }}%</div>
                <div class="text-sm text-muted-foreground">Complete</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div>
            <div class="h-3 w-full bg-muted rounded-full overflow-hidden">
                <div
                    :class="[
                        'h-full transition-all duration-300 ease-out',
                        status === 'completed' ? 'bg-green-500' : '',
                        status === 'processing' ? 'bg-blue-500' : '',
                        status === 'failed' ? 'bg-red-500' : '',
                        status === 'pending' ? 'bg-gray-400' : '',
                    ]"
                    :style="{ width: `${progressPercentage}%` }"
                />
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-3 gap-4">
            <!-- Total Records -->
            <div class="rounded-lg border bg-card p-4">
                <div class="text-2xl font-bold">
                    {{ totalRecords.toLocaleString() }}
                </div>
                <div class="text-sm text-muted-foreground">Total Records</div>
            </div>

            <!-- Successful -->
            <div class="rounded-lg border bg-card p-4">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ successfulRecords.toLocaleString() }}
                </div>
                <div class="text-sm text-muted-foreground">Successful</div>
            </div>

            <!-- Failed -->
            <div class="rounded-lg border bg-card p-4">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ failedRecords.toLocaleString() }}
                </div>
                <div class="text-sm text-muted-foreground">Failed</div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="rounded-lg border bg-card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-muted-foreground">Success Rate</div>
                    <div class="text-2xl font-bold">{{ successRate }}%</div>
                </div>

                <div class="h-16 w-16">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path
                            class="circle-bg text-muted"
                            d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="3"
                        />
                        <path
                            class="circle"
                            :stroke-dasharray="`${successRate}, 100`"
                            d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="3"
                            :class="successRate >= 90 ? 'text-green-500' : successRate >= 70 ? 'text-yellow-500' : 'text-red-500'"
                        />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Processing Message -->
        <div
            v-if="status === 'processing'"
            class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-900/20"
        >
            <div class="flex">
                <svg
                    class="h-5 w-5 text-blue-400"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                        clip-rule="evenodd"
                    />
                </svg>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        This import is running in the background. You can safely leave this page.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.circular-chart {
    display: block;
    margin: 0 auto;
    max-width: 100%;
    max-height: 100%;
}

.circle {
    stroke-linecap: round;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
    transition: stroke-dasharray 0.3s ease;
}
</style>
