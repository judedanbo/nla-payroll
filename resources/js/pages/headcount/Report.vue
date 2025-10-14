<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface Discrepancy {
    id: number;
    staff: {
        staff_number: string;
        full_name: string;
    };
    discrepancy_type: string;
    severity: 'low' | 'medium' | 'high' | 'critical';
    description: string;
    status: string;
    detected_by: string;
    detected_at: string;
}

interface Session {
    id: number;
    session_name: string;
    description: string | null;
    status: string;
    start_date: string;
    end_date: string | null;
    completion_percentage: number;
    created_by: {
        name: string;
    };
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
    discrepancies: Discrepancy[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Headcount', href: '/headcount' },
    { title: props.session.session_name, href: `/headcount/${props.session.id}` },
    { title: 'Report', href: `/headcount/${props.session.id}/report` },
];

const getSeverityBadge = (severity: string) => {
    switch (severity) {
        case 'critical':
            return {
                label: 'Critical',
                class: 'bg-red-500/10 text-red-700 dark:text-red-400 hover:bg-red-500/20',
            };
        case 'high':
            return {
                label: 'High',
                class: 'bg-orange-500/10 text-orange-700 dark:text-orange-400 hover:bg-orange-500/20',
            };
        case 'medium':
            return {
                label: 'Medium',
                class: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-500/20',
            };
        case 'low':
            return {
                label: 'Low',
                class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 hover:bg-blue-500/20',
            };
        default:
            return {
                label: severity,
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400',
            };
    }
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Report - ${session.session_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div class="flex items-center justify-between print:hidden">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Session Report</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Comprehensive report for {{ session.session_name }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button @click="printReport" variant="outline">
                        <svg
                            class="mr-2 h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                            />
                        </svg>
                        Print Report
                    </Button>

                    <Link :href="`/headcount/${session.id}`">
                        <Button variant="outline">Back to Session</Button>
                    </Link>
                </div>
            </div>

            <!-- Print Header (hidden on screen) -->
            <div class="hidden print:block">
                <div class="mb-6 border-b pb-4">
                    <h1 class="text-2xl font-bold">Headcount Session Report</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        National Lottery Authority Ghana - Forensic Audit
                    </p>
                </div>
            </div>

            <!-- Session Overview -->
            <Card>
                <CardHeader>
                    <CardTitle>Session Overview</CardTitle>
                    <CardDescription>Basic information about this headcount session</CardDescription>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">Session Name</dt>
                            <dd class="mt-1 font-medium">{{ session.session_name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm text-muted-foreground">Status</dt>
                            <dd class="mt-1 font-medium">{{ session.status }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm text-muted-foreground">Completion</dt>
                            <dd class="mt-1 font-medium">{{ session.completion_percentage }}%</dd>
                        </div>

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

                        <div v-if="session.description" class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-sm text-muted-foreground">Description</dt>
                            <dd class="mt-1">{{ session.description }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Summary Statistics -->
            <Card>
                <CardHeader>
                    <CardTitle>Summary Statistics</CardTitle>
                    <CardDescription>Overall verification results</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Total Staff -->
                        <div class="rounded-lg border p-6">
                            <div class="text-sm font-medium text-muted-foreground">Total Staff</div>
                            <div class="mt-2 text-4xl font-bold">{{ statistics.total_staff }}</div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Expected headcount
                            </p>
                        </div>

                        <!-- Verified -->
                        <div class="rounded-lg border p-6">
                            <div class="text-sm font-medium text-muted-foreground">Verified</div>
                            <div class="mt-2 text-4xl font-bold text-blue-600 dark:text-blue-400">
                                {{ statistics.verified_count }}
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ Math.round((statistics.verified_count / statistics.total_staff) * 100) }}%
                                of total
                            </p>
                        </div>

                        <!-- Completion Rate -->
                        <div class="rounded-lg border p-6">
                            <div class="text-sm font-medium text-muted-foreground">
                                Completion Rate
                            </div>
                            <div class="mt-2 text-4xl font-bold">
                                {{ session.completion_percentage }}%
                            </div>
                            <div class="mt-3 h-2 w-full bg-muted rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-blue-500 transition-all"
                                    :style="{ width: `${session.completion_percentage}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Verification Breakdown -->
            <Card>
                <CardHeader>
                    <CardTitle>Verification Breakdown</CardTitle>
                    <CardDescription>Detailed status distribution</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Present -->
                        <div class="flex items-center justify-between rounded-lg border p-6">
                            <div>
                                <div class="text-sm font-medium text-muted-foreground">Present</div>
                                <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                                    {{ statistics.present_count }}
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        Math.round(
                                            (statistics.present_count / statistics.verified_count) *
                                                100
                                        )
                                    }}%
                                    of verified
                                </p>
                            </div>
                            <div class="h-16 w-16 rounded-full bg-green-500/10 flex items-center justify-center">
                                <svg
                                    class="h-8 w-8 text-green-600 dark:text-green-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Absent -->
                        <div class="flex items-center justify-between rounded-lg border p-6">
                            <div>
                                <div class="text-sm font-medium text-muted-foreground">Absent</div>
                                <div class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                                    {{ statistics.absent_count }}
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        Math.round(
                                            (statistics.absent_count / statistics.verified_count) *
                                                100
                                        )
                                    }}%
                                    of verified
                                </p>
                            </div>
                            <div class="h-16 w-16 rounded-full bg-red-500/10 flex items-center justify-center">
                                <svg
                                    class="h-8 w-8 text-red-600 dark:text-red-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- On Leave -->
                        <div class="flex items-center justify-between rounded-lg border p-6">
                            <div>
                                <div class="text-sm font-medium text-muted-foreground">
                                    On Leave
                                </div>
                                <div class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ statistics.on_leave_count }}
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        Math.round(
                                            (statistics.on_leave_count / statistics.verified_count) *
                                                100
                                        )
                                    }}%
                                    of verified
                                </p>
                            </div>
                            <div class="h-16 w-16 rounded-full bg-blue-500/10 flex items-center justify-center">
                                <svg
                                    class="h-8 w-8 text-blue-600 dark:text-blue-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Ghost Employees -->
                        <div class="flex items-center justify-between rounded-lg border p-6 border-purple-200 dark:border-purple-900/50">
                            <div>
                                <div class="text-sm font-medium text-muted-foreground">
                                    Ghost Employees
                                </div>
                                <div class="mt-2 text-3xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ statistics.ghost_count }}
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        statistics.verified_count > 0
                                            ? Math.round(
                                                  (statistics.ghost_count /
                                                      statistics.verified_count) *
                                                      100
                                              )
                                            : 0
                                    }}%
                                    of verified
                                </p>
                            </div>
                            <div class="h-16 w-16 rounded-full bg-purple-500/10 flex items-center justify-center">
                                <svg
                                    class="h-8 w-8 text-purple-600 dark:text-purple-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Discrepancies -->
            <Card v-if="discrepancies.length > 0">
                <CardHeader>
                    <CardTitle>Discrepancies Detected</CardTitle>
                    <CardDescription>
                        {{ discrepancies.length }} discrepancies found during this session
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="discrepancy in discrepancies"
                            :key="discrepancy.id"
                            class="rounded-lg border p-4"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <Badge :class="getSeverityBadge(discrepancy.severity).class">
                                            {{ getSeverityBadge(discrepancy.severity).label }}
                                        </Badge>
                                        <span class="font-medium">{{ discrepancy.discrepancy_type }}</span>
                                    </div>

                                    <div class="mt-2">
                                        <div class="font-medium">
                                            {{ discrepancy.staff.full_name }}
                                            <span class="text-sm text-muted-foreground">
                                                ({{ discrepancy.staff.staff_number }})
                                            </span>
                                        </div>
                                        <p class="mt-1 text-sm text-muted-foreground">
                                            {{ discrepancy.description }}
                                        </p>
                                    </div>

                                    <div class="mt-3 flex items-center gap-4 text-xs text-muted-foreground">
                                        <span>
                                            Detected by {{ discrepancy.detected_by }}
                                        </span>
                                        <span>•</span>
                                        <span>{{ discrepancy.detected_at }}</span>
                                        <span>•</span>
                                        <Badge variant="outline" class="text-xs">
                                            {{ discrepancy.status }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- No Discrepancies -->
            <Card v-else>
                <CardContent class="py-12 text-center">
                    <svg
                        class="mx-auto h-12 w-12 text-green-600 dark:text-green-400"
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
                    <h3 class="mt-4 text-lg font-medium">No Discrepancies Found</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        All staff verifications completed without any issues detected.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    .print\:hidden {
        display: none !important;
    }

    .print\:block {
        display: block !important;
    }
}
</style>
