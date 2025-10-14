<script setup lang="ts">
import { ref, onUnmounted, watch } from 'vue';
import { useCamera } from '@/composables/useCamera';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {  Camera, X, RotateCw, Check } from 'lucide-vue-next';

interface Props {
    required?: boolean;
}

interface Emits {
    (e: 'captured', file: File): void;
    (e: 'cleared'): void;
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
});

const emit = defineEmits<Emits>();

const videoRef = ref<HTMLVideoElement | null>(null);
const camera = useCamera();
const facingMode = ref<'user' | 'environment'>('environment');

// Start camera when video element is available
watch(videoRef, async (element) => {
    if (element && !camera.isActive.value) {
        try {
            await camera.startCamera(facingMode.value);
            element.srcObject = camera.stream.value;
        } catch (err) {
            console.error('Failed to start camera:', err);
        }
    }
});

const handleCapture = async () => {
    if (!videoRef.value) return;

    try {
        await camera.capturePhoto(videoRef.value);
        const file = camera.getCapturedFile();
        if (file) {
            emit('captured', file);
        }
    } catch (err: any) {
        console.error('Capture failed:', err);
    }
};

const handleRetake = () => {
    camera.clearCapture();
    emit('cleared');
};

const toggleCamera = async () => {
    camera.stopCamera();
    facingMode.value = facingMode.value === 'user' ? 'environment' : 'user';

    if (videoRef.value) {
        try {
            await camera.startCamera(facingMode.value);
            videoRef.value.srcObject = camera.stream.value;
        } catch (err) {
            console.error('Failed to switch camera:', err);
        }
    }
};

onUnmounted(() => {
    camera.reset();
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Camera class="size-5" />
                Photo Verification
                <span v-if="required" class="text-sm text-destructive">*</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Error Alert -->
            <Alert v-if="camera.error.value" variant="destructive">
                <AlertDescription>
                    {{ camera.error.value.message }}
                </AlertDescription>
            </Alert>

            <!-- Camera not supported -->
            <Alert v-if="!camera.isSupported.value" variant="destructive">
                <AlertDescription>
                    Camera is not supported on this device
                </AlertDescription>
            </Alert>

            <!-- Video Preview / Captured Photo -->
            <div class="relative aspect-video overflow-hidden rounded-lg bg-muted">
                <!-- Live Video Feed -->
                <video
                    v-show="camera.isActive.value && !camera.previewUrl.value"
                    ref="videoRef"
                    autoplay
                    playsinline
                    class="h-full w-full object-cover"
                />

                <!-- Captured Photo Preview -->
                <img
                    v-if="camera.previewUrl.value"
                    :src="camera.previewUrl.value"
                    alt="Captured photo"
                    class="h-full w-full object-cover"
                />

                <!-- Placeholder when camera is off -->
                <div
                    v-if="!camera.isActive.value && !camera.previewUrl.value"
                    class="flex h-full items-center justify-center text-muted-foreground"
                >
                    <div class="text-center">
                        <Camera class="mx-auto size-16 opacity-20" />
                        <p class="mt-2 text-sm">Camera preview will appear here</p>
                    </div>
                </div>
            </div>

            <!-- Camera Controls -->
            <div class="flex flex-wrap gap-2">
                <!-- Capture Button (shown when camera is active) -->
                <Button
                    v-if="camera.isActive.value && !camera.previewUrl.value"
                    @click="handleCapture"
                    class="flex-1"
                >
                    <Camera class="mr-2 size-4" />
                    Capture Photo
                </Button>

                <!-- Retake Button (shown when photo is captured) -->
                <Button
                    v-if="camera.previewUrl.value"
                    @click="handleRetake"
                    variant="outline"
                    class="flex-1"
                >
                    <RotateCw class="mr-2 size-4" />
                    Retake
                </Button>

                <!-- Confirm Button (shown when photo is captured) -->
                <Button
                    v-if="camera.previewUrl.value"
                    @click="() => {}"
                    variant="default"
                    class="flex-1"
                >
                    <Check class="mr-2 size-4" />
                    Confirm
                </Button>

                <!-- Toggle Camera Button -->
                <Button
                    v-if="camera.isActive.value && !camera.previewUrl.value"
                    @click="toggleCamera"
                    variant="outline"
                    size="icon"
                >
                    <RotateCw class="size-4" />
                </Button>

                <!-- Clear Button -->
                <Button
                    v-if="camera.previewUrl.value"
                    @click="handleRetake"
                    variant="ghost"
                    size="icon"
                >
                    <X class="size-4" />
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
