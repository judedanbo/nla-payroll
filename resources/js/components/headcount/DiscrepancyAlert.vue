<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { AlertTriangle, XCircle } from 'lucide-vue-next'

interface Discrepancy {
  id: number
  staff: {
    staff_number: string
    full_name: string
  }
  discrepancy_type: string
  severity: 'low' | 'medium' | 'high' | 'critical'
  description: string
  status: string
  detected_at: string
}

interface Props {
  discrepancies: Discrepancy[]
  showAll?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showAll: false,
})

function getSeverityColor(severity: string) {
  const colors = {
    low: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    critical: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
  }
  return colors[severity as keyof typeof colors] || colors.medium
}
</script>

<template>
  <div v-if="discrepancies.length > 0" class="space-y-3">
    <Alert variant="destructive" class="border-destructive">
      <AlertTriangle class="size-4" />
      <AlertTitle>Discrepancies Detected</AlertTitle>
      <AlertDescription>
        {{ discrepancies.length }} discrepanc{{ discrepancies.length > 1 ? 'ies' : 'y' }} found during verification
      </AlertDescription>
    </Alert>

    <div class="space-y-2">
      <div
        v-for="(discrepancy, index) in showAll ? discrepancies : discrepancies.slice(0, 3)"
        :key="discrepancy.id"
        class="bg-muted/50 rounded-lg border p-3"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1 space-y-1">
            <div class="flex items-center gap-2">
              <XCircle class="text-destructive size-4 shrink-0" />
              <p class="text-sm font-medium">
                {{ discrepancy.staff.full_name }} ({{ discrepancy.staff.staff_number }})
              </p>
              <Badge :class="getSeverityColor(discrepancy.severity)" class="text-xs">
                {{ discrepancy.severity }}
              </Badge>
            </div>
            <p class="text-muted-foreground text-sm">{{ discrepancy.description }}</p>
            <p class="text-muted-foreground text-xs">{{ discrepancy.detected_at }}</p>
          </div>
        </div>
      </div>

      <button
        v-if="!showAll && discrepancies.length > 3"
        class="text-primary hover:underline w-full text-center text-sm"
      >
        Show {{ discrepancies.length - 3 }} more discrepanc{{ discrepancies.length - 3 > 1 ? 'ies' : 'y' }}
      </button>
    </div>
  </div>
</template>
