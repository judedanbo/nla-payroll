import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export interface Session {
    id: number;
    session_name: string;
    description?: string;
    status: 'pending' | 'in_progress' | 'paused' | 'completed' | 'cancelled';
    start_date: string;
    end_date?: string;
    completion_percentage: number;
    verification_stats: VerificationStats;
    created_by: { id: number; name: string };
}

export interface VerificationStats {
    total: number;
    present: number;
    absent: number;
    on_leave: number;
    ghost: number;
}

export interface Verification {
    id: number;
    staff_id: number;
    verification_status: 'present' | 'absent' | 'on_leave' | 'ghost';
    verified_at: string;
    location?: string;
    remarks?: string;
}

export interface VerificationFormData {
    headcount_session_id: number;
    staff_id: number;
    station_id: number;
    verification_status: 'present' | 'absent' | 'on_leave' | 'ghost';
    photo: File;
    latitude?: number;
    longitude?: number;
    remarks?: string;
}

export function useHeadcount() {
    const activeSession = ref<Session | null>(null);
    const verifications = ref<Verification[]>([]);
    const isCapturing = ref(false);
    const captureError = ref<string | null>(null);

    /**
     * Start a headcount session
     */
    const startSession = (
        sessionId: number,
        onSuccess?: () => void,
        onError?: (error: string) => void
    ) => {
        router.post(
            `/headcount/${sessionId}/start`,
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
     * End a headcount session
     */
    const endSession = (
        sessionId: number,
        onSuccess?: () => void,
        onError?: (error: string) => void
    ) => {
        router.post(
            `/headcount/${sessionId}/end`,
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
     * Pause a headcount session
     */
    const pauseSession = (
        sessionId: number,
        onSuccess?: () => void,
        onError?: (error: string) => void
    ) => {
        router.post(
            `/headcount/${sessionId}/pause`,
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
     * Capture staff verification with photo and GPS
     */
    const captureVerification = (
        data: VerificationFormData,
        onSuccess?: () => void,
        onError?: (error: string) => void
    ) => {
        isCapturing.value = true;
        captureError.value = null;

        const formData = new FormData();
        formData.append('headcount_session_id', data.headcount_session_id.toString());
        formData.append('staff_id', data.staff_id.toString());
        formData.append('station_id', data.station_id.toString());
        formData.append('verification_status', data.verification_status);
        formData.append('photo', data.photo);

        if (data.latitude !== undefined) {
            formData.append('latitude', data.latitude.toString());
        }

        if (data.longitude !== undefined) {
            formData.append('longitude', data.longitude.toString());
        }

        if (data.remarks) {
            formData.append('remarks', data.remarks);
        }

        router.post('/headcount/verify', formData, {
            onSuccess: () => {
                isCapturing.value = false;
                onSuccess?.();
            },
            onError: (errors) => {
                isCapturing.value = false;
                const errorMessage = Object.values(errors).flat().join(', ');
                captureError.value = errorMessage;
                onError?.(errorMessage);
            },
        });
    };

    /**
     * Bulk verify multiple staff members
     */
    const bulkVerify = (
        sessionId: number,
        staffIds: number[],
        stationId: number,
        status: 'present' | 'absent' | 'on_leave',
        onSuccess?: (count: number) => void,
        onError?: (error: string) => void
    ) => {
        router.post(
            '/headcount/bulk-verify',
            {
                headcount_session_id: sessionId,
                staff_ids: staffIds,
                station_id: stationId,
                verification_status: status,
            },
            {
                onSuccess: () => {
                    onSuccess?.(staffIds.length);
                },
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat().join(', ');
                    onError?.(errorMessage);
                },
            }
        );
    };

    /**
     * Get verification status label
     */
    const getStatusLabel = (status: string): string => {
        const labels: Record<string, string> = {
            present: 'Present',
            absent: 'Absent',
            on_leave: 'On Leave',
            ghost: 'Ghost Employee',
        };

        return labels[status] || status;
    };

    /**
     * Get status color classes
     */
    const getStatusColor = (status: string): string => {
        const colors: Record<string, string> = {
            present: 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900',
            absent: 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900',
            on_leave: 'text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900',
            ghost: 'text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900',
        };

        return colors[status] || 'text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-900';
    };

    /**
     * Get session status color
     */
    const getSessionStatusColor = (status: string): string => {
        const colors: Record<string, string> = {
            pending: 'text-gray-600 dark:text-gray-400',
            in_progress: 'text-blue-600 dark:text-blue-400',
            paused: 'text-yellow-600 dark:text-yellow-400',
            completed: 'text-green-600 dark:text-green-400',
            cancelled: 'text-red-600 dark:text-red-400',
        };

        return colors[status] || colors.pending;
    };

    /**
     * Calculate progress percentage from stats
     */
    const calculateProgress = (stats: VerificationStats, expectedTotal: number): number => {
        if (expectedTotal === 0) return 0;
        return Math.round((stats.total / expectedTotal) * 100);
    };

    /**
     * Format date for display
     */
    const formatDate = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return {
        // State
        activeSession: computed(() => activeSession.value),
        verifications: computed(() => verifications.value),
        isCapturing: computed(() => isCapturing.value),
        captureError: computed(() => captureError.value),

        // Methods
        startSession,
        endSession,
        pauseSession,
        captureVerification,
        bulkVerify,

        // Utilities
        getStatusLabel,
        getStatusColor,
        getSessionStatusColor,
        calculateProgress,
        formatDate,
    };
}
