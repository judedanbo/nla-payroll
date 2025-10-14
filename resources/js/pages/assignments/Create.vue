<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import InputError from '@/components/InputError.vue'
import { ArrowLeft, UserPlus } from 'lucide-vue-next'
import { route } from '@/wayfinder'

interface User {
  id: number
  name: string
  email: string
}

interface Station {
  id: number
  name: string
  code: string
  city: string
  region: string
  expected_headcount: number
}

interface Session {
  id: number
  session_name: string
  start_date: string
  end_date: string | null
}

interface PageProps {
  users: User[]
  stations: Station[]
  sessions: Session[]
}

const props = defineProps<PageProps>()

const form = useForm({
  user_id: undefined as number | undefined,
  station_id: undefined as number | undefined,
  headcount_session_id: undefined as number | undefined,
  start_date: new Date().toISOString().split('T')[0],
  end_date: '' as string,
})

function submit() {
  form.post(route('assignments.store'), {
    preserveScroll: true,
  })
}
</script>

<template>
  <AppLayout>
    <div class="container mx-auto max-w-3xl space-y-6 py-6">
      <div class="flex items-center gap-4">
        <Button
          variant="ghost"
          size="icon"
          as="a"
          :href="route('assignments.index')"
        >
          <ArrowLeft class="size-4" />
        </Button>
        <Heading>Create Team Assignment</Heading>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Assignment Details</CardTitle>
          <CardDescription>
            Assign an auditor to a station for the headcount session
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- User Selection -->
            <div class="space-y-2">
              <Label for="user">Auditor *</Label>
              <Select
                v-model="form.user_id"
                :disabled="form.processing"
              >
                <SelectTrigger id="user" :aria-invalid="Boolean(form.errors.user_id)">
                  <SelectValue placeholder="Select an auditor" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="user in users"
                    :key="user.id"
                    :value="user.id"
                  >
                    {{ user.name }} ({{ user.email }})
                  </SelectItem>
                </SelectContent>
              </Select>
              <InputError :message="form.errors.user_id" />
            </div>

            <!-- Station Selection -->
            <div class="space-y-2">
              <Label for="station">Station *</Label>
              <Select
                v-model="form.station_id"
                :disabled="form.processing"
              >
                <SelectTrigger id="station" :aria-invalid="Boolean(form.errors.station_id)">
                  <SelectValue placeholder="Select a station" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="station in stations"
                    :key="station.id"
                    :value="station.id"
                  >
                    <div class="flex flex-col">
                      <span>{{ station.name }} ({{ station.code }})</span>
                      <span class="text-muted-foreground text-xs">
                        {{ station.city }}, {{ station.region }} • {{ station.expected_headcount }} staff
                      </span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <InputError :message="form.errors.station_id" />
            </div>

            <!-- Session Selection (Optional) -->
            <div class="space-y-2">
              <Label for="session">Headcount Session (Optional)</Label>
              <Select
                v-model="form.headcount_session_id"
                :disabled="form.processing"
              >
                <SelectTrigger id="session">
                  <SelectValue placeholder="Link to a session (optional)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="undefined">
                    None
                  </SelectItem>
                  <SelectItem
                    v-for="session in sessions"
                    :key="session.id"
                    :value="session.id"
                  >
                    <div class="flex flex-col">
                      <span>{{ session.session_name }}</span>
                      <span class="text-muted-foreground text-xs">
                        {{ session.start_date }} - {{ session.end_date || 'Ongoing' }}
                      </span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <InputError :message="form.errors.headcount_session_id" />
            </div>

            <!-- Start Date -->
            <div class="space-y-2">
              <Label for="start_date">Start Date *</Label>
              <input
                id="start_date"
                v-model="form.start_date"
                type="date"
                :min="new Date().toISOString().split('T')[0]"
                :disabled="form.processing"
                :aria-invalid="Boolean(form.errors.start_date)"
                class="border-input placeholder:text-muted-foreground dark:bg-input/30 flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
              >
              <InputError :message="form.errors.start_date" />
            </div>

            <!-- End Date (Optional) -->
            <div class="space-y-2">
              <Label for="end_date">End Date (Optional)</Label>
              <input
                id="end_date"
                v-model="form.end_date"
                type="date"
                :min="form.start_date"
                :disabled="form.processing"
                :aria-invalid="Boolean(form.errors.end_date)"
                class="border-input placeholder:text-muted-foreground dark:bg-input/30 flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
              >
              <p class="text-muted-foreground text-xs">
                Leave empty for an ongoing assignment
              </p>
              <InputError :message="form.errors.end_date" />
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-3">
              <Button
                type="submit"
                :disabled="form.processing"
                class="flex-1"
              >
                <UserPlus class="mr-2 size-4" />
                {{ form.processing ? 'Creating Assignment...' : 'Create Assignment' }}
              </Button>
              <Button
                type="button"
                variant="outline"
                as="a"
                :href="route('assignments.index')"
                :disabled="form.processing"
              >
                Cancel
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
