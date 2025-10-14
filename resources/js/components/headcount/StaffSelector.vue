<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Search, UserCheck } from 'lucide-vue-next'

interface Staff {
  id: number
  staff_number: string
  full_name: string
  job_title: string | null
  department: string | null
  is_verified?: boolean
}

interface Props {
  staff: Staff[]
  selectedIds?: number[]
  showVerifiedStatus?: boolean
}

interface Emits {
  (e: 'select', staffId: number): void
  (e: 'selectMultiple', staffIds: number[]): void
}

const props = withDefaults(defineProps<Props>(), {
  selectedIds: () => [],
  showVerifiedStatus: true,
})

const emit = defineEmits<Emits>()

const searchQuery = ref('')

const filteredStaff = computed(() => {
  if (!searchQuery.value) return props.staff

  const query = searchQuery.value.toLowerCase()
  return props.staff.filter(
    staff =>
      staff.full_name.toLowerCase().includes(query) ||
      staff.staff_number.toLowerCase().includes(query) ||
      staff.department?.toLowerCase().includes(query)
  )
})

const unverifiedStaff = computed(() => {
  if (!props.showVerifiedStatus) return filteredStaff.value
  return filteredStaff.value.filter(s => !s.is_verified)
})

const verifiedStaff = computed(() => {
  if (!props.showVerifiedStatus) return []
  return filteredStaff.value.filter(s => s.is_verified)
})

function isSelected(staffId: number) {
  return props.selectedIds.includes(staffId)
}
</script>

<template>
  <div class="space-y-3">
    <!-- Search Input -->
    <div class="relative">
      <Search class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search by name, staff number, or department..."
        class="border-input placeholder:text-muted-foreground focus:border-ring focus:ring-ring/50 h-9 w-full rounded-md border bg-transparent pl-9 pr-3 text-sm shadow-xs outline-none focus:ring-[3px]"
      >
    </div>

    <!-- Stats -->
    <div class="text-muted-foreground flex items-center justify-between text-sm">
      <span>{{ unverifiedStaff.length }} unverified</span>
      <span v-if="verifiedStaff.length > 0">{{ verifiedStaff.length }} verified</span>
    </div>

    <!-- Unverified Staff List -->
    <div class="max-h-96 space-y-1 overflow-y-auto rounded-md border p-2">
      <button
        v-for="staffMember in unverifiedStaff"
        :key="staffMember.id"
        type="button"
        :class="[
          'hover:bg-accent w-full rounded-md p-3 text-left transition-colors',
          isSelected(staffMember.id) ? 'bg-accent' : '',
        ]"
        @click="emit('select', staffMember.id)"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="font-medium">{{ staffMember.full_name }}</div>
            <div class="text-muted-foreground text-xs">{{ staffMember.staff_number }}</div>
            <div class="text-muted-foreground text-xs">
              {{ staffMember.job_title }}
              <span v-if="staffMember.department"> • {{ staffMember.department }}</span>
            </div>
          </div>
        </div>
      </button>

      <p v-if="unverifiedStaff.length === 0" class="text-muted-foreground py-8 text-center text-sm">
        No staff members found
      </p>
    </div>

    <!-- Verified Staff (Collapsed) -->
    <details v-if="verifiedStaff.length > 0" class="group">
      <summary class="text-muted-foreground flex cursor-pointer items-center gap-2 text-sm hover:underline">
        <span>Verified Staff ({{ verifiedStaff.length }})</span>
      </summary>
      <div class="mt-2 space-y-1 rounded-md border p-2">
        <div
          v-for="staffMember in verifiedStaff"
          :key="staffMember.id"
          class="bg-muted/50 flex items-center justify-between rounded-md p-2"
        >
          <div>
            <div class="text-sm font-medium">{{ staffMember.full_name }}</div>
            <div class="text-muted-foreground text-xs">{{ staffMember.staff_number }}</div>
          </div>
          <UserCheck class="text-green-600 dark:text-green-400 size-4" />
        </div>
      </div>
    </details>
  </div>
</template>
