import { ref, computed } from 'vue';

export interface Coordinates {
    latitude: number;
    longitude: number;
}

export interface GeolocationError {
    code: number;
    message: string;
}

export function useGPS() {
    const coordinates = ref<Coordinates | null>(null);
    const accuracy = ref<number>(0);
    const error = ref<GeolocationError | null>(null);
    const isTracking = ref(false);
    const isLoading = ref(false);
    let watchId: number | null = null;

    /**
     * Check if geolocation is supported
     */
    const isSupported = computed(() => 'geolocation' in navigator);

    /**
     * Get current position (one-time)
     */
    const getCurrentPosition = (): Promise<Coordinates> => {
        return new Promise((resolve, reject) => {
            if (!isSupported.value) {
                const err: GeolocationError = {
                    code: 0,
                    message: 'Geolocation is not supported by your browser',
                };
                error.value = err;
                reject(err);
                return;
            }

            isLoading.value = true;
            error.value = null;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    isLoading.value = false;
                    coordinates.value = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                    };
                    accuracy.value = position.coords.accuracy;
                    resolve(coordinates.value);
                },
                (err) => {
                    isLoading.value = false;
                    const geoError: GeolocationError = {
                        code: err.code,
                        message: getErrorMessage(err.code),
                    };
                    error.value = geoError;
                    reject(geoError);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                }
            );
        });
    };

    /**
     * Start watching position (continuous tracking)
     */
    const watchPosition = (
        onUpdate?: (coords: Coordinates) => void
    ): void => {
        if (!isSupported.value) {
            error.value = {
                code: 0,
                message: 'Geolocation is not supported by your browser',
            };
            return;
        }

        if (isTracking.value) {
            return; // Already tracking
        }

        isTracking.value = true;
        error.value = null;

        watchId = navigator.geolocation.watchPosition(
            (position) => {
                coordinates.value = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                };
                accuracy.value = position.coords.accuracy;

                if (onUpdate) {
                    onUpdate(coordinates.value);
                }
            },
            (err) => {
                const geoError: GeolocationError = {
                    code: err.code,
                    message: getErrorMessage(err.code),
                };
                error.value = geoError;
                stopWatching();
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            }
        );
    };

    /**
     * Stop watching position
     */
    const stopWatching = (): void => {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        isTracking.value = false;
    };

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    const calculateDistance = (
        lat1: number,
        lng1: number,
        lat2: number,
        lng2: number
    ): number => {
        const R = 6371; // Earth's radius in kilometers
        const dLat = toRadians(lat2 - lat1);
        const dLng = toRadians(lng2 - lng1);

        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRadians(lat1)) *
                Math.cos(toRadians(lat2)) *
                Math.sin(dLng / 2) *
                Math.sin(dLng / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const distance = R * c;

        return Math.round(distance * 100) / 100; // Round to 2 decimal places
    };

    /**
     * Convert degrees to radians
     */
    const toRadians = (degrees: number): number => {
        return degrees * (Math.PI / 180);
    };

    /**
     * Get human-readable error message
     */
    const getErrorMessage = (code: number): string => {
        switch (code) {
            case 1:
                return 'Location permission denied. Please enable location access in your browser settings.';
            case 2:
                return 'Location information unavailable. Please check your device settings.';
            case 3:
                return 'Location request timed out. Please try again.';
            default:
                return 'An unknown error occurred while retrieving your location.';
        }
    };

    /**
     * Check if coordinates are within a given radius (in km)
     */
    const isWithinRadius = (
        targetLat: number,
        targetLng: number,
        radiusKm: number
    ): boolean => {
        if (!coordinates.value) return false;

        const distance = calculateDistance(
            coordinates.value.latitude,
            coordinates.value.longitude,
            targetLat,
            targetLng
        );

        return distance <= radiusKm;
    };

    /**
     * Format coordinates for display
     */
    const formatCoordinates = (coords?: Coordinates): string => {
        const c = coords || coordinates.value;
        if (!c) return 'N/A';

        return `${c.latitude.toFixed(6)}°, ${c.longitude.toFixed(6)}°`;
    };

    /**
     * Get distance to target location
     */
    const getDistanceToTarget = (
        targetLat: number,
        targetLng: number
    ): number | null => {
        if (!coordinates.value) return null;

        return calculateDistance(
            coordinates.value.latitude,
            coordinates.value.longitude,
            targetLat,
            targetLng
        );
    };

    /**
     * Clear current position
     */
    const clearPosition = (): void => {
        stopWatching();
        coordinates.value = null;
        accuracy.value = 0;
        error.value = null;
    };

    return {
        // State
        coordinates: computed(() => coordinates.value),
        accuracy: computed(() => accuracy.value),
        error: computed(() => error.value),
        isTracking: computed(() => isTracking.value),
        isLoading: computed(() => isLoading.value),
        isSupported,

        // Methods
        getCurrentPosition,
        watchPosition,
        stopWatching,
        calculateDistance,
        isWithinRadius,
        getDistanceToTarget,
        clearPosition,

        // Utilities
        formatCoordinates,
        getErrorMessage,
    };
}
