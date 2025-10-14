<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

interface Props {
    status: 'present' | 'absent' | 'on_leave' | 'ghost';
}

const props = defineProps<Props>();

const badgeConfig = computed(() => {
    switch (props.status) {
        case 'present':
            return {
                label: 'Present',
                class: 'bg-green-500/10 text-green-700 dark:text-green-400 hover:bg-green-500/20',
            };
        case 'absent':
            return {
                label: 'Absent',
                class: 'bg-red-500/10 text-red-700 dark:text-red-400 hover:bg-red-500/20',
            };
        case 'on_leave':
            return {
                label: 'On Leave',
                class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 hover:bg-blue-500/20',
            };
        case 'ghost':
            return {
                label: 'Ghost Employee',
                class: 'bg-purple-500/10 text-purple-700 dark:text-purple-400 hover:bg-purple-500/20',
            };
        default:
            return {
                label: 'Unknown',
                class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400 hover:bg-gray-500/20',
            };
    }
});
</script>

<template>
    <Badge :class="badgeConfig.class">
        {{ badgeConfig.label }}
    </Badge>
</template>
