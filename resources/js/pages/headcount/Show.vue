<script setup lang="ts">
import HeadcountController from '@/actions/App/Http/Controllers/HeadcountController';
import AppLayout from '@/layouts/AppLayout.vue';
import VerificationStatusBadge from '@/components/headcount/VerificationStatusBadge.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Verification {
    id: number;
    staff: {
        id: number;
        staff_number: string;
        full_name: string;
        job_title: string;
        department: string;
    };
    verification_status: 'present' | 'absent' | 'on_leave' | 'ghost';
    location: string | null;
    verified_at: string;
    verified_by: {
        id: number;
        name: string;
    };
    has_photos: boolean;
    notes_count: number;
}

interface Session {
    id: number;
    session_name: string;
    description: string | null;
    status: 'pending' | 'in_progress' | 'paused' | 'completed';
    start_date: string;
    end_date: string | null;
    completion_percentage: number;
    created_at: string;
    created_by: {
        id: number;
        name: string;
    };
    can_start: boolean;
    can_end: boolean;
    is_active: boolean;
}

interface Statistics {
    total_staff: number;
    verified_count: number;
    present_count: number;
    absent_count: number;
    on_leave_count: number;
    ghost_count: number;
}

interface Props {
    session: Session;
    statistics: Statistics;
    verifications: Verification[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Headcount', href: '/headcount' },
    { title: props.session.session_name, href: `/headcount/${props.session.id}` },
];

const isStarting = ref(false);
const isEnding = ref(false);
const isPausing = ref(false);

const startSession = () => {
    isStarting.value = true;
    router.post(
        HeadcountController.startSession({ session: props.session.id }).url,
        {},
        {
            onFinish: () => {
                isStarting.value = false;
            },
        }
    );
};

const endSession = () => {
    if (!confirm('Are you sure you want to end this session? This action cannot be undone.')) {
        return;
    }

    isEnding.value = true;
    router.post(
        HeadcountController.endSession({ session: props.session.id }).url,
        {},
        {
            onFinish: () => {
                isEnding.value = false;
            },
        }
    );
};

const pauseSession = () => {
    isPausing.value = true;
    router.post(
        HeadcountController.pauseSession({ session: props.session.id }).url,
        {},
        {
            onFinish: () => {
                isPausing.value = false;
            },
        }
    );
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'pending':
            return {
                label: 'Pending',
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400 hover:bg-gray-500/20',
            };
        case 'in_progress':
            return {
                label: 'In Progress',
                class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 hover:bg-blue-500/20',
            };
        case 'paused':
            return {
                label: 'Paused',
                class: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-500/20',
            };
        case 'completed':
            return {
                label: 'Completed',
                class: 'bg-green-500/10 text-green-700 dark:text-green-400 hover:bg-green-500/20',
            };
        default:
            return {
                label: status,
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400',
            };
    }
};

const statusBadge = getStatusBadge(props.session.status);
</script>

<template>
    <Head :title="`${session.session_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ session.session_name }}
                        </h1>
                        <Badge :class="statusBadge.class">
                            {{ statusBadge.label }}
                        </Badge>
                    </div>
                    <p v-if="session.description" class="mt-2 text-sm text-muted-foreground">
                        {{ session.description }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Created by {{ session.created_by.name }} on {{ session.created_at }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link
                        v-if="session.is_active"
                        :href="HeadcountController.verificationForm({ session: session.id }).url"
                    >
                        <Button>Verify Staff</Button>
                    </Link>

                    <Button
                        v-if="session.can_start"
                        @click="startSession"
                        :disabled="isStarting"
                    >
                        {{ isStarting ? 'Starting...' : 'Start Session' }}
                    </Button>

                    <Button
                        v-if="session.is_active"
                        @click="pauseSession"
                        variant="outline"
                        :disabled="isPausing"
                    >
                        {{ isPausing ? 'Pausing...' : 'Pause' }}
                    </Button>

                    <Button
                        v-if="session.can_end"
                        @click="endSession"
                        variant="destructive"
                        :disabled="isEnding"
                    >
                        {{ isEnding ? 'Ending...' : 'End Session' }}
                    </Button>

                    <Link
                        v-if="session.status === 'completed'"
                        :href="HeadcountController.sessionReport({ session: session.id }).url"
                    >
                        <Button variant="outline">View Report</Button>
                    </Link>
                </div>
            </div>

            <!-- Active Session Alert -->
            <Alert v-if="session.is_active" class="border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-900/20">
                <AlertDescription class="text-blue-800 dark:text-blue-200">
                    This session is currently active. You can verify staff attendance now.
                </AlertDescription>
            </Alert>

            <!-- Progress Overview -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Completion Percentage -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Completion</CardDescription>
                        <CardTitle class="text-3xl">{{ session.completion_percentage }}%</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-2 w-full bg-muted rounded-full overflow-hidden">
                            <div
                                class="h-full bg-blue-500 transition-all duration-300"
                                :style="{ width: `${session.completion_percentage}%` }"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Verified -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Verified</CardDescription>
                        <CardTitle class="text-3xl">
                            {{ statistics.verified_count }}/{{ statistics.total_staff }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Staff members verified
                        </p>
                    </CardContent>
                </Card>

                <!-- Present -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Present</CardDescription>
                        <CardTitle class="text-3xl text-green-600 dark:text-green-400">
                            {{ statistics.present_count }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Staff present at work
                        </p>
                    </CardContent>
                </Card>

                <!-- Issues -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Issues</CardDescription>
                        <CardTitle class="text-3xl text-red-600 dark:text-red-400">
                            {{ statistics.absent_count + statistics.ghost_count }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            {{ statistics.absent_count }} absent, {{ statistics.ghost_count }} ghost
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Detailed Statistics -->
            <Card>
                <CardHeader>
                    <CardTitle>Verification Breakdown</CardTitle>
                    <CardDescription>Detailed statistics for this session</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ statistics.present_count }}
                                </div>
                                <div class="text-sm text-muted-foreground">Present</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                    {{ statistics.absent_count }}
                                </div>
                                <div class="text-sm text-muted-foreground">Absent</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ statistics.on_leave_count }}
                                </div>
                                <div class="text-sm text-muted-foreground">On Leave</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ statistics.ghost_count }}
                                </div>
                                <div class="text-sm text-muted-foreground">Ghost Employees</div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Verifications -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Recent Verifications</CardTitle>
                            <CardDescription>
                                Last {{ verifications.length }} staff verifications
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="verifications.length > 0" class="space-y-3">
                        <div
                            v-for="verification in verifications"
                            :key="verification.id"
                            class="flex items-center justify-between rounded-lg border p-4 hover:bg-muted/50"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <div>
                                        <div class="font-medium">
                                            {{ verification.staff.full_name }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ verification.staff.staff_number }} •
                                            {{ verification.staff.job_title }} •
                                            {{ verification.staff.department }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <VerificationStatusBadge :status="verification.verification_status" />

                                <div class="text-right">
                                    <div class="text-sm font-medium">
                                        {{ verification.verified_by.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ verification.verified_at }}
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <Badge v-if="verification.has_photos" variant="outline">
                                        Photo
                                    </Badge>
                                    <Badge v-if="verification.notes_count > 0" variant="outline">
                                        {{ verification.notes_count }} Notes
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-12 text-center">
                        <svg
                            class="mx-auto h-12 w-12 text-muted-foreground"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium">No verifications yet</h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Start verifying staff to see them appear here.
                        </p>
                        <Link
                            v-if="session.is_active"
                            :href="HeadcountController.verificationForm({ session: session.id }).url"
                            class="mt-4 inline-block"
                        >
                            <Button>Start Verifying</Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Session Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Session Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">Start Date</dt>
                            <dd class="mt-1 font-medium">{{ session.start_date }}</dd>
                        </div>

                        <div v-if="session.end_date">
                            <dt class="text-sm text-muted-foreground">End Date</dt>
                            <dd class="mt-1 font-medium">{{ session.end_date }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm text-muted-foreground">Created By</dt>
                            <dd class="mt-1 font-medium">{{ session.created_by.name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm text-muted-foreground">Created At</dt>
                            <dd class="mt-1 font-medium">{{ session.created_at }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
