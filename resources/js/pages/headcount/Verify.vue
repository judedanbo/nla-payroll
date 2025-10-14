<script setup lang="ts">
import GPSCapture from '@/components/headcount/GPSCapture.vue';
import PhotoCapture from '@/components/headcount/PhotoCapture.vue';
import VerificationStatusBadge from '@/components/headcount/VerificationStatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ArrowLeft, Search, UserCheck, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Station {
    id: number;
    name: string;
    code: string;
    city: string;
    staff_count: number;
    latitude: number | null;
    longitude: number | null;
    gps_boundary: number | null;
}

interface StaffMember {
    id: number;
    staff_number: string;
    full_name: string;
    job_title: string | null;
    department: string | null;
    unit: string | null;
    is_verified: boolean;
}

interface Session {
    id: number;
    session_name: string;
}

interface PageProps {
    session: Session;
    stations: Station[];
    staff: StaffMember[];
    selected_station_id?: number;
}

const props = defineProps<PageProps>();

const selectedStationId = ref<number | undefined>(props.selected_station_id);
const selectedStaffId = ref<number | undefined>();
const verificationStatus = ref<'present' | 'absent' | 'on_leave' | 'ghost'>(
    'present',
);
const photoFile = ref<File | null>(null);
const gpsData = ref<{ latitude: number; longitude: number } | null>(null);
const remarks = ref('');
const searchQuery = ref('');
const isSubmitting = ref(false);

const selectedStation = computed(() => {
    if (!selectedStationId.value) return null;
    return props.stations.find((s) => s.id === selectedStationId.value);
});

const selectedStaff = computed(() => {
    if (!selectedStaffId.value) return null;
    return props.staff.find((s) => s.id === selectedStaffId.value);
});

const filteredStaff = computed(() => {
    if (!searchQuery.value) return props.staff;

    const query = searchQuery.value.toLowerCase();
    return props.staff.filter(
        (staff) =>
            staff.full_name.toLowerCase().includes(query) ||
            staff.staff_number.toLowerCase().includes(query) ||
            staff.department?.toLowerCase().includes(query),
    );
});

const unverifiedStaff = computed(() => {
    return filteredStaff.value.filter((s) => !s.is_verified);
});

const verifiedStaff = computed(() => {
    return filteredStaff.value.filter((s) => s.is_verified);
});

const canSubmit = computed(() => {
    return (
        selectedStaffId.value &&
        selectedStationId.value &&
        photoFile.value &&
        !isSubmitting.value
    );
});

// Watch station selection and reload staff list
watch(selectedStationId, (newStationId) => {
    if (newStationId) {
        router.get(
            `/headcount/${props.session.id}/verify`,
            { station_id: newStationId },
            { preserveState: true, preserveScroll: true },
        );
    }
    selectedStaffId.value = undefined;
    photoFile.value = null;
    gpsData.value = null;
    remarks.value = '';
});

// Reset form when staff selection changes
watch(selectedStaffId, () => {
    photoFile.value = null;
    gpsData.value = null;
    remarks.value = '';
    verificationStatus.value = 'present';
});

function handlePhotoCapture(file: File) {
    photoFile.value = file;
}

function handleGPSCapture(data: { latitude: number; longitude: number }) {
    gpsData.value = data;
}

function handleSubmit() {
    if (!canSubmit.value) return;

    isSubmitting.value = true;

    const formData = new FormData();
    formData.append('headcount_session_id', String(props.session.id));
    formData.append('staff_id', String(selectedStaffId.value));
    formData.append('station_id', String(selectedStationId.value));
    formData.append('verification_status', verificationStatus.value);

    if (photoFile.value) {
        formData.append('photo', photoFile.value);
    }

    if (gpsData.value) {
        formData.append('latitude', String(gpsData.value.latitude));
        formData.append('longitude', String(gpsData.value.longitude));
    }

    if (remarks.value) {
        formData.append('remarks', remarks.value);
    }

    router.post('/headcount/verify', formData, {
        preserveScroll: true,
        onSuccess: () => {
            // Reset form for next verification
            selectedStaffId.value = undefined;
            photoFile.value = null;
            gpsData.value = null;
            remarks.value = '';
            verificationStatus.value = 'present';
            searchQuery.value = '';
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <div class="container mx-auto space-y-6 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button
                        variant="ghost"
                        size="icon"
                        as="a"
                        :href="`/headcount/${session.id}`"
                    >
                        <ArrowLeft class="size-4" />
                    </Button>
                    <div>
                        <Heading title="Staff Verification" />
                        <p class="text-sm text-muted-foreground">
                            {{ session.session_name }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Station & Staff Selection -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="size-4" />
                            Select Staff
                        </CardTitle>
                        <CardDescription>
                            Choose station and staff member to verify
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Station Selection -->
                        <div class="space-y-2">
                            <Label for="station">Station</Label>
                            <Select v-model="selectedStationId">
                                <SelectTrigger id="station">
                                    <SelectValue
                                        placeholder="Select a station"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="station in stations"
                                        :key="station.id"
                                        :value="station.id"
                                    >
                                        {{ station.name }} ({{ station.code }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="selectedStation"
                                class="text-xs text-muted-foreground"
                            >
                                {{ selectedStation.city }} •
                                {{ selectedStation.staff_count }} staff members
                            </p>
                        </div>

                        <!-- Staff Search -->
                        <div v-if="selectedStationId" class="space-y-2">
                            <Label for="search">Search Staff</Label>
                            <div class="relative">
                                <Search
                                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                />
                                <input
                                    id="search"
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Name or staff number..."
                                    class="h-9 w-full rounded-md border border-input bg-transparent pr-3 pl-9 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-[3px] focus:ring-ring/50"
                                />
                            </div>
                        </div>

                        <!-- Staff List -->
                        <div v-if="selectedStationId" class="space-y-2">
                            <Label
                                >Unverified Staff ({{
                                    unverifiedStaff.length
                                }})</Label
                            >
                            <div
                                class="max-h-96 space-y-1 overflow-y-auto rounded-md border p-2"
                            >
                                <button
                                    v-for="staff in unverifiedStaff"
                                    :key="staff.id"
                                    type="button"
                                    :class="[
                                        'w-full rounded-md p-3 text-left transition-colors hover:bg-accent',
                                        selectedStaffId === staff.id
                                            ? 'bg-accent'
                                            : '',
                                    ]"
                                    @click="selectedStaffId = staff.id"
                                >
                                    <div class="font-medium">
                                        {{ staff.full_name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ staff.staff_number }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ staff.job_title }}
                                    </div>
                                </button>
                                <p
                                    v-if="unverifiedStaff.length === 0"
                                    class="py-4 text-center text-sm text-muted-foreground"
                                >
                                    No unverified staff found
                                </p>
                            </div>

                            <!-- Verified Staff (Collapsed) -->
                            <details
                                v-if="verifiedStaff.length > 0"
                                class="group"
                            >
                                <summary
                                    class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground"
                                >
                                    Verified Staff ({{ verifiedStaff.length }})
                                </summary>
                                <div
                                    class="mt-2 space-y-1 rounded-md border p-2"
                                >
                                    <div
                                        v-for="staff in verifiedStaff"
                                        :key="staff.id"
                                        class="rounded-md bg-muted/50 p-2"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <div>
                                                <div
                                                    class="text-sm font-medium"
                                                >
                                                    {{ staff.full_name }}
                                                </div>
                                                <div
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{ staff.staff_number }}
                                                </div>
                                            </div>
                                            <UserCheck
                                                class="size-4 text-green-600"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </CardContent>
                </Card>

                <!-- Verification Form -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Verification Details</CardTitle>
                        <CardDescription>
                            Capture photo, GPS location, and verification status
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="handleSubmit" class="space-y-6">
                            <!-- Selected Staff Info -->
                            <div
                                v-if="selectedStaff"
                                class="rounded-lg border bg-muted/50 p-4"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-semibold">
                                            {{ selectedStaff.full_name }}
                                        </h4>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ selectedStaff.staff_number }}
                                        </p>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ selectedStaff.job_title }} •
                                            {{ selectedStaff.department }}
                                        </p>
                                    </div>
                                    <VerificationStatusBadge
                                        :status="verificationStatus"
                                    />
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                            >
                                <Users
                                    class="mx-auto mb-2 size-12 text-muted-foreground/50"
                                />
                                <p class="text-sm">
                                    Select a staff member to begin verification
                                </p>
                            </div>

                            <!-- Photo Capture -->
                            <div v-if="selectedStaff" class="space-y-2">
                                <Label>Verification Photo *</Label>
                                <PhotoCapture @captured="handlePhotoCapture" />
                            </div>

                            <!-- GPS Capture -->
                            <div
                                v-if="selectedStaff && selectedStation"
                                class="space-y-2"
                            >
                                <Label>GPS Location</Label>
                                <GPSCapture
                                    :station-latitude="selectedStation.latitude"
                                    :station-longitude="
                                        selectedStation.longitude
                                    "
                                    :max-distance-km="
                                        (selectedStation.gps_boundary ?? 100) /
                                        1000
                                    "
                                    @captured="handleGPSCapture"
                                />
                            </div>

                            <!-- Verification Status -->
                            <div v-if="selectedStaff" class="space-y-2">
                                <Label for="status"
                                    >Verification Status *</Label
                                >
                                <Select v-model="verificationStatus">
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="present"
                                            >Present</SelectItem
                                        >
                                        <SelectItem value="absent"
                                            >Absent</SelectItem
                                        >
                                        <SelectItem value="on_leave"
                                            >On Leave</SelectItem
                                        >
                                        <SelectItem value="ghost"
                                            >Ghost Employee</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Remarks -->
                            <div v-if="selectedStaff" class="space-y-2">
                                <Label for="remarks">Remarks</Label>
                                <Textarea
                                    id="remarks"
                                    v-model="remarks"
                                    placeholder="Add any additional notes or observations..."
                                    :rows="4"
                                />
                            </div>

                            <!-- Submit Button -->
                            <div v-if="selectedStaff" class="flex gap-3">
                                <Button
                                    type="submit"
                                    :disabled="!canSubmit"
                                    class="flex-1"
                                >
                                    <UserCheck class="mr-2 size-4" />
                                    {{
                                        isSubmitting
                                            ? 'Saving...'
                                            : 'Save Verification'
                                    }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="
                                        () => {
                                            selectedStaffId = undefined;
                                            photoFile = null;
                                            gpsData = null;
                                            remarks = '';
                                            verificationStatus = 'present';
                                        }
                                    "
                                >
                                    Clear
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
