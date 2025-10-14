import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export interface ImportStatus {
    id: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    total_records: number;
    successful_records: number;
    failed_records: number;
    progress: number;
}

export function useImport() {
    const isUploading = ref(false);
    const isProcessing = ref(false);
    const uploadError = ref<string | null>(null);
    const processError = ref<string | null>(null);

    /**
     * Upload a file for import
     */
    const uploadFile = (
        file: File,
        importType: string,
        onSuccess?: () => void,
        onError?: (error: string) => void
    ) => {
        isUploading.value = true;
        uploadError.value = null;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('import_type', importType);

        router.post('/import/upload', formData, {
            onSuccess: () => {
                isUploading.value = false;
                onSuccess?.();
            },
            onError: (errors) => {
                isUploading.value = false;
                const errorMessage = Object.values(errors).flat().join(', ');
                uploadError.value = errorMessage;
                onError?.(errorMessage);
            },
        });
    };

    /**
     * Process import with column mapping
     */
    const processImport = (
        columnMapping: Record<string, string | null>,
        options: {
            skipDuplicates?: boolean;
            validateOnly?: boolean;
        } = {},
        onSuccess?: (importId: number) => void,
        onError?: (error: string) => void
    ) => {
        isProcessing.value = true;
        processError.value = null;

        router.post(
            '/import/process',
            {
                column_mapping: columnMapping,
                skip_duplicates: options.skipDuplicates ?? true,
                validate_only: options.validateOnly ?? false,
            },
            {
                onSuccess: (page) => {
                    isProcessing.value = false;
                    // Extract import ID from redirect URL if available
                    const importId = page.props?.import?.id;
                    onSuccess?.(importId);
                },
                onError: (errors) => {
                    isProcessing.value = false;
                    const errorMessage = Object.values(errors).flat().join(', ');
                    processError.value = errorMessage;
                    onError?.(errorMessage);
                },
            }
        );
    };

    /**
     * Poll import status
     */
    const pollImportStatus = (
        importId: number,
        onUpdate: (status: ImportStatus) => void,
        interval = 2000
    ): () => void => {
        const intervalId = setInterval(() => {
            fetch(`/import/${importId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    const status: ImportStatus = {
                        id: data.import.id,
                        status: data.import.status,
                        total_records: data.import.total_records,
                        successful_records: data.import.successful_records,
                        failed_records: data.import.failed_records,
                        progress:
                            data.import.total_records > 0
                                ? Math.round(
                                      ((data.import.successful_records +
                                          data.import.failed_records) /
                                          data.import.total_records) *
                                          100
                                  )
                                : 0,
                    };

                    onUpdate(status);

                    // Stop polling if import is complete
                    if (
                        status.status === 'completed' ||
                        status.status === 'failed'
                    ) {
                        clearInterval(intervalId);
                    }
                })
                .catch((error) => {
                    console.error('Failed to poll import status:', error);
                });
        }, interval);

        // Return cleanup function
        return () => clearInterval(intervalId);
    };

    /**
     * Rollback an import
     */
    const rollbackImport = (
        importId: number,
        onSuccess?: () => void,
        onError?: (error: string) => void
    ) => {
        router.post(
            `/import/${importId}/rollback`,
            {},
            {
                onSuccess: () => {
                    onSuccess?.();
                },
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat().join(', ');
                    onError?.(errorMessage);
                },
            }
        );
    };

    /**
     * Download error CSV
     */
    const downloadErrors = (importId: number) => {
        window.location.href = `/import/${importId}/errors`;
    };

    /**
     * Format file size
     */
    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    /**
     * Get import type label
     */
    const getImportTypeLabel = (type: string): string => {
        const labels: Record<string, string> = {
            staff: 'Staff Records',
            bank_details: 'Bank Details',
            monthly_payments: 'Monthly Payments',
        };

        return labels[type] || type;
    };

    /**
     * Get status color classes
     */
    const getStatusColor = (status: string): string => {
        const colors: Record<string, string> = {
            completed: 'text-green-600 dark:text-green-400',
            processing: 'text-blue-600 dark:text-blue-400',
            failed: 'text-red-600 dark:text-red-400',
            pending: 'text-gray-600 dark:text-gray-400',
        };

        return colors[status] || colors.pending;
    };

    return {
        // State
        isUploading: computed(() => isUploading.value),
        isProcessing: computed(() => isProcessing.value),
        uploadError: computed(() => uploadError.value),
        processError: computed(() => processError.value),

        // Methods
        uploadFile,
        processImport,
        pollImportStatus,
        rollbackImport,
        downloadErrors,

        // Utilities
        formatFileSize,
        getImportTypeLabel,
        getStatusColor,
    };
}
