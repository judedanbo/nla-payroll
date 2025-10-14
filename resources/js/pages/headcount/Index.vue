<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    Calendar,
    CheckCircle2,
    Plus,
    User,
    Users,
} from 'lucide-vue-next';

interface Session {
    id: number;
    session_name: string;
    description: string | null;
    status: 'planned' | 'in_progress' | 'cancelled' | 'completed';
    start_date: string;
    end_date: string | null;
    completion_percentage: number;
    verification_stats: {
        total_staff: number;
        verified_count: number;
        present_count: number;
        absent_count: number;
        on_leave_count: number;
        ghost_count: number;
    };
    created_at: string;
    created_by: {
        id: number;
        name: string;
    };
    can_start: boolean;
    can_end: boolean;
}

interface Statistics {
    total_sessions: number;
    active_sessions: number;
    total_verifications: number;
    ghost_employees_detected: number;
}

interface Props {
    sessions: {
        data: Session[];
        links: any[];
        meta: any;
    };
    statistics: Statistics;
    filters: {
        status?: string;
        created_by?: number;
        date_from?: string;
        date_to?: string;
    };
}

defineProps<Props>();

const getStatusBadge = (status: Session['status']) => {
    const configs = {
        planned: {
            label: 'Planned',
            class: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400',
        },
        in_progress: {
            label: 'In Progress',
            class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400',
        },
        cancelled: {
            label: 'Cancelled',
            class: 'bg-orange-500/10 text-orange-700 dark:text-orange-400',
        },
        completed: {
            label: 'Completed',
            class: 'bg-green-500/10 text-green-700 dark:text-green-400',
        },
    };
    return configs[status];
};

const handleStartSession = (sessionId: number) => {
    router.post(
        `/headcount/${sessionId}/start`,
        {},
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head title="Headcount Sessions" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Headcount Management
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        Manage and track staff verification sessions
                    </p>
                </div>
                <Link href="/headcount/create">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        New Session
                    </Button>
                </Link>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Sessions</CardTitle
                        >
                        <Calendar class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ statistics.total_sessions }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Active Sessions</CardTitle
                        >
                        <Activity class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ statistics.active_sessions }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Verifications</CardTitle
                        >
                        <CheckCircle2 class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ statistics.total_verifications }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Ghost Employees</CardTitle
                        >
                        <AlertTriangle class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-destructive">
                            {{ statistics.ghost_employees_detected }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sessions List -->
            <Card>
                <CardHeader>
                    <CardTitle>Headcount Sessions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="session in sessions.data"
                            :key="session.id"
                            class="flex flex-col gap-4 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="`/headcount/${session.id}`"
                                        class="font-semibold hover:underline"
                                    >
                                        {{ session.session_name }}
                                    </Link>
                                    <Badge
                                        :class="
                                            getStatusBadge(session.status).class
                                        "
                                    >
                                        {{
                                            getStatusBadge(session.status).label
                                        }}
                                    </Badge>
                                </div>
                                <p
                                    v-if="session.description"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ session.description }}
                                </p>
                                <div
                                    class="flex flex-wrap gap-4 text-sm text-muted-foreground"
                                >
                                    <span class="flex items-center gap-1">
                                        <Calendar class="size-3" />
                                        {{ session.start_date }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <User class="size-3" />
                                        {{ session.created_by.name }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Users class="size-3" />
                                        {{
                                            session.verification_stats
                                                .verified_count
                                        }}
                                        verified
                                    </span>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <Button
                                    v-if="session.can_start"
                                    @click="handleStartSession(session.id)"
                                    size="sm"
                                    variant="default"
                                >
                                    Start Session
                                </Button>
                                <Link :href="`/headcount/${session.id}/verify`">
                                    <Button
                                        v-if="session.status === 'in_progress'"
                                        size="sm"
                                        variant="default"
                                    >
                                        Verify Staff
                                    </Button>
                                </Link>
                                <Link :href="`/headcount/${session.id}`">
                                    <Button size="sm" variant="outline">
                                        View Details
                                    </Button>
                                </Link>
                            </div>
                        </div>

                        <div
                            v-if="sessions.data.length === 0"
                            class="py-12 text-center"
                        >
                            <Users
                                class="mx-auto size-12 text-muted-foreground/20"
                            />
                            <h3 class="mt-4 text-lg font-semibold">
                                No headcount sessions yet
                            </h3>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Get started by creating your first headcount
                                session.
                            </p>
                            <Link
                                href="/headcount/create"
                                class="mt-4 inline-block"
                            >
                                <Button>
                                    <Plus class="mr-2 size-4" />
                                    Create Session
                                </Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
