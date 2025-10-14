<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface Staff {
    id: number;
    staff_number: string;
    full_name: string;
    department: string;
}

interface Station {
    id: number;
    name: string;
    code: string;
}

interface FilterOption {
    value: string;
    label: string;
}

interface Props {
    staff: Staff[];
    stations: Station[];
    types: FilterOption[];
    severities: FilterOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Discrepancies', href: '/discrepancies' },
    { title: 'Create', href: '/discrepancies/create' },
];

const form = useForm({
    staff_id: '',
    station_id: '',
    discrepancy_type: '',
    severity: '',
    description: '',
});

const submit = () => {
    form.post('/discrepancies');
};
</script>

<template>
    <Head title="Create Discrepancy" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Create Discrepancy</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Manually create a discrepancy record for investigation
                </p>
            </div>

            <!-- Form -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Discrepancy Details</CardTitle>
                    <CardDescription>
                        Provide information about the discrepancy you've identified
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Staff Selection -->
                        <div>
                            <Label for="staff_id">
                                Staff Member <span class="text-red-500">*</span>
                            </Label>
                            <select
                                id="staff_id"
                                v-model="form.staff_id"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                required
                            >
                                <option value="">Select a staff member</option>
                                <option v-for="member in staff" :key="member.id" :value="member.id">
                                    {{ member.full_name }} ({{ member.staff_number }}) - {{ member.department }}
                                </option>
                            </select>
                            <div v-if="form.errors.staff_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.staff_id }}
                            </div>
                        </div>

                        <!-- Station Selection (Optional) -->
                        <div>
                            <Label for="station_id">Station (Optional)</Label>
                            <select
                                id="station_id"
                                v-model="form.station_id"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option value="">No specific station</option>
                                <option v-for="station in stations" :key="station.id" :value="station.id">
                                    {{ station.name }} ({{ station.code }})
                                </option>
                            </select>
                            <div v-if="form.errors.station_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.station_id }}
                            </div>
                        </div>

                        <!-- Discrepancy Type -->
                        <div>
                            <Label for="discrepancy_type">
                                Discrepancy Type <span class="text-red-500">*</span>
                            </Label>
                            <select
                                id="discrepancy_type"
                                v-model="form.discrepancy_type"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                required
                            >
                                <option value="">Select a type</option>
                                <option v-for="type in types" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <div v-if="form.errors.discrepancy_type" class="mt-1 text-sm text-red-600">
                                {{ form.errors.discrepancy_type }}
                            </div>
                        </div>

                        <!-- Severity -->
                        <div>
                            <Label for="severity">
                                Severity <span class="text-red-500">*</span>
                            </Label>
                            <select
                                id="severity"
                                v-model="form.severity"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                required
                            >
                                <option value="">Select severity</option>
                                <option v-for="sev in severities" :key="sev.value" :value="sev.value">
                                    {{ sev.label }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Critical: Immediate action required | High: Priority investigation | Medium:
                                Review needed | Low: Minor issue
                            </p>
                            <div v-if="form.errors.severity" class="mt-1 text-sm text-red-600">
                                {{ form.errors.severity }}
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <Label for="description">
                                Description <span class="text-red-500">*</span>
                            </Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Provide detailed information about this discrepancy, including what was observed, when it was noticed, and any supporting evidence..."
                                rows="6"
                                class="mt-1"
                                required
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Minimum 10 characters, maximum 1000 characters
                            </p>
                            <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-4">
                            <Button type="submit" :disabled="form.processing">
                                Create Discrepancy
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="$inertia.visit('/discrepancies')"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Help Card -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Guidelines</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <p>
                        <strong>When to create a manual discrepancy:</strong> Use this form when you
                        discover an issue that wasn't automatically detected by the system.
                    </p>
                    <p>
                        <strong>Description best practices:</strong> Include specific details such as
                        dates, amounts, locations, and any evidence or documentation references.
                    </p>
                    <p>
                        <strong>Severity levels:</strong>
                    </p>
                    <ul class="list-disc list-inside space-y-1 ml-4">
                        <li>
                            <strong>Critical:</strong> Potential fraud, ghost employees, major financial
                            discrepancies
                        </li>
                        <li>
                            <strong>High:</strong> Significant anomalies requiring prompt investigation
                        </li>
                        <li><strong>Medium:</strong> Notable issues that should be reviewed</li>
                        <li><strong>Low:</strong> Minor discrepancies or data quality issues</li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
