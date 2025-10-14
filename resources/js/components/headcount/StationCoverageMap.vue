<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { MapPin, Users } from 'lucide-vue-next'

interface Station {
  id: number
  name: string
  code: string
  region: string
  expected_headcount: number
  coverage_status: 'covered' | 'uncovered'
  assigned_teams?: Array<{
    id: number
    user_name: string
    start_date: string
    end_date: string | null
  }>
}

interface RegionCoverage {
  region: string
  total_stations: number
  covered_stations: number
  uncovered_stations: number
  coverage_percentage: number
  stations: Station[]
}

interface Props {
  coverage: RegionCoverage[]
}

const props = defineProps<Props>()

const totalStats = computed(() => {
  return props.coverage.reduce(
    (acc, region) => ({
      total: acc.total + region.total_stations,
      covered: acc.covered + region.covered_stations,
      uncovered: acc.uncovered + region.uncovered_stations,
    }),
    { total: 0, covered: 0, uncovered: 0 }
  )
})

const overallPercentage = computed(() => {
  if (totalStats.value.total === 0) return 0
  return Math.round((totalStats.value.covered / totalStats.value.total) * 100)
})
</script>

<template>
  <div class="space-y-4">
    <!-- Overall Stats Card -->
    <Card>
      <CardHeader>
        <CardTitle>Overall Coverage</CardTitle>
        <CardDescription>{{ totalStats.total }} stations across {{ coverage.length }} regions</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid gap-4 md:grid-cols-3">
          <div class="bg-muted/50 rounded-lg p-4">
            <div class="text-muted-foreground mb-1 text-sm">Total Stations</div>
            <div class="text-2xl font-bold">{{ totalStats.total }}</div>
          </div>
          <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="text-green-700 dark:text-green-300 mb-1 text-sm">Covered</div>
            <div class="text-green-700 dark:text-green-300 text-2xl font-bold">{{ totalStats.covered }}</div>
          </div>
          <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
            <div class="text-red-700 dark:text-red-300 mb-1 text-sm">Uncovered</div>
            <div class="text-red-700 dark:text-red-300 text-2xl font-bold">{{ totalStats.uncovered }}</div>
          </div>
        </div>

        <div class="mt-4">
          <div class="mb-2 flex items-center justify-between text-sm">
            <span class="text-muted-foreground">Coverage Progress</span>
            <span class="font-medium">{{ overallPercentage }}%</span>
          </div>
          <div class="bg-muted h-2 overflow-hidden rounded-full">
            <div
              class="bg-primary h-full transition-all duration-300"
              :style="{ width: `${overallPercentage}%` }"
            />
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Region Coverage Cards -->
    <div class="grid gap-4 md:grid-cols-2">
      <Card v-for="region in coverage" :key="region.region">
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle class="text-base">{{ region.region }}</CardTitle>
            <Badge
              :class="[
                region.coverage_percentage === 100
                  ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                  : region.coverage_percentage > 50
                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
              ]"
            >
              {{ region.coverage_percentage }}%
            </Badge>
          </div>
          <CardDescription>
            {{ region.covered_stations }}/{{ region.total_stations }} stations covered
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div
              v-for="station in region.stations"
              :key="station.id"
              class="border-l-4 rounded-md border p-3"
              :class="[
                station.coverage_status === 'covered'
                  ? 'bg-green-50 dark:bg-green-900/10 border-green-600'
                  : 'bg-red-50 dark:bg-red-900/10 border-red-600',
              ]"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <MapPin class="size-3" />
                    <span class="text-sm font-medium">{{ station.name }} ({{ station.code }})</span>
                  </div>
                  <div class="text-muted-foreground mt-1 flex items-center gap-1 text-xs">
                    <Users class="size-3" />
                    {{ station.expected_headcount }} expected staff
                  </div>
                  <div v-if="station.assigned_teams && station.assigned_teams.length > 0" class="mt-2 space-y-1">
                    <div
                      v-for="team in station.assigned_teams"
                      :key="team.id"
                      class="text-muted-foreground text-xs"
                    >
                      → {{ team.user_name }} ({{ team.start_date }} - {{ team.end_date || 'Ongoing' }})
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
