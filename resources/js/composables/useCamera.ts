import { ref, computed } from 'vue';

export interface CameraError {
    name: string;
    message: string;
}

export function useCamera() {
    const stream = ref<MediaStream | null>(null);
    const isActive = ref(false);
    const error = ref<CameraError | null>(null);
    const previewUrl = ref<string | null>(null);
    const capturedBlob = ref<Blob | null>(null);

    /**
     * Check if camera is supported
     */
    const isSupported = computed(
        () =>
            navigator.mediaDevices &&
            typeof navigator.mediaDevices.getUserMedia === 'function'
    );

    /**
     * Start camera stream
     */
    const startCamera = async (facingMode: 'user' | 'environment' = 'environment'): Promise<void> => {
        if (!isSupported.value) {
            error.value = {
                name: 'NotSupportedError',
                message: 'Camera is not supported on this device',
            };
            throw error.value;
        }

        if (isActive.value) {
            return; // Camera already active
        }

        try {
            error.value = null;

            stream.value = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: facingMode,
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                },
                audio: false,
            });

            isActive.value = true;
        } catch (err: any) {
            const cameraError: CameraError = {
                name: err.name,
                message: getErrorMessage(err.name),
            };
            error.value = cameraError;
            isActive.value = false;
            throw cameraError;
        }
    };

    /**
     * Stop camera stream
     */
    const stopCamera = (): void => {
        if (stream.value) {
            stream.value.getTracks().forEach((track) => track.stop());
            stream.value = null;
        }
        isActive.value = false;
    };

    /**
     * Capture photo from video stream
     */
    const capturePhoto = (
        videoElement: HTMLVideoElement,
        quality: number = 0.8
    ): Promise<Blob> => {
        return new Promise((resolve, reject) => {
            if (!stream.value || !isActive.value) {
                reject(
                    new Error('Camera is not active. Please start the camera first.')
                );
                return;
            }

            try {
                // Create canvas with video dimensions
                const canvas = document.createElement('canvas');
                canvas.width = videoElement.videoWidth;
                canvas.height = videoElement.videoHeight;

                // Draw current video frame to canvas
                const context = canvas.getContext('2d');
                if (!context) {
                    reject(new Error('Failed to get canvas context'));
                    return;
                }

                context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

                // Convert canvas to blob
                canvas.toBlob(
                    (blob) => {
                        if (blob) {
                            capturedBlob.value = blob;
                            previewUrl.value = URL.createObjectURL(blob);
                            resolve(blob);
                        } else {
                            reject(new Error('Failed to capture photo'));
                        }
                    },
                    'image/jpeg',
                    quality
                );
            } catch (err: any) {
                reject(err);
            }
        });
    };

    /**
     * Convert blob to File object
     */
    const blobToFile = (blob: Blob, filename: string): File => {
        return new File([blob], filename, {
            type: blob.type,
            lastModified: Date.now(),
        });
    };

    /**
     * Get captured photo as File
     */
    const getCapturedFile = (filename: string = 'verification-photo.jpg'): File | null => {
        if (!capturedBlob.value) return null;
        return blobToFile(capturedBlob.value, filename);
    };

    /**
     * Clear captured photo
     */
    const clearCapture = (): void => {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }
        previewUrl.value = null;
        capturedBlob.value = null;
    };

    /**
     * Reset everything
     */
    const reset = (): void => {
        stopCamera();
        clearCapture();
        error.value = null;
    };

    /**
     * Get error message from error name
     */
    const getErrorMessage = (errorName: string): string => {
        const messages: Record<string, string> = {
            NotAllowedError:
                'Camera permission denied. Please allow camera access in your browser settings.',
            NotFoundError:
                'No camera found on this device. Please ensure a camera is connected.',
            NotReadableError:
                'Camera is already in use by another application. Please close other apps using the camera.',
            OverconstrainedError:
                'Camera does not support the requested settings. Please try again.',
            SecurityError:
                'Camera access is blocked for security reasons. Please check your browser settings.',
            TypeError: 'Invalid camera configuration. Please try again.',
        };

        return (
            messages[errorName] ||
            'An unknown error occurred while accessing the camera.'
        );
    };

    /**
     * Compress image blob
     */
    const compressImage = async (
        blob: Blob,
        maxWidth: number = 1920,
        maxHeight: number = 1080,
        quality: number = 0.8
    ): Promise<Blob> => {
        return new Promise((resolve, reject) => {
            const img = new Image();
            const url = URL.createObjectURL(blob);

            img.onload = () => {
                URL.revokeObjectURL(url);

                // Calculate new dimensions maintaining aspect ratio
                let width = img.width;
                let height = img.height;

                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }

                if (height > maxHeight) {
                    width = Math.round((width * maxHeight) / height);
                    height = maxHeight;
                }

                // Create canvas and draw resized image
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    reject(new Error('Failed to get canvas context'));
                    return;
                }

                ctx.drawImage(img, 0, 0, width, height);

                // Convert to blob
                canvas.toBlob(
                    (compressedBlob) => {
                        if (compressedBlob) {
                            resolve(compressedBlob);
                        } else {
                            reject(new Error('Failed to compress image'));
                        }
                    },
                    'image/jpeg',
                    quality
                );
            };

            img.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('Failed to load image'));
            };

            img.src = url;
        });
    };

    return {
        // State
        stream: computed(() => stream.value),
        isActive: computed(() => isActive.value),
        error: computed(() => error.value),
        previewUrl: computed(() => previewUrl.value),
        capturedBlob: computed(() => capturedBlob.value),
        isSupported,

        // Methods
        startCamera,
        stopCamera,
        capturePhoto,
        getCapturedFile,
        clearCapture,
        reset,
        compressImage,

        // Utilities
        blobToFile,
        getErrorMessage,
    };
}
