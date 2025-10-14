<script setup lang="ts">
import HeadcountController from '@/actions/App/Http/Controllers/HeadcountController';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Station {
    id: number;
    name: string;
    code: string;
    city: string;
    region: string;
    expected_headcount: number;
}

interface Props {
    stations: Station[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Headcount', href: '/headcount' },
    { title: 'Create Session', href: '/headcount/create' },
];
</script>

<template>
    <Head title="Create Headcount Session" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Create Headcount Session
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Create a new headcount verification session to track staff attendance
                    </p>
                </div>

                <Link href="/headcount">
                    <Button variant="outline">Cancel</Button>
                </Link>
            </div>

            <!-- Form Card -->
            <Card class="max-w-3xl">
                <CardHeader>
                    <CardTitle>Session Details</CardTitle>
                    <CardDescription>
                        Provide basic information about this headcount session
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <Form
                        v-bind="HeadcountController.store.form()"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <!-- Session Name -->
                        <div class="grid gap-2">
                            <Label for="session_name">
                                Session Name
                                <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                id="session_name"
                                name="session_name"
                                type="text"
                                required
                                placeholder="e.g., Q4 2025 Headcount Verification"
                                class="w-full"
                            />
                            <InputError :message="errors.session_name" />
                        </div>

                        <!-- Description -->
                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Optional description of this headcount session..."
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                            <InputError :message="errors.description" />
                        </div>

                        <!-- Date Range -->
                        <div class="grid gap-6 sm:grid-cols-2">
                            <!-- Start Date -->
                            <div class="grid gap-2">
                                <Label for="start_date">
                                    Start Date
                                    <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="start_date"
                                    name="start_date"
                                    type="date"
                                    required
                                    class="w-full"
                                />
                                <InputError :message="errors.start_date" />
                            </div>

                            <!-- End Date -->
                            <div class="grid gap-2">
                                <Label for="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    name="end_date"
                                    type="date"
                                    class="w-full"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Leave blank for open-ended session
                                </p>
                                <InputError :message="errors.end_date" />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between border-t pt-6">
                            <div class="flex items-center gap-4">
                                <Button type="submit" :disabled="processing">
                                    {{ processing ? 'Creating...' : 'Create Session' }}
                                </Button>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p
                                        v-show="recentlySuccessful"
                                        class="text-sm text-green-600 dark:text-green-400"
                                    >
                                        Session created successfully!
                                    </p>
                                </Transition>
                            </div>

                            <Link href="/headcount">
                                <Button variant="ghost" type="button">Cancel</Button>
                            </Link>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <!-- Info Card -->
            <Card class="max-w-3xl">
                <CardHeader>
                    <CardTitle>Available Stations</CardTitle>
                    <CardDescription>
                        {{ stations.length }} active stations available for verification
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <div class="grid gap-3">
                        <div
                            v-for="station in stations"
                            :key="station.id"
                            class="flex items-center justify-between rounded-lg border p-3 hover:bg-muted/50"
                        >
                            <div>
                                <div class="font-medium">{{ station.name }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ station.code }} • {{ station.city }}, {{ station.region }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium">
                                    {{ station.expected_headcount }}
                                </div>
                                <div class="text-xs text-muted-foreground">Expected Staff</div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
