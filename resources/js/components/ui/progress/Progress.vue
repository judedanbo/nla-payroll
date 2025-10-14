<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { ProgressIndicator, ProgressRoot, type ProgressRootProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<ProgressRootProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <ProgressRoot
    v-bind="props"
    :class="cn(
      'bg-secondary relative h-4 w-full overflow-hidden rounded-full',
      props.class,
    )"
  >
    <ProgressIndicator
      class="bg-primary size-full flex-1 transition-all duration-300"
      :style="`transform: translateX(-${100 - (props.modelValue ?? 0)}%)`"
    />
  </ProgressRoot>
</template>
