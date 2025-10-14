<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { CheckCircle2, Clock, Users, XCircle } from 'lucide-vue-next'

interface Props {
  sessionName: string
  description?: string
  stats: {
    total: number
    present: number
    absent: number
    on_leave: number
    ghost: number
  }
  completionPercentage: number
  status: 'pending' | 'in_progress' | 'completed' | 'paused'
}

const props = defineProps<Props>()

const statusConfig = computed(() => {
  const configs = {
    pending: {
      label: 'Pending',
      icon: Clock,
      color: 'text-yellow-600 dark:text-yellow-400',
      bgColor: 'bg-yellow-100 dark:bg-yellow-900/30',
    },
    in_progress: {
      label: 'In Progress',
      icon: Clock,
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    completed: {
      label: 'Completed',
      icon: CheckCircle2,
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
    },
    paused: {
      label: 'Paused',
      icon: XCircle,
      color: 'text-gray-600 dark:text-gray-400',
      bgColor: 'bg-gray-100 dark:bg-gray-900/30',
    },
  }
  return configs[props.status]
})
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <CardTitle>{{ sessionName }}</CardTitle>
          <CardDescription v-if="description">{{ description }}</CardDescription>
        </div>
        <div :class="['flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium', statusConfig.bgColor, statusConfig.color]">
          <component :is="statusConfig.icon" class="size-3" />
          {{ statusConfig.label }}
        </div>
      </div>
    </CardHeader>
    <CardContent class="space-y-4">
      <!-- Progress Bar -->
      <div class="space-y-2">
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted-foreground">Overall Progress</span>
          <span class="font-medium">{{ completionPercentage }}%</span>
        </div>
        <Progress :model-value="completionPercentage" class="h-2" />
      </div>

      <!-- Statistics Grid -->
      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="bg-muted/50 rounded-lg p-3">
          <div class="flex items-center gap-2">
            <Users class="text-muted-foreground size-4" />
            <span class="text-muted-foreground text-xs">Total</span>
          </div>
          <p class="mt-1 text-2xl font-bold">{{ stats.total }}</p>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="text-green-600 dark:text-green-400 size-4" />
            <span class="text-green-700 dark:text-green-300 text-xs">Present</span>
          </div>
          <p class="text-green-700 dark:text-green-300 mt-1 text-2xl font-bold">{{ stats.present }}</p>
        </div>

        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
          <div class="flex items-center gap-2">
            <XCircle class="text-red-600 dark:text-red-400 size-4" />
            <span class="text-red-700 dark:text-red-300 text-xs">Absent</span>
          </div>
          <p class="text-red-700 dark:text-red-300 mt-1 text-2xl font-bold">{{ stats.absent }}</p>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3">
          <div class="flex items-center gap-2">
            <Clock class="text-yellow-600 dark:text-yellow-400 size-4" />
            <span class="text-yellow-700 dark:text-yellow-300 text-xs">On Leave</span>
          </div>
          <p class="text-yellow-700 dark:text-yellow-300 mt-1 text-2xl font-bold">{{ stats.on_leave }}</p>
        </div>
      </div>

      <!-- Ghost Employees Alert (if any) -->
      <div v-if="stats.ghost > 0" class="bg-destructive/10 border-destructive text-destructive rounded-lg border p-3">
        <div class="flex items-center gap-2">
          <XCircle class="size-4" />
          <span class="text-sm font-medium">{{ stats.ghost }} Ghost Employee{{ stats.ghost > 1 ? 's' : '' }} Detected</span>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
