<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ColumnMapper from '@/components/import/ColumnMapper.vue';
import DataPreview from '@/components/import/DataPreview.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface PreviewRow {
    [key: string]: string | number | null;
}

interface Props {
    fileName: string;
    importType: string;
    headers: string[];
    rows: PreviewRow[];
    expectedColumns: { [key: string]: string };
    suggestedMapping: { [key: string]: string | null };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Import', href: '/import' },
    { title: 'Preview & Map', href: '/import/preview' },
];

const columnMapping = ref<{ [key: string]: string | null }>(props.suggestedMapping);
const skipDuplicates = ref(true);
const validateOnly = ref(false);
const isProcessing = ref(false);

const columnMapperRef = ref<InstanceType<typeof ColumnMapper>>();

const handleMappingChanged = (newMapping: { [key: string]: string | null }) => {
    columnMapping.value = newMapping;
};

const processImport = () => {
    if (!columnMapperRef.value?.isValid) {
        alert('Please map all required fields before continuing.');
        return;
    }

    isProcessing.value = true;

    router.post(
        '/import/process',
        {
            column_mapping: columnMapping.value,
            skip_duplicates: skipDuplicates.value,
            validate_only: validateOnly.value,
        },
        {
            onSuccess: () => {
                isProcessing.value = false;
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
};

const cancelImport = () => {
    if (confirm('Are you sure you want to cancel this import?')) {
        router.visit('/import');
    }
};
</script>

<template>
    <Head title="Preview & Map Columns" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Preview & Map Columns</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Review your data and map CSV columns to database fields
                </p>
            </div>

            <!-- File Info -->
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg
                            class="w-8 h-8 text-muted-foreground"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <div>
                            <h3 class="font-medium">{{ fileName }}</h3>
                            <p class="text-sm text-muted-foreground">
                                {{ importType.replace('_', ' ').toUpperCase() }} Import
                            </p>
                        </div>
                    </div>

                    <div class="text-sm text-muted-foreground">
                        {{ rows.length }} rows preview
                    </div>
                </div>
            </div>

            <!-- Column Mapper -->
            <div class="rounded-xl border bg-card p-6">
                <ColumnMapper
                    ref="columnMapperRef"
                    :csv-headers="headers"
                    :expected-columns="expectedColumns"
                    :suggested-mapping="suggestedMapping"
                    @mapping-changed="handleMappingChanged"
                />
            </div>

            <!-- Data Preview -->
            <div class="rounded-xl border bg-card p-6">
                <DataPreview :headers="headers" :rows="rows" />
            </div>

            <!-- Options -->
            <div class="rounded-xl border bg-card p-6">
                <h3 class="text-lg font-semibold mb-4">Import Options</h3>

                <div class="space-y-3">
                    <label class="flex items-center gap-2">
                        <input
                            v-model="skipDuplicates"
                            type="checkbox"
                            class="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span class="text-sm">
                            Skip duplicate records
                            <span class="text-muted-foreground">
                                (Recommended - prevents duplicate entries)
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input
                            v-model="validateOnly"
                            type="checkbox"
                            class="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span class="text-sm">
                            Validate only (don't import)
                            <span class="text-muted-foreground">
                                (Useful for checking data quality before actual import)
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between rounded-xl border bg-card p-6">
                <button
                    @click="cancelImport"
                    class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent"
                >
                    Cancel
                </button>

                <button
                    @click="processImport"
                    :disabled="isProcessing"
                    class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                >
                    <template v-if="isProcessing">
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
                        Starting Import...
                    </template>
                    <template v-else>
                        {{ validateOnly ? 'Validate Data' : 'Start Import' }}
                    </template>
                </button>
            </div>
        </div>
    </AppLayout>
</template>
