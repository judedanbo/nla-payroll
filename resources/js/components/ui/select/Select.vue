<script setup lang="ts">
import { SelectRoot, type SelectRootProps } from 'reka-ui'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<SelectRootProps>()
</script>

<template>
  <SelectRoot v-bind="{ ...props, ...$attrs }">
    <slot />
  </SelectRoot>
</template>
