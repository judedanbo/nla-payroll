<script setup lang="ts">
import { CheckCircle2, Clock, Image, StickyNote, User } from 'lucide-vue-next'
import VerificationStatusBadge from './VerificationStatusBadge.vue'

interface Verification {
  id: number
  verification_status: 'present' | 'absent' | 'on_leave' | 'ghost'
  verified_at: string
  verified_by: {
    id: number
    name: string
  }
  location: string | null
  remarks: string | null
  has_photos: boolean
  notes_count: number
}

interface Props {
  verifications: Verification[]
  showAll?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showAll: false,
})
</script>

<template>
  <div v-if="verifications.length > 0" class="space-y-4">
    <h3 class="text-sm font-semibold">Verification History</h3>

    <div class="relative space-y-4">
      <!-- Timeline Line -->
      <div class="absolute left-3 top-2 bottom-2 w-px bg-border" />

      <!-- Timeline Items -->
      <div
        v-for="(verification, index) in showAll ? verifications : verifications.slice(0, 5)"
        :key="verification.id"
        class="relative flex gap-4"
      >
        <!-- Timeline Dot -->
        <div class="bg-background relative z-10 flex size-6 shrink-0 items-center justify-center rounded-full border-2 border-primary">
          <div class="bg-primary size-2 rounded-full" />
        </div>

        <!-- Content -->
        <div class="bg-muted/50 flex-1 rounded-lg border p-3">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 space-y-2">
              <div class="flex items-center gap-2">
                <VerificationStatusBadge :status="verification.verification_status" />
                <span class="text-muted-foreground text-xs">{{ verification.verified_at }}</span>
              </div>

              <div class="flex items-center gap-2 text-sm">
                <User class="text-muted-foreground size-3" />
                <span class="text-muted-foreground">Verified by {{ verification.verified_by.name }}</span>
              </div>

              <div v-if="verification.remarks" class="flex items-start gap-2 text-sm">
                <StickyNote class="text-muted-foreground mt-0.5 size-3 shrink-0" />
                <span class="text-muted-foreground">{{ verification.remarks }}</span>
              </div>

              <div class="flex items-center gap-3 text-xs">
                <span v-if="verification.has_photos" class="text-muted-foreground flex items-center gap-1">
                  <Image class="size-3" />
                  Photo attached
                </span>
                <span v-if="verification.notes_count > 0" class="text-muted-foreground flex items-center gap-1">
                  <StickyNote class="size-3" />
                  {{ verification.notes_count }} note{{ verification.notes_count > 1 ? 's' : '' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <button
        v-if="!showAll && verifications.length > 5"
        class="text-primary hover:underline ml-10 text-sm"
      >
        Show {{ verifications.length - 5 }} more verification{{ verifications.length - 5 > 1 ? 's' : '' }}
      </button>
    </div>
  </div>

  <div v-else class="text-muted-foreground rounded-lg border border-dashed p-8 text-center">
    <Clock class="text-muted-foreground/50 mx-auto mb-2 size-12" />
    <p class="text-sm">No verification history available</p>
  </div>
</template>
