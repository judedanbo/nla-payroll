<script setup lang="ts">
import { ref } from 'vue';

interface Props {
    accept?: string;
    maxSize?: number; // in MB
}

const props = withDefaults(defineProps<Props>(), {
    accept: '.csv,.xls,.xlsx',
    maxSize: 10,
});

const emit = defineEmits<{
    fileSelected: [file: File];
    error: [message: string];
}>();

const isDragging = ref(false);
const selectedFile = ref<File | null>(null);

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        processFile(target.files[0]);
    }
};

const handleDrop = (event: DragEvent) => {
    isDragging.value = false;

    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        processFile(event.dataTransfer.files[0]);
    }
};

const processFile = (file: File) => {
    // Validate file type
    const acceptedTypes = props.accept.split(',').map(t => t.trim());
    const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();

    if (!acceptedTypes.includes(fileExtension)) {
        emit('error', `Invalid file type. Accepted types: ${props.accept}`);
        return;
    }

    // Validate file size
    const fileSizeMB = file.size / (1024 * 1024);
    if (fileSizeMB > props.maxSize) {
        emit('error', `File size exceeds ${props.maxSize}MB limit`);
        return;
    }

    selectedFile.value = file;
    emit('fileSelected', file);
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const clearFile = () => {
    selectedFile.value = null;
};

defineExpose({
    clearFile,
});
</script>

<template>
    <div class="w-full">
        <label
            :class="[
                'flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-lg cursor-pointer transition-colors',
                isDragging
                    ? 'border-primary bg-primary/10'
                    : 'border-muted-foreground/25 bg-muted/30 hover:bg-muted/50',
            ]"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop.prevent="handleDrop"
        >
            <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4">
                <svg
                    class="w-10 h-10 mb-3 text-muted-foreground"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 20 16"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                    />
                </svg>

                <template v-if="!selectedFile">
                    <p class="mb-2 text-sm text-muted-foreground">
                        <span class="font-semibold">Click to upload</span>
                        or drag and drop
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ accept.replace(/\./g, '').toUpperCase() }} (MAX. {{ maxSize }}MB)
                    </p>
                </template>

                <template v-else>
                    <div class="flex items-center gap-2">
                        <svg
                            class="w-5 h-5 text-green-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <p class="text-sm font-medium text-foreground">
                            {{ selectedFile.name }}
                        </p>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ (selectedFile.size / (1024 * 1024)).toFixed(2) }} MB
                    </p>
                </template>
            </div>

            <input
                type="file"
                class="hidden"
                :accept="accept"
                @change="handleFileSelect"
            />
        </label>
    </div>
</template>
