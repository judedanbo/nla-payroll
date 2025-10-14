<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    AlertTriangle,
    Calendar,
    CheckCircle2,
    DollarSign,
    Ghost,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Staff {
    id: number;
    staff_number: string;
    full_name: string;
    station: string;
    is_ghost: boolean;
}

interface GhostDiscrepancy {
    id: number;
    staff: Staff;
    severity: string;
    severity_color: string;
    status: string;
    description: string;
    detected_at: string;
    days_open: number;
    payment_count: number;
    verification_count: number;
    last_verified: string | null;
}

interface PaginatedData {
    data: GhostDiscrepancy[];
    links: any;
    meta: any;
}

interface Stats {
    total_ghost_discrepancies: number;
    open: number;
    resolved: number;
    staff_flagged_as_ghost: number;
}

interface Props {
    discrepancies: PaginatedData;
    stats: Stats;
    filters: {
        status?: string;
        severity?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Discrepancies', href: '/discrepancies' },
    { title: 'Ghost Employees', href: '/discrepancies/ghost-employees/list' },
];

const selectedStatus = ref(props.filters.status || '');
const selectedSeverity = ref(props.filters.severity || '');

const applyFilters = () => {
    router.get(
        '/discrepancies/ghost-employees/list',
        {
            status: selectedStatus.value || undefined,
            severity: selectedSeverity.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    selectedStatus.value = '';
    selectedSeverity.value = '';
    router.get(
        '/discrepancies/ghost-employees/list',
        {},
        { preserveState: true },
    );
};

const getSeverityBadge = (severity: string) => {
    switch (severity) {
        case 'critical':
            return {
                label: 'CRITICAL',
                class: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-200',
                icon: AlertTriangle,
            };
        case 'high':
            return {
                label: 'HIGH',
                class: 'bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200',
                icon: AlertCircle,
            };
        case 'medium':
            return {
                label: 'MEDIUM',
                class: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-200',
                icon: AlertCircle,
            };
        default:
            return {
                label: severity.toUpperCase(),
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400 border-gray-200',
                icon: AlertCircle,
            };
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'open':
            return {
                label: 'Open',
                class: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400',
            };
        case 'under_review':
            return {
                label: 'Under Review',
                class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400',
            };
        case 'resolved':
            return {
                label: 'Resolved',
                class: 'bg-green-500/10 text-green-700 dark:text-green-400',
            };
        default:
            return {
                label: status,
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400',
            };
    }
};
</script>

<template>
    <Head title="Ghost Employees" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Ghost class="h-8 w-8 text-red-600" />
                        <h1 class="text-3xl font-bold tracking-tight">
                            Ghost Employee Detection
                        </h1>
                    </div>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Monitor and investigate suspected ghost employees in the
                        payroll system
                    </p>
                </div>

                <Link href="/discrepancies">
                    <Button variant="outline">All Discrepancies</Button>
                </Link>
            </div>

            <!-- Alert Banner -->
            <div
                v-if="stats.open > 0"
                class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/20"
            >
                <div class="flex items-start gap-3">
                    <AlertTriangle
                        class="mt-0.5 h-5 w-5 text-red-600 dark:text-red-500"
                    />
                    <div>
                        <h3
                            class="font-semibold text-red-900 dark:text-red-200"
                        >
                            Critical Alert: {{ stats.open }} Open Ghost Employee
                            Discrepancies
                        </h3>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                            These cases require immediate investigation. Ghost
                            employees represent potential fraud and should be
                            prioritized.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Total Cases
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total_ghost_discrepancies }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Ghost employee discrepancies
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Open Cases
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-red-600 dark:text-red-500"
                        >
                            {{ stats.open }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Require investigation
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Resolved Cases
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-green-600 dark:text-green-500"
                        >
                            {{ stats.resolved }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Successfully addressed
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Flagged Staff
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-orange-600 dark:text-orange-500"
                        >
                            {{ stats.staff_flagged_as_ghost }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Marked as ghost in system
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                    <CardDescription
                        >Filter by status or severity</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div>
                            <label for="status" class="text-sm font-medium"
                                >Status</label
                            >
                            <select
                                id="status"
                                v-model="selectedStatus"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">All Statuses</option>
                                <option value="open">Open</option>
                                <option value="under_review">
                                    Under Review
                                </option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>

                        <div>
                            <label for="severity" class="text-sm font-medium"
                                >Severity</label
                            >
                            <select
                                id="severity"
                                v-model="selectedSeverity"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">All Severities</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2 sm:col-span-2">
                            <Button @click="applyFilters" class="flex-1"
                                >Apply</Button
                            >
                            <Button @click="clearFilters" variant="outline"
                                >Clear</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Ghost Employee List -->
            <Card>
                <CardHeader>
                    <CardTitle>Ghost Employee Cases</CardTitle>
                    <CardDescription>
                        {{ discrepancies.meta?.total ?? 0 }} cases found
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="discrepancies.data.length > 0" class="space-y-3">
                        <Link
                            v-for="discrepancy in discrepancies.data"
                            :key="discrepancy.id"
                            :href="`/discrepancies/${discrepancy.id}`"
                            class="flex items-start justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <Badge
                                        :class="
                                            getSeverityBadge(
                                                discrepancy.severity,
                                            ).class
                                        "
                                        class="border"
                                    >
                                        <component
                                            :is="
                                                getSeverityBadge(
                                                    discrepancy.severity,
                                                ).icon
                                            "
                                            class="mr-1 h-3 w-3"
                                        />
                                        {{
                                            getSeverityBadge(
                                                discrepancy.severity,
                                            ).label
                                        }}
                                    </Badge>

                                    <Badge
                                        :class="
                                            getStatusBadge(discrepancy.status)
                                                .class
                                        "
                                    >
                                        {{
                                            getStatusBadge(discrepancy.status)
                                                .label
                                        }}
                                    </Badge>

                                    <Badge
                                        v-if="discrepancy.staff.is_ghost"
                                        class="border-red-200 bg-red-500/10 text-red-700 dark:text-red-400"
                                    >
                                        <Ghost class="mr-1 h-3 w-3" />
                                        FLAGGED AS GHOST
                                    </Badge>
                                </div>

                                <div class="mt-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{
                                            discrepancy.staff.full_name
                                        }}</span>
                                        <span
                                            class="text-sm text-muted-foreground"
                                        >
                                            #{{
                                                discrepancy.staff.staff_number
                                            }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ discrepancy.staff.station }}
                                    </div>
                                </div>

                                <div class="mt-2 line-clamp-2 text-sm">
                                    {{ discrepancy.description }}
                                </div>

                                <div
                                    class="mt-3 grid gap-3 text-xs sm:grid-cols-4"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-muted-foreground"
                                    >
                                        <DollarSign class="h-3.5 w-3.5" />
                                        <span>
                                            {{
                                                discrepancy.payment_count
                                            }}
                                            payments (3 months)
                                        </span>
                                    </div>

                                    <div
                                        class="flex items-center gap-1.5 text-muted-foreground"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        <span>
                                            {{
                                                discrepancy.verification_count
                                            }}
                                            verifications
                                        </span>
                                    </div>

                                    <div
                                        class="flex items-center gap-1.5 text-muted-foreground"
                                    >
                                        <Calendar class="h-3.5 w-3.5" />
                                        <span>
                                            Last verified:
                                            {{
                                                discrepancy.last_verified ||
                                                'Never'
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex items-center gap-1.5 text-muted-foreground"
                                    >
                                        <AlertCircle class="h-3.5 w-3.5" />
                                        <span
                                            >{{ discrepancy.days_open }} days
                                            open</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="py-12 text-center">
                        <CheckCircle2
                            class="mx-auto h-12 w-12 text-green-600"
                        />
                        <h3 class="mt-4 text-lg font-medium">
                            No Ghost Employee Cases Found
                        </h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            All staff members have been verified. No ghost
                            employee discrepancies detected.
                        </p>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="discrepancies.data.length > 0"
                        class="mt-6 flex items-center justify-between border-t pt-4"
                    >
                        <div class="text-sm text-muted-foreground">
                            Showing {{ discrepancies.meta?.from ?? 0 }} to
                            {{ discrepancies.meta?.to ?? 0 }} of
                            {{ discrepancies.meta?.total ?? 0 }} cases
                        </div>

                        <div class="flex gap-2">
                            <Link
                                v-for="link in discrepancies.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'inline-flex items-center rounded-md px-3 py-2 text-sm',
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'border bg-background hover:bg-muted',
                                    !link.url
                                        ? 'cursor-not-allowed opacity-50'
                                        : '',
                                ]"
                                :disabled="!link.url"
                                preserve-scroll
                            >
                                <span v-html="link.label"></span>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Information Card -->
            <Card
                class="border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/20"
            >
                <CardHeader>
                    <CardTitle class="text-blue-900 dark:text-blue-200">
                        About Ghost Employee Detection
                    </CardTitle>
                </CardHeader>
                <CardContent
                    class="space-y-2 text-sm text-blue-800 dark:text-blue-300"
                >
                    <p>
                        <strong>What is a ghost employee?</strong> A ghost
                        employee is a fictitious person on the payroll who
                        doesn't actually work for the organization but receives
                        salary payments.
                    </p>
                    <p>
                        <strong
                            >The system automatically detects ghost employees
                            by:</strong
                        >
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-1">
                        <li>
                            Identifying staff receiving payments but never
                            verified during headcount
                        </li>
                        <li>
                            Flagging staff with 3+ consecutive absences in
                            verification sessions
                        </li>
                        <li>
                            Detecting staff marked as ghost but still receiving
                            payments
                        </li>
                    </ul>
                    <p class="pt-2">
                        <strong>Action required:</strong> All ghost employee
                        cases require immediate investigation and resolution to
                        prevent fraudulent salary payments.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
