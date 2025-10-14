<script setup lang="ts">
import { computed, watch } from 'vue';
import { useGPS, type Coordinates } from '@/composables/useGPS';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { MapPin, Loader2, CheckCircle2, AlertCircle } from 'lucide-vue-next';

interface Props {
    required?: boolean;
    stationLatitude?: number | null;
    stationLongitude?: number | null;
    maxDistanceKm?: number;
}

interface Emits {
    (e: 'captured', coordinates: Coordinates): void;
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
    stationLatitude: null,
    stationLongitude: null,
    maxDistanceKm: 0.5, // 500 meters default
});

const emit = defineEmits<Emits>();

const gps = useGPS();

const handleCapture = async () => {
    try {
        const coords = await gps.getCurrentPosition();
        emit('captured', coords);
    } catch (err) {
        console.error('Failed to get GPS coordinates:', err);
    }
};

const isWithinBoundary = computed(() => {
    if (!gps.coordinates.value || !props.stationLatitude || !props.stationLongitude) {
        return null;
    }

    return gps.isWithinRadius(
        props.stationLatitude,
        props.stationLongitude,
        props.maxDistanceKm
    );
});

const distanceToStation = computed(() => {
    if (!gps.coordinates.value || !props.stationLatitude || !props.stationLongitude) {
        return null;
    }

    return gps.getDistanceToTarget(props.stationLatitude, props.stationLongitude);
});

// Auto-emit when coordinates are captured
watch(() => gps.coordinates.value, (coords) => {
    if (coords) {
        emit('captured', coords);
    }
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <MapPin class="size-5" />
                GPS Location
                <span v-if="required" class="text-sm text-destructive">*</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Error Alert -->
            <Alert v-if="gps.error.value" variant="destructive">
                <AlertCircle class="size-4" />
                <AlertDescription>
                    {{ gps.error.value.message }}
                </AlertDescription>
            </Alert>

            <!-- GPS not supported -->
            <Alert v-if="!gps.isSupported.value" variant="destructive">
                <AlertCircle class="size-4" />
                <AlertDescription>
                    GPS is not supported on this device
                </AlertDescription>
            </Alert>

            <!-- Location Boundary Warning -->
            <Alert
                v-if="gps.coordinates.value && isWithinBoundary === false"
                variant="destructive"
            >
                <AlertCircle class="size-4" />
                <AlertDescription>
                    You are {{ distanceToStation?.toFixed(2) }} km from the station.
                    Please verify within {{ maxDistanceKm }} km of the station location.
                </AlertDescription>
            </Alert>

            <!-- Location Boundary Success -->
            <Alert
                v-if="gps.coordinates.value && isWithinBoundary === true"
                class="border-green-500/20 bg-green-500/10"
            >
                <CheckCircle2 class="size-4 text-green-600" />
                <AlertDescription class="text-green-700 dark:text-green-400">
                    Location confirmed within station boundary ({{ distanceToStation?.toFixed(2) }} km away)
                </AlertDescription>
            </Alert>

            <!-- Coordinates Display -->
            <div v-if="gps.coordinates.value" class="rounded-lg bg-muted p-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="font-medium text-muted-foreground">Latitude</p>
                        <p class="font-mono">{{ gps.coordinates.value.latitude.toFixed(6) }}°</p>
                    </div>
                    <div>
                        <p class="font-medium text-muted-foreground">Longitude</p>
                        <p class="font-mono">{{ gps.coordinates.value.longitude.toFixed(6) }}°</p>
                    </div>
                    <div class="col-span-2">
                        <p class="font-medium text-muted-foreground">Accuracy</p>
                        <p>{{ gps.accuracy.value.toFixed(0) }} meters</p>
                    </div>
                </div>
            </div>

            <!-- Placeholder when no coordinates -->
            <div
                v-else
                class="flex min-h-[120px] items-center justify-center rounded-lg border-2 border-dashed"
            >
                <div class="text-center text-muted-foreground">
                    <MapPin class="mx-auto size-12 opacity-20" />
                    <p class="mt-2 text-sm">GPS coordinates will appear here</p>
                </div>
            </div>

            <!-- Capture Button -->
            <Button
                @click="handleCapture"
                :disabled="gps.isLoading.value || !gps.isSupported.value"
                class="w-full"
            >
                <Loader2 v-if="gps.isLoading.value" class="mr-2 size-4 animate-spin" />
                <MapPin v-else class="mr-2 size-4" />
                {{ gps.isLoading.value ? 'Getting Location...' : 'Capture GPS Location' }}
            </Button>

            <!-- Clear Button -->
            <Button
                v-if="gps.coordinates.value"
                @click="gps.clearPosition"
                variant="outline"
                class="w-full"
            >
                Clear Location
            </Button>
        </CardContent>
    </Card>
</template>
