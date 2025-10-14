<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ImportProgress from '@/components/import/ImportProgress.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface ImportError {
    id: number;
    row_number: number;
    field_name: string;
    error_message: string;
    row_data: Record<string, any>;
}

interface ImportData {
    id: number;
    import_type: string;
    file_name: string;
    status: string;
    total_records: number;
    successful_records: number;
    failed_records: number;
    success_rate: number;
    column_mapping: Record<string, string>;
    options: Record<string, any>;
    can_rollback: boolean;
    rolled_back_at: string | null;
    created_at: string;
    completed_at: string | null;
    user: {
        name: string;
    };
    errors: ImportError[];
    has_more_errors: boolean;
}

interface Props {
    import: ImportData;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Import', href: '/import' },
    { title: 'History', href: '/import/history' },
    { title: `Import #${props.import.id}`, href: `/import/${props.import.id}` },
];

const showErrorDetails = ref<number | null>(null);
const isRollingBack = ref(false);

const toggleErrorDetails = (errorId: number) => {
    showErrorDetails.value = showErrorDetails.value === errorId ? null : errorId;
};

const rollbackImport = () => {
    if (!confirm('Are you sure you want to rollback this import? This will delete all imported records.')) {
        return;
    }

    isRollingBack.value = true;

    router.post(
        `/import/${props.import.id}/rollback`,
        {},
        {
            onSuccess: () => {
                isRollingBack.value = false;
            },
            onError: () => {
                isRollingBack.value = false;
            },
        }
    );
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head :title="`Import #${import.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Import #{{ import.id }}
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ import.file_name }} • {{ import.import_type.replace('_', ' ').toUpperCase() }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <a
                        v-if="import.failed_records > 0"
                        :href="`/import/${import.id}/errors`"
                        class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent"
                    >
                        Download Errors
                    </a>

                    <button
                        v-if="import.can_rollback"
                        @click="rollbackImport"
                        :disabled="isRollingBack"
                        class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        {{ isRollingBack ? 'Rolling back...' : 'Rollback Import' }}
                    </button>
                </div>
            </div>

            <!-- Progress -->
            <div class="rounded-xl border bg-card p-6">
                <ImportProgress
                    :status="import.status"
                    :total-records="import.total_records"
                    :successful-records="import.successful_records"
                    :failed-records="import.failed_records"
                />
            </div>

            <!-- Import Details -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Metadata -->
                <div class="rounded-xl border bg-card p-6">
                    <h2 class="text-xl font-semibold mb-4">Import Details</h2>

                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Import Type</dt>
                            <dd class="font-medium">
                                {{ import.import_type.replace('_', ' ').toUpperCase() }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-muted-foreground">File Name</dt>
                            <dd class="font-medium">{{ import.file_name }}</dd>
                        </div>

                        <div>
                            <dt class="text-muted-foreground">Imported By</dt>
                            <dd class="font-medium">{{ import.user.name }}</dd>
                        </div>

                        <div>
                            <dt class="text-muted-foreground">Started At</dt>
                            <dd class="font-medium">{{ formatDate(import.created_at) }}</dd>
                        </div>

                        <div v-if="import.completed_at">
                            <dt class="text-muted-foreground">Completed At</dt>
                            <dd class="font-medium">{{ formatDate(import.completed_at) }}</dd>
                        </div>

                        <div v-if="import.rolled_back_at">
                            <dt class="text-muted-foreground">Rolled Back At</dt>
                            <dd class="font-medium text-red-600 dark:text-red-400">
                                {{ formatDate(import.rolled_back_at) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Options -->
                <div class="rounded-xl border bg-card p-6">
                    <h2 class="text-xl font-semibold mb-4">Import Options</h2>

                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Skip Duplicates</dt>
                            <dd class="font-medium">
                                {{ import.options?.skip_duplicates ? 'Yes' : 'No' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-muted-foreground">Validate Only</dt>
                            <dd class="font-medium">
                                {{ import.options?.validate_only ? 'Yes' : 'No' }}
                            </dd>
                        </div>

                        <div v-if="import.options?.anomalies">
                            <dt class="text-muted-foreground">Anomalies Detected</dt>
                            <dd class="font-medium text-yellow-600 dark:text-yellow-400">
                                {{ import.options.anomalies.length }} anomalies
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Errors -->
            <div v-if="import.errors.length > 0" class="rounded-xl border bg-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Import Errors</h2>
                    <span class="text-sm text-muted-foreground">
                        Showing {{ import.errors.length }}
                        {{ import.has_more_errors ? `of ${import.failed_records}` : '' }} errors
                    </span>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="error in import.errors"
                        :key="error.id"
                        class="border rounded-lg overflow-hidden"
                    >
                        <button
                            @click="toggleErrorDetails(error.id)"
                            class="w-full px-4 py-3 hover:bg-muted/50 text-left flex items-center justify-between"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-medium text-muted-foreground">
                                        Row {{ error.row_number }}
                                    </span>
                                    <span class="text-xs font-medium text-red-600 dark:text-red-400">
                                        {{ error.field_name }}
                                    </span>
                                    <span class="text-sm">
                                        {{ error.error_message }}
                                    </span>
                                </div>
                            </div>

                            <svg
                                :class="[
                                    'w-5 h-5 transition-transform',
                                    showErrorDetails === error.id ? 'rotate-180' : '',
                                ]"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-if="showErrorDetails === error.id"
                            class="px-4 py-3 bg-muted/30 border-t"
                        >
                            <h4 class="text-sm font-medium mb-2">Row Data:</h4>
                            <pre class="text-xs bg-background rounded p-3 overflow-x-auto">{{ JSON.stringify(error.row_data, null, 2) }}</pre>
                        </div>
                    </div>
                </div>

                <div v-if="import.has_more_errors" class="mt-4 text-center">
                    <p class="text-sm text-muted-foreground">
                        There are more errors. Download the error CSV to see all {{ import.failed_records }} errors.
                    </p>
                    <a
                        :href="`/import/${import.id}/errors`"
                        class="inline-flex items-center mt-2 text-sm text-primary hover:underline"
                    >
                        Download Full Error Report
                    </a>
                </div>
            </div>

            <!-- No Errors -->
            <div
                v-else-if="import.status === 'completed'"
                class="rounded-xl border bg-card p-12 text-center"
            >
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
                <h3 class="mt-4 text-lg font-medium">Perfect Import!</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    All records were imported successfully with no errors.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
