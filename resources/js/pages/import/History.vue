<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface ImportHistory {
    id: number;
    import_type: string;
    file_name: string;
    status: string;
    total_records: number;
    successful_records: number;
    failed_records: number;
    success_rate: number;
    can_rollback: boolean;
    rolled_back_at: string | null;
    created_at: string;
    completed_at: string | null;
    user: {
        name: string;
    };
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: ImportHistory[];
}

interface Filters {
    status?: string;
    import_type?: string;
    date_from?: string;
    date_to?: string;
}

interface Props {
    imports: Pagination;
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Import', href: '/import' },
    { title: 'History', href: '/import/history' },
];

const localFilters = ref<Filters>({
    status: props.filters.status || '',
    import_type: props.filters.import_type || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const isRollingBack = ref<number | null>(null);

// Watch filters and reload with debounce
watch(
    localFilters,
    (newFilters) => {
        router.get(
            '/import/history',
            { ...newFilters },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    },
    { deep: true }
);

const clearFilters = () => {
    localFilters.value = {
        status: '',
        import_type: '',
        date_from: '',
        date_to: '',
    };
};

const rollbackImport = (importId: number) => {
    if (!confirm('Are you sure you want to rollback this import? This will delete all imported records.')) {
        return;
    }

    isRollingBack.value = importId;

    router.post(
        `/import/${importId}/rollback`,
        {},
        {
            onSuccess: () => {
                isRollingBack.value = null;
            },
            onError: () => {
                isRollingBack.value = null;
            },
        }
    );
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'processing':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
        case 'failed':
            return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head title="Import History" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Import History</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        View and manage all import operations
                    </p>
                </div>

                <a
                    href="/import"
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                >
                    New Import
                </a>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border bg-card p-6">
                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Status</label>
                        <select
                            v-model="localFilters.status"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Import Type</label>
                        <select
                            v-model="localFilters.import_type"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All Types</option>
                            <option value="staff">Staff Records</option>
                            <option value="bank_details">Bank Details</option>
                            <option value="monthly_payments">Monthly Payments</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">From Date</label>
                        <input
                            v-model="localFilters.date_from"
                            type="date"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">To Date</label>
                        <input
                            v-model="localFilters.date_to"
                            type="date"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </div>

                <div class="mt-4">
                    <button
                        @click="clearFilters"
                        class="text-sm text-primary hover:underline"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Import List -->
            <div class="rounded-xl border bg-card">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">File Name</th>
                                <th class="px-4 py-3 text-left font-medium">Type</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-left font-medium">Records</th>
                                <th class="px-4 py-3 text-left font-medium">Success Rate</th>
                                <th class="px-4 py-3 text-left font-medium">User</th>
                                <th class="px-4 py-3 text-left font-medium">Date</th>
                                <th class="px-4 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="item in imports.data"
                                :key="item.id"
                                class="hover:bg-muted/50"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ item.file_name }}</div>
                                    <div v-if="item.rolled_back_at" class="text-xs text-red-600 dark:text-red-400">
                                        Rolled back {{ formatDate(item.rolled_back_at) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium">
                                        {{ item.import_type.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            getStatusColor(item.status),
                                            'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                        ]"
                                    >
                                        {{ item.status.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-xs space-y-1">
                                        <div>Total: {{ item.total_records }}</div>
                                        <div class="text-green-600 dark:text-green-400">
                                            ✓ {{ item.successful_records }}
                                        </div>
                                        <div class="text-red-600 dark:text-red-400">
                                            ✗ {{ item.failed_records }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ item.success_rate }}%</div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ item.user.name }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground text-xs">
                                    <div>{{ formatDate(item.created_at) }}</div>
                                    <div v-if="item.completed_at">
                                        Done: {{ formatDate(item.completed_at) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a
                                            :href="`/import/${item.id}`"
                                            class="text-xs text-primary hover:underline"
                                        >
                                            View
                                        </a>

                                        <button
                                            v-if="item.can_rollback"
                                            @click="rollbackImport(item.id)"
                                            :disabled="isRollingBack === item.id"
                                            class="text-xs text-red-600 hover:underline disabled:opacity-50"
                                        >
                                            {{ isRollingBack === item.id ? 'Rolling back...' : 'Rollback' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="imports.data.length === 0"
                    class="text-center py-12"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-muted-foreground/50"
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
                    <h3 class="mt-2 text-sm font-medium">No imports found</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Try adjusting your filters or create a new import.
                    </p>
                </div>

                <!-- Pagination -->
                <div
                    v-if="imports.last_page > 1"
                    class="flex items-center justify-between border-t px-4 py-3"
                >
                    <div class="text-sm text-muted-foreground">
                        Showing {{ (imports.current_page - 1) * imports.per_page + 1 }} to
                        {{ Math.min(imports.current_page * imports.per_page, imports.total) }}
                        of {{ imports.total }} results
                    </div>

                    <div class="flex gap-2">
                        <a
                            v-for="page in imports.last_page"
                            :key="page"
                            :href="`/import/history?page=${page}`"
                            :class="[
                                'px-3 py-1 rounded-md text-sm',
                                page === imports.current_page
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-muted',
                            ]"
                        >
                            {{ page }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
