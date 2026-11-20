<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { format } from "date-fns";
import { LogIn, LogOut } from "@iconoir/vue";
import Axios from "@/services/axios";
import { HRM_ATTENDANCES } from "@/services/endpoints/hrm.js";
import SelfieCapture from "./SelfieCapture.vue";

const props = defineProps({
    modalId: { type: String, default: 'punch-attendance-modal' },
    mode: { type: String, required: true }, // 'in' | 'out'
    todayStatus: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["close", "success"]);

const { t } = useI18n();
const toast = useToast();

const now              = ref(new Date());
const note             = ref('');
const workFromHome     = ref(false);
const submitting       = ref(false);
const selectedShiftId  = ref(null);
const selfieBlob       = ref(null);

const availableWorkShifts = computed(() => props.todayStatus?.available_work_shifts ?? []);
const multipleShifts      = computed(() => !isPunchOut.value && availableWorkShifts.value.length > 1);

/* ── Location state ─────────────────────────────────── */
const ip            = ref(null);
const latitude      = ref(null);
const longitude     = ref(null);
const address       = ref(null);
const locationError = ref(null);
const locationLoading = ref(false);

const geoProvider = computed(() => props.todayStatus?.geo_provider ?? 'none');
const geoApiKey   = computed(() => props.todayStatus?.geo_api_key  ?? null);

const isPunchOut = computed(() => props.mode === 'out');

/* ── Static map image URL ───────────────────────────── */
const mapImageUrl = computed(() => {
    if (!latitude.value || !longitude.value) return null;
    const lat = latitude.value;
    const lon = longitude.value;
    const key = geoApiKey.value;

    if (geoProvider.value === 'mapbox' && key) {
        return `https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-s+dc2626(${lon},${lat})/${lon},${lat},14,0/640x320?access_token=${key}`;
    }
    if (geoProvider.value === 'google_map' && key) {
        return `https://maps.googleapis.com/maps/api/staticmap?center=${lat},${lon}&zoom=14&size=640x320&markers=color:red%7C${lat},${lon}&key=${key}`;
    }
    // ip_geolocation with coordinates: use free OSM static map
    if (geoProvider.value === 'ip_geolocation') {
        return `https://staticmap.openstreetmap.de/staticmap.php?center=${lat},${lon}&zoom=14&size=640x320&markers=${lat},${lon},red-pushpin`;
    }
    return null;
});

/* ── IP fetch ───────────────────────────────────────── */
const fetchIp = async () => {
    try {
        const res  = await fetch('https://api.ipify.org?format=json');
        const data = await res.json();
        ip.value   = data.ip;
    } catch {
        ip.value = null;
    }
};

/* ── Reverse geocode after browser GPS ─────────────── */
const fetchAddress = async () => {
    if (!latitude.value || !longitude.value || !geoApiKey.value) return;
    const lat = latitude.value;
    const lon = longitude.value;
    const key = geoApiKey.value;

    try {
        if (geoProvider.value === 'mapbox') {
            const res  = await fetch(
                `https://api.mapbox.com/geocoding/v5/mapbox.places/${lon},${lat}.json?access_token=${key}`
            );
            const data = await res.json();
            address.value = data.features?.[0]?.place_name ?? null;
        } else if (geoProvider.value === 'google_map') {
            const res  = await fetch(
                `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lon}&key=${key}`
            );
            const data = await res.json();
            address.value = data.results?.[0]?.formatted_address ?? null;
        }
    } catch {
        // address stays null — non-critical
    }
};

/* ── Browser GPS (mapbox / google_map) ─────────────── */
const fetchLocation = () => {
    if (!navigator.geolocation) {
        locationError.value = t('location_not_supported');
        return;
    }
    locationLoading.value = true;
    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            latitude.value      = pos.coords.latitude;
            longitude.value     = pos.coords.longitude;
            locationError.value = null;
            locationLoading.value = false;
            await fetchAddress();
        },
        (err) => {
            locationError.value = err.code === err.PERMISSION_DENIED
                ? t('location_permission_denied')
                : t('could_not_determine_location');
            locationLoading.value = false;
        },
        { timeout: 10000 }
    );
};

/* ── IP Geolocation provider ────────────────────────── */
const fetchIpGeolocation = async () => {
    locationLoading.value = true;
    try {
        const key = geoApiKey.value;
        const url = key
            ? `https://api.ipgeolocation.io/ipgeo?apiKey=${key}`
            : `https://api.ipgeolocation.io/ipgeo`;
        const res  = await fetch(url);
        const data = await res.json();
        ip.value        = data.ip        ?? null;
        latitude.value  = data.latitude  ? parseFloat(data.latitude)  : null;
        longitude.value = data.longitude ? parseFloat(data.longitude) : null;
        address.value   = [data.district, data.city, data.state_prov, data.country_name]
            .filter(Boolean).join(', ') || null;
    } catch {
        locationError.value = 'Could not fetch IP geolocation data';
    } finally {
        locationLoading.value = false;
    }
};

/* ── Bootstrap on mount ─────────────────────────────── */
onMounted(() => {
    const provider = geoProvider.value;

    if (provider === 'ip_geolocation') {
        fetchIpGeolocation();
    } else {
        fetchIp();
        if (provider === 'mapbox' || provider === 'google_map') {
            fetchLocation();
        }
    }
});

/* ── Submit ─────────────────────────────────────────── */
const submit = () => {
    submitting.value = true;

    const formData = new FormData();
    if (note.value) formData.append('note', note.value);
    if (ip.value) formData.append('ip', ip.value);
    if (latitude.value !== null) formData.append('latitude', latitude.value);
    if (longitude.value !== null) formData.append('longitude', longitude.value);
    if (address.value) formData.append('address', address.value);
    if (locationError.value) formData.append('location_error', locationError.value);
    if (selfieBlob.value) formData.append('selfie', selfieBlob.value, 'selfie.jpg');

    if (!isPunchOut.value) {
        formData.append('work_from_home', workFromHome.value ? '1' : '0');
        if (selectedShiftId.value) {
            formData.append('work_shift_id', selectedShiftId.value);
        }
    }

    const url = isPunchOut.value
        ? `${HRM_ATTENDANCES}/punch-out`
        : `${HRM_ATTENDANCES}/punch-in`;

    Axios.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(({ data }) => {
            toast.success(data.message);
            emit('success');
            emit('close');
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message ?? 'Something went wrong');
        })
        .finally(() => {
            submitting.value = false;
        });
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large"
        :title="isPunchOut ? $t('punch_out') : $t('punch_in')"
        :save-button-label="isPunchOut ? $t('punch_out') : $t('punch_in')"
        :preloader="submitting" @submit="submit" @close="$emit('close')">
        <template #body>

            <!-- Time row -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 d-flex gap-3 align-items-start">
                    <div class="punch-icon"
                        :class="isPunchOut ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'">
                        <component :is="isPunchOut ? LogOut : LogIn" />
                    </div>
                    <div>
                        <p class="text-muted mb-1">
                            {{ isPunchOut ? $t('punch_out_date_time') : $t('punch_in_date_time') }}
                        </p>
                        <p class="fw-bold mb-1">Today ({{ format(now, 'h:mm a') }})</p>
                        <p class="text-muted mb-0">{{ format(now, 'dd-MM-yyyy') }}</p>
                    </div>
                </div>

                <div v-if="isPunchOut" class="col-md-6 d-flex gap-3 align-items-start">
                    <div class="punch-icon bg-success-subtle text-success">
                        <LogIn />
                    </div>
                    <div>
                        <p class="text-muted mb-1">{{ $t('punch_in_date_time') }}</p>
                        <p class="fw-bold mb-1">Today ({{ todayStatus.in_time }})</p>
                        <p class="text-muted mb-2">{{ todayStatus.in_date }}</p>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" disabled
                                :checked="todayStatus.work_from_home" />
                            <label class="form-check-label">{{ $t('work_from_home') }}</label>
                        </div>
                    </div>
                </div>

                <div v-else class="col-md-6">
                    <div class="form-check form-switch mt-2">
                        <input id="punch_work_from_home" class="form-check-input" type="checkbox"
                            v-model="workFromHome" />
                        <label class="form-check-label" for="punch_work_from_home">
                            {{ $t('work_from_home') }}
                        </label>
                    </div>
                </div>

                <div v-if="multipleShifts" class="col-md-12">
                    <label class="form-label mb-1 fw-semibold">Work Shift <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="selectedShiftId" required>
                        <option :value="null" disabled>Select work shift</option>
                        <option v-for="shift in availableWorkShifts" :key="shift.id" :value="shift.id">
                            {{ shift.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Location info card -->
            <div class="punch-location mb-4">
                <div v-if="locationLoading" class="punch-location__loading">
                    <div class="spinner-border spinner-border-sm text-secondary me-2"></div>
                    Detecting location…
                </div>

                <template v-else>
                    <!-- Info row -->
                    <div class="punch-location__info">
                        <div class="punch-location__row">
                            <span class="punch-location__label">IP</span>
                            <span class="punch-location__value">{{ ip ?? '—' }}</span>
                        </div>
                        <div v-if="latitude && longitude" class="punch-location__row">
                            <span class="punch-location__label">Coordinates</span>
                            <span class="punch-location__value">
                                {{ latitude.toFixed(6) }}, {{ longitude.toFixed(6) }}
                            </span>
                        </div>
                        <div v-if="address" class="punch-location__row">
                            <span class="punch-location__label">Address</span>
                            <span class="punch-location__value">{{ address }}</span>
                        </div>
                    </div>

                    <!-- Map -->
                    <div v-if="mapImageUrl" class="punch-location__map">
                        <img :src="mapImageUrl" alt="Location map" loading="lazy" />
                    </div>

                    <!-- Error -->
                    <div v-if="locationError" class="alert alert-warning py-2 mb-0 mt-2">
                        {{ locationError }}
                    </div>
                </template>
            </div>

            <!-- Selfie -->
            <selfie-capture v-model="selfieBlob" />

            <!-- Note -->
            <p class="text-muted mb-2">
                {{ isPunchOut ? $t('punch_out_note_hint') : $t('punch_in_note_hint') }}
            </p>
            <textarea class="form-control" rows="3" v-model="note" :placeholder="$t('note')"></textarea>

        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.punch-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Location card */
.punch-location {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
    background: var(--white);

    &__loading {
        padding: 16px;
        display: flex;
        align-items: center;
        font-size: 13px;
        color: var(--secondary-font-color, #6b7280);
    }

    &__info {
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    &__row {
        display: flex;
        gap: 10px;
        font-size: 13px;
        line-height: 1.4;
    }

    &__label {
        width: 90px;
        flex-shrink: 0;
        color: var(--secondary-font-color, #6b7280);
        font-weight: 500;
    }

    &__value {
        color: var(--font-color);
        font-weight: 600;
        word-break: break-all;
    }

    &__map {
        border-top: 1px solid var(--border-color);
        img {
            display: block;
            width: 100%;
            height: 240px;
            object-fit: cover;
        }
    }
}
</style>
