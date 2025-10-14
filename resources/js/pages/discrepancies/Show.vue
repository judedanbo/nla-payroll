<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { AlertCircle, CheckCircle2, Clock, User, Building2, Calendar, MessageSquare } from 'lucide-vue-next';

interface Staff {
    id: number;
    staff_number: string;
    full_name: string;
    email: string;
    phone: string;
    current_salary: string;
    job_title: string;
    department: string;
    station: string;
    employment_status: string;
    is_ghost: boolean;
}

interface Station {
    id: number;
    name: string;
    code: string;
    region: string;
}

interface Note {
    id: number;
    content: string;
    is_internal: boolean;
    created_by: string;
    created_at: string;
}

interface Resolution {
    resolution_type: string;
    notes: string;
    outcome: string;
    resolved_by: string;
    resolved_at: string;
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
    detected_by: { id: number; name: string } | { name: string };
    detected_at: string;
    days_open: number;
    notes: Note[];
    resolution: Resolution | null;
    can_edit: boolean;
    can_resolve: boolean;
    can_dismiss: boolean;
}

interface Props {
    discrepancy: Discrepancy;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Discrepancies', href: '/discrepancies' },
    { title: `#${props.discrepancy.id}`, href: `/discrepancies/${props.discrepancy.id}` },
];

const noteForm = useForm({
    content: '',
    is_internal: false,
});

const resolveForm = useForm({
    resolution_type: 'correction',
    notes: '',
    outcome: 'resolved',
});

const dismissForm = useForm({
    reason: '',
});

const showResolveDialog = ref(false);
const showDismissDialog = ref(false);
const showNoteDialog = ref(false);

const markUnderReview = () => {
    if (!confirm('Mark this discrepancy as under review?')) return;

    router.post(`/discrepancies/${props.discrepancy.id}/under-review`, {}, {
        preserveScroll: true,
    });
};

const submitNote = () => {
    noteForm.post(`/discrepancies/${props.discrepancy.id}/notes`, {
        preserveScroll: true,
        onSuccess: () => {
            noteForm.reset();
            showNoteDialog.value = false;
        },
    });
};

const submitResolve = () => {
    resolveForm.post(`/discrepancies/${props.discrepancy.id}/resolve`, {
        preserveScroll: true,
        onSuccess: () => {
            resolveForm.reset();
            showResolveDialog.value = false;
        },
    });
};

const submitDismiss = () => {
    dismissForm.post(`/discrepancies/${props.discrepancy.id}/dismiss`, {
        preserveScroll: true,
        onSuccess: () => {
            dismissForm.reset();
            showDismissDialog.value = false;
        },
    });
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'open':
            return {
                label: 'Open',
                class: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400',
                icon: AlertCircle,
            };
        case 'under_review':
            return {
                label: 'Under Review',
                class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400',
                icon: Clock,
            };
        case 'resolved':
            return {
                label: 'Resolved',
                class: 'bg-green-500/10 text-green-700 dark:text-green-400',
                icon: CheckCircle2,
            };
        case 'dismissed':
            return {
                label: 'Dismissed',
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400',
                icon: CheckCircle2,
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
            return 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-200';
        case 'high':
            return 'bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200';
        case 'medium':
            return 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-200';
        case 'low':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200';
        default:
            return 'bg-gray-500/10 text-gray-700 dark:text-gray-400 border-gray-200';
    }
};
</script>

<template>
    <Head :title="`Discrepancy #${discrepancy.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Discrepancy #{{ discrepancy.id }}</h1>
                    <div class="mt-3 flex items-center gap-3 flex-wrap">
                        <Badge :class="getSeverityBadge(discrepancy.severity)" class="border">
                            {{ discrepancy.severity.toUpperCase() }}
                        </Badge>
                        <Badge :class="getStatusBadge(discrepancy.status).class">
                            <component :is="getStatusBadge(discrepancy.status).icon" class="mr-1 h-3 w-3" />
                            {{ getStatusBadge(discrepancy.status).label }}
                        </Badge>
                        <Badge variant="outline">
                            {{ discrepancy.type_label }}
                        </Badge>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button
                        v-if="discrepancy.can_resolve && discrepancy.status === 'open'"
                        @click="markUnderReview"
                        variant="outline"
                    >
                        Mark Under Review
                    </Button>

                    <Dialog v-model:open="showResolveDialog">
                        <DialogTrigger as-child>
                            <Button v-if="discrepancy.can_resolve" variant="default">
                                Resolve
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Resolve Discrepancy</DialogTitle>
                                <DialogDescription>
                                    Provide resolution details for this discrepancy
                                </DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="submitResolve" class="space-y-4">
                                <div>
                                    <Label for="resolution_type">Resolution Type</Label>
                                    <select
                                        id="resolution_type"
                                        v-model="resolveForm.resolution_type"
                                        class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background"
                                    >
                                        <option value="correction">Correction</option>
                                        <option value="verification">Verification</option>
                                        <option value="dismissal">Dismissal</option>
                                        <option value="escalation">Escalation</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div>
                                    <Label for="notes">Resolution Notes</Label>
                                    <Textarea
                                        id="notes"
                                        v-model="resolveForm.notes"
                                        placeholder="Describe the resolution..."
                                        rows="4"
                                    />
                                    <div v-if="resolveForm.errors.notes" class="text-sm text-red-600 mt-1">
                                        {{ resolveForm.errors.notes }}
                                    </div>
                                </div>

                                <div>
                                    <Label for="outcome">Outcome</Label>
                                    <select
                                        id="outcome"
                                        v-model="resolveForm.outcome"
                                        class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background"
                                    >
                                        <option value="resolved">Resolved</option>
                                        <option value="unresolved">Unresolved</option>
                                    </select>
                                </div>

                                <div class="flex gap-2">
                                    <Button type="submit" :disabled="resolveForm.processing">
                                        Submit Resolution
                                    </Button>
                                    <Button type="button" variant="outline" @click="showResolveDialog = false">
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Dialog v-model:open="showDismissDialog">
                        <DialogTrigger as-child>
                            <Button v-if="discrepancy.can_dismiss" variant="outline">
                                Dismiss
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Dismiss Discrepancy</DialogTitle>
                                <DialogDescription>
                                    Provide a reason for dismissing this discrepancy
                                </DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="submitDismiss" class="space-y-4">
                                <div>
                                    <Label for="reason">Reason (Optional)</Label>
                                    <Textarea
                                        id="reason"
                                        v-model="dismissForm.reason"
                                        placeholder="Why is this discrepancy being dismissed?"
                                        rows="3"
                                    />
                                </div>

                                <div class="flex gap-2">
                                    <Button type="submit" variant="destructive" :disabled="dismissForm.processing">
                                        Dismiss Discrepancy
                                    </Button>
                                    <Button type="button" variant="outline" @click="showDismissDialog = false">
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm whitespace-pre-wrap">{{ discrepancy.description }}</p>
                        </CardContent>
                    </Card>

                    <!-- Staff Information -->
                    <Card v-if="discrepancy.staff">
                        <CardHeader>
                            <CardTitle>Staff Information</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Name</div>
                                    <div class="mt-1 text-sm">{{ discrepancy.staff.full_name }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Staff Number</div>
                                    <div class="mt-1 text-sm">{{ discrepancy.staff.staff_number }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Email</div>
                                    <div class="mt-1 text-sm">{{ discrepancy.staff.email }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Phone</div>
                                    <div class="mt-1 text-sm">{{ discrepancy.staff.phone }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Job Title</div>
                                    <div class="mt-1 text-sm">{{ discrepancy.staff.job_title }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Department</div>
                                    <div class="mt-1 text-sm">{{ discrepancy.staff.department }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Station</div>
                                    <div class="mt-1 text-sm">{{ discrepancy.staff.station }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Status</div>
                                    <div class="mt-1">
                                        <Badge
                                            v-if="discrepancy.staff.is_ghost"
                                            class="bg-red-500/10 text-red-700 dark:text-red-400"
                                        >
                                            GHOST
                                        </Badge>
                                        <span v-else class="text-sm">{{ discrepancy.staff.employment_status }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Resolution (if resolved) -->
                    <Card v-if="discrepancy.resolution">
                        <CardHeader>
                            <CardTitle>Resolution</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Resolution Type</div>
                                    <div class="mt-1 text-sm capitalize">
                                        {{ discrepancy.resolution.resolution_type }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Notes</div>
                                    <div class="mt-1 text-sm whitespace-pre-wrap">
                                        {{ discrepancy.resolution.notes }}
                                    </div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <div class="text-sm font-medium text-muted-foreground">Outcome</div>
                                        <div class="mt-1 text-sm capitalize">
                                            {{ discrepancy.resolution.outcome }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-muted-foreground">Resolved By</div>
                                        <div class="mt-1 text-sm">{{ discrepancy.resolution.resolved_by }}</div>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-muted-foreground">Resolved At</div>
                                    <div class="mt-1 text-sm">{{ discrepancy.resolution.resolved_at }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notes -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle>Notes</CardTitle>
                                <Dialog v-model:open="showNoteDialog">
                                    <DialogTrigger as-child>
                                        <Button size="sm">Add Note</Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Add Note</DialogTitle>
                                            <DialogDescription>
                                                Add a note to track investigation progress
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form @submit.prevent="submitNote" class="space-y-4">
                                            <div>
                                                <Label for="content">Note Content</Label>
                                                <Textarea
                                                    id="content"
                                                    v-model="noteForm.content"
                                                    placeholder="Enter your note..."
                                                    rows="4"
                                                />
                                                <div v-if="noteForm.errors.content" class="text-sm text-red-600 mt-1">
                                                    {{ noteForm.errors.content }}
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <input
                                                    id="is_internal"
                                                    v-model="noteForm.is_internal"
                                                    type="checkbox"
                                                    class="rounded border-input"
                                                />
                                                <Label for="is_internal" class="font-normal">
                                                    Internal note (not visible to staff)
                                                </Label>
                                            </div>

                                            <div class="flex gap-2">
                                                <Button type="submit" :disabled="noteForm.processing">
                                                    Add Note
                                                </Button>
                                                <Button type="button" variant="outline" @click="showNoteDialog = false">
                                                    Cancel
                                                </Button>
                                            </div>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="discrepancy.notes.length > 0" class="space-y-4">
                                <div
                                    v-for="note in discrepancy.notes"
                                    :key="note.id"
                                    class="rounded-lg border p-4"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <MessageSquare class="h-4 w-4 text-muted-foreground" />
                                            <span class="text-sm font-medium">{{ note.created_by }}</span>
                                            <Badge v-if="note.is_internal" variant="outline" class="text-xs">
                                                Internal
                                            </Badge>
                                        </div>
                                        <span class="text-xs text-muted-foreground">{{ note.created_at }}</span>
                                    </div>
                                    <p class="mt-2 text-sm whitespace-pre-wrap">{{ note.content }}</p>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center text-sm text-muted-foreground">
                                No notes yet. Add a note to track investigation progress.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                    <Calendar class="h-4 w-4" />
                                    Detected At
                                </div>
                                <div class="mt-1 text-sm">{{ discrepancy.detected_at }}</div>
                            </div>

                            <div>
                                <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                    <Clock class="h-4 w-4" />
                                    Days Open
                                </div>
                                <div class="mt-1 text-sm">{{ discrepancy.days_open }} days</div>
                            </div>

                            <div>
                                <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                    <User class="h-4 w-4" />
                                    Detected By
                                </div>
                                <div class="mt-1 text-sm">{{ discrepancy.detected_by.name }}</div>
                            </div>

                            <div v-if="discrepancy.station">
                                <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                    <Building2 class="h-4 w-4" />
                                    Station
                                </div>
                                <div class="mt-1 text-sm">
                                    {{ discrepancy.station.name }}
                                    <span class="text-muted-foreground">({{ discrepancy.station.code }})</span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ discrepancy.station.region }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
