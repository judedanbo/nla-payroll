<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { FileDown } from 'lucide-vue-next';

interface ImportHistory {
    id: number;
    import_type: string;
    file_name: string;
    status: string;
    total_records: number;
    successful_records: number;
    failed_records: number;
    success_rate: number;
    created_at: string;
    user: {
        name: string;
    };
}

interface Props {
    recentImports: ImportHistory[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Import',
        href: '/import',
    },
];

// State
const currentStep = ref(1);
const selectedFile = ref<File | null>(null);
const importType = ref<string>('staff');
const isUploading = ref(false);

// Computed
const importTypes = [
    { value: 'staff', label: 'Staff Records' },
    { value: 'bank_details', label: 'Bank Details' },
    { value: 'monthly_payments', label: 'Monthly Payments' },
];

const steps = [
    { number: 1, title: 'Select File' },
    { number: 2, title: 'Preview & Map' },
    { number: 3, title: 'Process' },
];

// Methods
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        selectedFile.value = target.files[0];
    }
};

const handleUpload = () => {
    if (!selectedFile.value) return;

    isUploading.value = true;

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('import_type', importType.value);

    router.post('/import/upload', formData, {
        onSuccess: () => {
            isUploading.value = false;
        },
        onError: () => {
            isUploading.value = false;
        },
    });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'text-green-600 dark:text-green-400';
        case 'processing':
            return 'text-blue-600 dark:text-blue-400';
        case 'failed':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head title="Import Data" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Import Data</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Upload and process CSV/Excel files to import staff records, bank details, or monthly payments.
                </p>
            </div>

            <!-- Stepper -->
            <div class="rounded-xl border bg-card p-6">
                <nav aria-label="Progress">
                    <ol role="list" class="flex items-center">
                        <li
                            v-for="(step, stepIdx) in steps"
                            :key="step.number"
                            :class="[
                                stepIdx !== steps.length - 1 ? 'pr-8 sm:pr-20' : '',
                                'relative',
                            ]"
                        >
                            <!-- Step Line -->
                            <div
                                v-if="stepIdx !== steps.length - 1"
                                class="absolute inset-0 flex items-center"
                                aria-hidden="true"
                            >
                                <div
                                    :class="[
                                        step.number < currentStep
                                            ? 'bg-primary'
                                            : 'bg-muted',
                                        'h-0.5 w-full',
                                    ]"
                                />
                            </div>

                            <!-- Step Content -->
                            <div
                                class="group relative flex items-center"
                            >
                                <span class="flex items-center px-6 py-4 text-sm font-medium">
                                    <span
                                        :class="[
                                            step.number === currentStep
                                                ? 'bg-primary text-primary-foreground'
                                                : step.number < currentStep
                                                  ? 'bg-primary text-primary-foreground'
                                                  : 'border-2 border-muted bg-background text-muted-foreground',
                                            'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full',
                                        ]"
                                    >
                                        <template v-if="step.number < currentStep">
                                            <svg
                                                class="h-6 w-6"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                        </template>
                                        <template v-else>
                                            {{ step.number }}
                                        </template>
                                    </span>
                                    <span
                                        :class="[
                                            step.number === currentStep
                                                ? 'text-foreground'
                                                : 'text-muted-foreground',
                                            'ml-4 text-sm font-medium',
                                        ]"
                                    >
                                        {{ step.title }}
                                    </span>
                                </span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Download Templates Section -->
            <div class="rounded-xl border bg-card p-6">
                <h2 class="text-lg font-semibold mb-3">Download CSV Templates</h2>
                <p class="text-sm text-muted-foreground mb-4">
                    Download the appropriate template file for your import type. Templates include all required columns with example data.
                </p>
                <div class="grid gap-3 sm:grid-cols-3">
                    <a
                        href="/import/templates/staff"
                        download
                        class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2.5 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        <FileDown class="size-4" />
                        Staff Records
                    </a>
                    <a
                        href="/import/templates/bank_details"
                        download
                        class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2.5 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        <FileDown class="size-4" />
                        Bank Details
                    </a>
                    <a
                        href="/import/templates/monthly_payments"
                        download
                        class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2.5 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        <FileDown class="size-4" />
                        Monthly Payments
                    </a>
                </div>
            </div>

            <!-- Step Content -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Upload Form -->
                <div class="rounded-xl border bg-card p-6">
                    <h2 class="text-xl font-semibold mb-4">Step 1: Select File</h2>

                    <div class="space-y-4">
                        <!-- Import Type Selection -->
                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Import Type
                            </label>
                            <select
                                v-model="importType"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option
                                    v-for="type in importTypes"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <!-- File Upload -->
                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Select CSV/Excel File
                            </label>
                            <div
                                class="flex items-center justify-center w-full"
                            >
                                <label
                                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer bg-muted/50 hover:bg-muted/70"
                                >
                                    <div
                                        class="flex flex-col items-center justify-center pt-5 pb-6"
                                    >
                                        <svg
                                            class="w-8 h-8 mb-4 text-muted-foreground"
                                            aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 20 16"
                                        >
                                            <path
                                                stroke="currentColor"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                                            />
                                        </svg>
                                        <p
                                            class="mb-2 text-sm text-muted-foreground"
                                        >
                                            <span class="font-semibold">Click to upload</span>
                                            or drag and drop
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            CSV, XLS, XLSX (MAX. 10MB)
                                        </p>
                                        <p
                                            v-if="selectedFile"
                                            class="mt-2 text-sm font-medium text-primary"
                                        >
                                            Selected: {{ selectedFile.name }}
                                        </p>
                                    </div>
                                    <input
                                        type="file"
                                        class="hidden"
                                        accept=".csv,.xls,.xlsx"
                                        @change="handleFileSelect"
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- Upload Button -->
                        <button
                            @click="handleUpload"
                            :disabled="!selectedFile || isUploading"
                            class="w-full inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <template v-if="isUploading">
                                <svg
                                    class="animate-spin -ml-1 mr-3 h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    />
                                </svg>
                                Uploading...
                            </template>
                            <template v-else>
                                Upload & Continue
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Recent Imports -->
                <div class="rounded-xl border bg-card p-6">
                    <h2 class="text-xl font-semibold mb-4">Recent Imports</h2>

                    <div v-if="recentImports.length === 0" class="text-center py-8">
                        <p class="text-sm text-muted-foreground">
                            No recent imports found
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="item in recentImports"
                            :key="item.id"
                            class="rounded-lg border p-4 hover:bg-muted/50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="font-medium text-sm">
                                        {{ item.file_name }}
                                    </h3>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        {{ item.import_type.replace('_', ' ').toUpperCase() }}
                                        • {{ item.user.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        {{ formatDate(item.created_at) }}
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        getStatusColor(item.status),
                                        'text-xs font-medium px-2 py-1 rounded',
                                    ]"
                                >
                                    {{ item.status.toUpperCase() }}
                                </span>
                            </div>

                            <div class="mt-3 flex items-center gap-4 text-xs text-muted-foreground">
                                <span>Total: {{ item.total_records }}</span>
                                <span class="text-green-600 dark:text-green-400">
                                    Success: {{ item.successful_records }}
                                </span>
                                <span class="text-red-600 dark:text-red-400">
                                    Failed: {{ item.failed_records }}
                                </span>
                                <span>Rate: {{ item.success_rate }}%</span>
                            </div>

                            <div class="mt-3">
                                <a
                                    :href="`/import/${item.id}`"
                                    class="text-xs text-primary hover:underline"
                                >
                                    View Details →
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a
                            href="/import/history"
                            class="text-sm text-primary hover:underline font-medium"
                        >
                            View All History →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
