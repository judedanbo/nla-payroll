<script setup lang="ts">
import { computed } from 'vue';

interface PreviewRow {
    [key: string]: string | number | null;
}

interface Props {
    headers: string[];
    rows: PreviewRow[];
    maxRows?: number;
}

const props = withDefaults(defineProps<Props>(), {
    maxRows: 10,
});

const displayRows = computed(() => {
    return props.rows.slice(0, props.maxRows);
});

const hasMoreRows = computed(() => {
    return props.rows.length > props.maxRows;
});
</script>

<template>
    <div class="space-y-4">
        <!-- Header -->
        <div>
            <h3 class="text-lg font-semibold">Data Preview</h3>
            <p class="text-sm text-muted-foreground">
                Preview of first {{ displayRows.length }} rows
                <span v-if="hasMoreRows">
                    ({{ rows.length - maxRows }} more rows not shown)
                </span>
            </p>
        </div>

        <!-- Data Table -->
        <div class="border rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted">
                        <tr>
                            <th
                                class="px-4 py-3 text-left font-medium text-muted-foreground border-r last:border-r-0"
                            >
                                #
                            </th>
                            <th
                                v-for="header in headers"
                                :key="header"
                                class="px-4 py-3 text-left font-medium text-muted-foreground border-r last:border-r-0 whitespace-nowrap"
                            >
                                {{ header }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="(row, index) in displayRows"
                            :key="index"
                            class="hover:bg-muted/50"
                        >
                            <td
                                class="px-4 py-3 font-medium text-muted-foreground border-r"
                            >
                                {{ index + 1 }}
                            </td>
                            <td
                                v-for="header in headers"
                                :key="header"
                                class="px-4 py-3 border-r last:border-r-0 max-w-xs truncate"
                                :title="String(row[header] || '')"
                            >
                                <span v-if="row[header]" class="text-foreground">
                                    {{ row[header] }}
                                </span>
                                <span v-else class="text-muted-foreground italic">
                                    (empty)
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="displayRows.length === 0"
            class="text-center py-8 text-muted-foreground"
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
            <p class="mt-2">No data to preview</p>
        </div>
    </div>
</template>
