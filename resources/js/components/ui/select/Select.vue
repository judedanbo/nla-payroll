<script setup lang="ts">
import { SelectRoot, type SelectRootEmits, type SelectRootProps } from 'reka-ui'

const props = defineProps<SelectRootProps>()
const emits = defineEmits<SelectRootEmits>()
</script>

<template>
  <SelectRoot v-bind="props" v-on="emits">
    <slot />
  </SelectRoot>
</template>
