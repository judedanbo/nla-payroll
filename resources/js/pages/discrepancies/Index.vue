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
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Clock, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface Staff {
    id: number;
    staff_number: string;
    full_name: string;
}

interface Station {
    id: number;
    name: string;
    code: string;
}

interface Discrepancy {
    id: number;
    staff: Staff | null;
    station: Station | null;
    discrepancy_type: string;
    type_label: string;
    severity: string;
    severity_color: string;
    status: string;
    description: string;
    detected_at: string;
    days_open: number;
    is_overdue: boolean;
    can_resolve: boolean;
}

interface PaginatedData {
    data: Discrepancy[];
    links: any;
    current_page: number;
    from: number;
    to: number;
    per_page: number;
    total: number;
    last_page: number;
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
}

interface FilterOption {
    value: string;
    label: string;
}

interface Stats {
    total: number;
    open: number;
    under_review: number;
    resolved: number;
    critical: number;
}

interface Props {
    discrepancies: PaginatedData;
    statuses: FilterOption[];
    types: FilterOption[];
    severities: FilterOption[];
    stats: Stats;
    filters: {
        status?: string;
        type?: string;
        severity?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Discrepancies', href: '/discrepancies' },
];

const selectedStatus = ref(props.filters.status || '');
const selectedType = ref(props.filters.type || '');
const selectedSeverity = ref(props.filters.severity || '');
const searchQuery = ref(props.filters.search || '');

const applyFilters = () => {
    router.get(
        '/discrepancies',
        {
            status: selectedStatus.value || undefined,
            type: selectedType.value || undefined,
            severity: selectedSeverity.value || undefined,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    selectedStatus.value = '';
    selectedType.value = '';
    selectedSeverity.value = '';
    searchQuery.value = '';
    router.get('/discrepancies', {}, { preserveState: true });
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'open':
            return {
                label: 'Open',
                class: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-500/20',
                icon: AlertCircle,
            };
        case 'under_review':
            return {
                label: 'Under Review',
                class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 hover:bg-blue-500/20',
                icon: Clock,
            };
        case 'resolved':
            return {
                label: 'Resolved',
                class: 'bg-green-500/10 text-green-700 dark:text-green-400 hover:bg-green-500/20',
                icon: CheckCircle2,
            };
        case 'dismissed':
            return {
                label: 'Dismissed',
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400 hover:bg-gray-500/20',
                icon: XCircle,
            };
        default:
            return {
                label: status,
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400',
                icon: AlertCircle,
            };
    }
};

const getSeverityBadge = (severity: string) => {
    switch (severity) {
        case 'critical':
            return 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900';
        case 'high':
            return 'bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-900';
        case 'medium':
            return 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-900';
        case 'low':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900';
        default:
            return 'bg-gray-500/10 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-900';
    }
};
</script>

<template>
    <Head title="Discrepancies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Discrepancy Management
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Track and resolve payroll and headcount discrepancies
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link href="/discrepancies/ghost-employees/list">
                        <Button variant="outline">Ghost Employees</Button>
                    </Link>

                    <Link href="/discrepancies/create">
                        <Button>Create Discrepancy</Button>
                    </Link>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-5">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Total
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Open
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-yellow-600 dark:text-yellow-500"
                        >
                            {{ stats.open }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Under Review
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-blue-600 dark:text-blue-500"
                        >
                            {{ stats.under_review }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Resolved
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-green-600 dark:text-green-500"
                        >
                            {{ stats.resolved }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Critical
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-red-600 dark:text-red-500"
                        >
                            {{ stats.critical }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                    <CardDescription>
                        Filter discrepancies by status, type, severity, or
                        search
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-5">
                        <!-- Status Filter -->
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
                                <option
                                    v-for="status in statuses"
                                    :key="status.value"
                                    :value="status.value"
                                >
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label for="type" class="text-sm font-medium"
                                >Type</label
                            >
                            <select
                                id="type"
                                v-model="selectedType"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">All Types</option>
                                <option
                                    v-for="type in types"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Severity Filter -->
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
                                <option
                                    v-for="severity in severities"
                                    :key="severity.value"
                                    :value="severity.value"
                                >
                                    {{ severity.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Search -->
                        <div>
                            <label for="search" class="text-sm font-medium"
                                >Search</label
                            >
                            <Input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Staff name or number"
                                class="mt-1"
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-end gap-2">
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

            <!-- Discrepancies List -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>All Discrepancies</CardTitle>
                            <CardDescription>
                                {{ discrepancies?.total ?? 0 }} total
                                discrepancies
                            </CardDescription>
                        </div>
                    </div>
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
                                            )
                                        "
                                        class="border"
                                    >
                                        {{ discrepancy.severity.toUpperCase() }}
                                    </Badge>

                                    <Badge
                                        :class="
                                            getStatusBadge(discrepancy.status)
                                                .class
                                        "
                                    >
                                        <component
                                            :is="
                                                getStatusBadge(
                                                    discrepancy.status,
                                                ).icon
                                            "
                                            class="mr-1 h-3 w-3"
                                        />
                                        {{
                                            getStatusBadge(discrepancy.status)
                                                .label
                                        }}
                                    </Badge>

                                    <Badge variant="outline">
                                        {{ discrepancy.type_label }}
                                    </Badge>

                                    <Badge
                                        v-if="discrepancy.is_overdue"
                                        class="border-red-200 bg-red-500/10 text-red-700 dark:border-red-900 dark:text-red-400"
                                    >
                                        OVERDUE
                                    </Badge>
                                </div>

                                <div v-if="discrepancy.staff" class="mt-3">
                                    <div class="font-medium">
                                        {{ discrepancy.staff.full_name }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        Staff #
                                        {{ discrepancy.staff.staff_number }}
                                        <span v-if="discrepancy.station">
                                            • {{ discrepancy.station.name }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="mt-2 line-clamp-2 text-sm text-foreground"
                                >
                                    {{ discrepancy.description }}
                                </div>

                                <div
                                    class="mt-2 flex items-center gap-4 text-xs text-muted-foreground"
                                >
                                    <span
                                        >Detected
                                        {{ discrepancy.detected_at }}</span
                                    >
                                    <span
                                        >{{ discrepancy.days_open }} days
                                        open</span
                                    >
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="py-12 text-center">
                        <AlertCircle
                            class="mx-auto h-12 w-12 text-muted-foreground"
                        />
                        <h3 class="mt-4 text-lg font-medium">
                            No discrepancies found
                        </h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Try adjusting your filters or create a new
                            discrepancy manually.
                        </p>
                        <Link
                            href="/discrepancies/create"
                            class="mt-4 inline-block"
                        >
                            <Button>Create Discrepancy</Button>
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="discrepancies.data.length > 0"
                        class="mt-6 flex items-center justify-between border-t pt-4"
                    >
                        <div class="text-sm text-muted-foreground">
                            Showing {{ discrepancies?.from ?? 0 }} to
                            {{ discrepancies?.to ?? 0 }} of
                            {{ discrepancies?.total ?? 0 }} discrepancies
                        </div>

                        <div class="flex gap-2">
                            <Link
                                v-for="link in discrepancies.links"
                                :key="link.label"
                                :href="link.url ?? '#'"
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
        </div>
    </AppLayout>
</template>
