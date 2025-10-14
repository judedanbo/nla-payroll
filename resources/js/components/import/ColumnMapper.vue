<script setup lang="ts">
import { ref, computed, watch } from 'vue';

interface ColumnMapping {
    [csvColumn: string]: string | null;
}

interface ExpectedColumn {
    field: string;
    label: string;
    required: boolean;
}

interface Props {
    csvHeaders: string[];
    expectedColumns: { [key: string]: string };
    suggestedMapping?: ColumnMapping;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    mappingChanged: [mapping: ColumnMapping];
}>();

const mapping = ref<ColumnMapping>({});

// Initialize mapping with suggestions
watch(
    () => props.suggestedMapping,
    (suggested) => {
        if (suggested) {
            mapping.value = { ...suggested };
            emit('mappingChanged', mapping.value);
        }
    },
    { immediate: true }
);

const expectedColumnsList = computed((): ExpectedColumn[] => {
    return Object.entries(props.expectedColumns).map(([field, label]) => ({
        field,
        label,
        required: !label.includes('Optional'),
    }));
});

const mappedFields = computed(() => {
    return Object.values(mapping.value).filter(v => v !== null);
});

const unmappedRequiredFields = computed(() => {
    return expectedColumnsList.value
        .filter(col => col.required)
        .filter(col => !mappedFields.value.includes(col.field));
});

const isValid = computed(() => {
    return unmappedRequiredFields.value.length === 0;
});

const updateMapping = (csvColumn: string, dbField: string | null) => {
    mapping.value[csvColumn] = dbField;
    emit('mappingChanged', mapping.value);
};

const autoMapAll = () => {
    props.csvHeaders.forEach(csvHeader => {
        const normalizedCsv = csvHeader.toLowerCase().replace(/[^a-z0-9]/g, '');

        for (const [field, label] of Object.entries(props.expectedColumns)) {
            const normalizedField = field.toLowerCase().replace(/[^a-z0-9]/g, '');
            const normalizedLabel = label.toLowerCase().replace(/[^a-z0-9]/g, '');

            if (
                normalizedCsv === normalizedField ||
                normalizedCsv === normalizedLabel ||
                normalizedCsv.includes(normalizedField) ||
                normalizedField.includes(normalizedCsv)
            ) {
                mapping.value[csvHeader] = field;
                break;
            }
        }
    });

    emit('mappingChanged', mapping.value);
};

const clearAll = () => {
    mapping.value = props.csvHeaders.reduce((acc, header) => {
        acc[header] = null;
        return acc;
    }, {} as ColumnMapping);

    emit('mappingChanged', mapping.value);
};

defineExpose({
    isValid,
    mapping,
});
</script>

<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Column Mapping</h3>
                <p class="text-sm text-muted-foreground">
                    Map CSV columns to database fields
                </p>
            </div>

            <div class="flex gap-2">
                <button
                    @click="autoMapAll"
                    class="inline-flex items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                >
                    Auto-Map
                </button>
                <button
                    @click="clearAll"
                    class="inline-flex items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                >
                    Clear All
                </button>
            </div>
        </div>

        <!-- Validation Status -->
        <div
            v-if="unmappedRequiredFields.length > 0"
            class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/50 dark:bg-yellow-900/20"
        >
            <div class="flex">
                <svg
                    class="h-5 w-5 text-yellow-400"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                        clip-rule="evenodd"
                    />
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Required fields not mapped
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li v-for="field in unmappedRequiredFields" :key="field.field">
                                {{ field.label }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900/50 dark:bg-green-900/20"
        >
            <div class="flex">
                <svg
                    class="h-5 w-5 text-green-400"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd"
                    />
                </svg>
                <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-200">
                    All required fields are mapped
                </p>
            </div>
        </div>

        <!-- Mapping Grid -->
        <div class="border rounded-lg overflow-hidden">
            <div class="bg-muted px-4 py-3 border-b">
                <div class="grid grid-cols-2 gap-4 text-sm font-medium">
                    <div>CSV Column</div>
                    <div>Database Field</div>
                </div>
            </div>

            <div class="divide-y max-h-96 overflow-y-auto">
                <div
                    v-for="csvHeader in csvHeaders"
                    :key="csvHeader"
                    class="px-4 py-3 hover:bg-muted/50"
                >
                    <div class="grid grid-cols-2 gap-4 items-center">
                        <div class="text-sm font-medium">
                            {{ csvHeader }}
                        </div>

                        <div>
                            <select
                                :value="mapping[csvHeader]"
                                @change="(e) => updateMapping(csvHeader, (e.target as HTMLSelectElement).value || null)"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option :value="null">-- Skip this column --</option>
                                <option
                                    v-for="col in expectedColumnsList"
                                    :key="col.field"
                                    :value="col.field"
                                >
                                    {{ col.label }}
                                    {{ col.required ? '*' : '' }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="text-sm text-muted-foreground">
            <p>
                Mapped: {{ mappedFields.length }} / {{ expectedColumnsList.length }} fields
            </p>
        </div>
    </div>
</template>
