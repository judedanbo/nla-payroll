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
import { ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Station {
    id: number;
    name: string;
    code: string;
    city: string;
    region: string;
}

interface Session {
    id: number;
    session_name: string;
}

interface Assignment {
    id: number;
    user: User;
    station: Station;
    session: Session | null;
    start_date: string;
    end_date: string | null;
    status: 'active' | 'pending' | 'completed' | 'cancelled';
    assigned_by: {
        name: string;
    };
    is_active: boolean;
    can_cancel: boolean;
}

interface PaginatedData {
    data: Assignment[];
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

interface Props {
    assignments: PaginatedData;
    users: Array<{ id: number; name: string }>;
    stations: Array<{ id: number; name: string; code: string }>;
    filters: {
        status?: string;
        user_id?: number;
        station_id?: number;
        session_id?: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Team Assignments', href: '/assignments' },
];

const selectedStatus = ref(props.filters.status || '');
const selectedUser = ref(props.filters.user_id?.toString() || '');
const selectedStation = ref(props.filters.station_id?.toString() || '');

const applyFilters = () => {
    router.get(
        '/assignments',
        {
            status: selectedStatus.value || undefined,
            user_id: selectedUser.value || undefined,
            station_id: selectedStation.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    selectedStatus.value = '';
    selectedUser.value = '';
    selectedStation.value = '';
    router.get('/assignments', {}, { preserveState: true });
};

const cancelAssignment = (assignmentId: number) => {
    if (!confirm('Are you sure you want to cancel this assignment?')) {
        return;
    }

    router.delete(`/assignments/${assignmentId}`, {
        preserveScroll: true,
    });
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active':
            return {
                label: 'Active',
                class: 'bg-green-500/10 text-green-700 dark:text-green-400 hover:bg-green-500/20',
            };
        case 'pending':
            return {
                label: 'Pending',
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400 hover:bg-gray-500/20',
            };
        case 'completed':
            return {
                label: 'Completed',
                class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 hover:bg-blue-500/20',
            };
        case 'cancelled':
            return {
                label: 'Cancelled',
                class: 'bg-red-500/10 text-red-700 dark:text-red-400 hover:bg-red-500/20',
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
    <Head title="Team Assignments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Team Assignments
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Manage field auditor assignments to stations
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link href="/assignments/coverage">
                        <Button variant="outline">View Coverage</Button>
                    </Link>

                    <Link href="/assignments/create">
                        <Button>New Assignment</Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                    <CardDescription
                        >Filter assignments by status, user, or
                        station</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-4">
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
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- User Filter -->
                        <div>
                            <label for="user" class="text-sm font-medium"
                                >User</label
                            >
                            <select
                                id="user"
                                v-model="selectedUser"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">All Users</option>
                                <option
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Station Filter -->
                        <div>
                            <label for="station" class="text-sm font-medium"
                                >Station</label
                            >
                            <select
                                id="station"
                                v-model="selectedStation"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">All Stations</option>
                                <option
                                    v-for="station in stations"
                                    :key="station.id"
                                    :value="station.id"
                                >
                                    {{ station.name }} ({{ station.code }})
                                </option>
                            </select>
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

            <!-- Assignments List -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>All Assignments</CardTitle>
                            <CardDescription>
                                {{ assignments.meta.total }} total assignments
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="assignments.data.length > 0" class="space-y-3">
                        <div
                            v-for="assignment in assignments.data"
                            :key="assignment.id"
                            class="flex items-center justify-between rounded-lg border p-4 hover:bg-muted/50"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <Badge
                                        :class="
                                            getStatusBadge(assignment.status)
                                                .class
                                        "
                                    >
                                        {{
                                            getStatusBadge(assignment.status)
                                                .label
                                        }}
                                    </Badge>

                                    <div>
                                        <div class="font-medium">
                                            {{ assignment.user.name }}
                                        </div>
                                        <div
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ assignment.user.email }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                    <div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            Station
                                        </div>
                                        <div class="text-sm font-medium">
                                            {{ assignment.station.name }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ assignment.station.code }} •
                                            {{ assignment.station.city }},
                                            {{ assignment.station.region }}
                                        </div>
                                    </div>

                                    <div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            Duration
                                        </div>
                                        <div class="text-sm font-medium">
                                            {{ assignment.start_date }}
                                            <span v-if="assignment.end_date">
                                                → {{ assignment.end_date }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                            >
                                                (No end date)
                                            </span>
                                        </div>
                                    </div>

                                    <div v-if="assignment.session">
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            Session
                                        </div>
                                        <div class="text-sm font-medium">
                                            {{
                                                assignment.session.session_name
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 text-xs text-muted-foreground">
                                    Assigned by
                                    {{ assignment.assigned_by.name }}
                                </div>
                            </div>

                            <div class="ml-4 flex items-center gap-2">
                                <Button
                                    v-if="assignment.can_cancel"
                                    @click="cancelAssignment(assignment.id)"
                                    variant="outline"
                                    size="sm"
                                >
                                    Cancel
                                </Button>
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
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium">
                            No assignments found
                        </h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Get started by creating a new team assignment.
                        </p>
                        <Link
                            href="/assignments/create"
                            class="mt-4 inline-block"
                        >
                            <Button>Create Assignment</Button>
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="assignments.data.length > 0"
                        class="mt-6 flex items-center justify-between border-t pt-4"
                    >
                        <div class="text-sm text-muted-foreground">
                            Showing {{ assignments?.from }} to
                            {{ assignments?.to }} of
                            {{ assignments?.total }} assignments
                        </div>

                        <div class="flex gap-2">
                            <Link
                                v-for="link in assignments.links"
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
        </div>
    </AppLayout>
</template>
