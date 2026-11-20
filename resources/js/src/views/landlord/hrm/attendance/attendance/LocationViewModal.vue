<script setup>
import { ref, computed, onMounted } from 'vue';
import Axios from '@/services/axios';
import { HRM_ATTENDANCES } from '@/services/endpoints/hrm.js';

const props = defineProps({
    modalId: { type: String, default: 'location-view-modal' },
    title:   { type: String, default: 'Location' },
    location: {
        type: Object,
        default: () => ({}),
        // { ip, latitude, longitude, address, geo_provider }
    },
});

defineEmits(['close']);

const geoApiKey  = ref(null);
const geoLoading = ref(true);

onMounted(() => {
    Axios.get(`${HRM_ATTENDANCES}/geo-settings`)
        .then(({ data }) => { geoApiKey.value = data.geo_api_key; })
        .finally(() => { geoLoading.value = false; });
});

const provider = computed(() => props.location?.geo_provider ?? null);

const mapImageUrl = computed(() => {
    const lat = props.location?.latitude;
    const lon = props.location?.longitude;
    if (!lat || !lon) return null;

    const key = geoApiKey.value;

    if (provider.value === 'mapbox' && key) {
        return `https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-s+dc2626(${lon},${lat})/${lon},${lat},14,0/700x340?access_token=${key}`;
    }
    if (provider.value === 'google_map' && key) {
        return `https://maps.googleapis.com/maps/api/staticmap?center=${lat},${lon}&zoom=14&size=700x340&markers=color:red%7C${lat},${lon}&key=${key}`;
    }
    if (provider.value === 'ip_geolocation') {
        return `https://staticmap.openstreetmap.de/staticmap.php?center=${lat},${lon}&zoom=14&size=700x340&markers=${lat},${lon},red-pushpin`;
    }
    return null;
});

const providerLabel = computed(() => {
    const map = { mapbox: 'Mapbox', google_map: 'Google Map', ip_geolocation: 'IP Geolocation' };
    return provider.value ? (map[provider.value] ?? provider.value) : null;
});
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="title" :show-footer="false"
        @close="$emit('close')">
        <template #body>

            <!-- Map -->
            <div v-if="!geoLoading && mapImageUrl" class="loc-map mb-3">
                <img :src="mapImageUrl" alt="Location map" loading="lazy" />
            </div>
            <div v-else-if="geoLoading" class="loc-map-placeholder d-flex align-items-center justify-content-center mb-3">
                <div class="spinner-border spinner-border-sm text-secondary me-2"></div>
                <span class="text-muted small">Loading map…</span>
            </div>

            <!-- Info rows -->
            <div class="loc-info">
                <div v-if="location.ip" class="loc-row">
                    <span class="loc-label">Ip location</span>
                    <span class="loc-value">{{ location.ip }}</span>
                </div>
                <div v-if="location.latitude && location.longitude" class="loc-row">
                    <span class="loc-label">Coordinates</span>
                    <span class="loc-value">{{ location.latitude }}, {{ location.longitude }}</span>
                </div>
                <div v-if="location.address" class="loc-row">
                    <span class="loc-label">Address</span>
                    <span class="loc-value">{{ location.address }}</span>
                </div>
                <div v-if="providerLabel" class="loc-row">
                    <span class="loc-label">Provider</span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3">
                        {{ providerLabel }}
                    </span>
                </div>

                <div v-if="!location.ip && !location.latitude" class="text-muted text-center py-3">
                    No location data available.
                </div>
            </div>

        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.loc-map {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    background: #f1f5f9;

    img {
        display: block;
        width: 100%;
        height: 300px;
        object-fit: cover;
    }
}

.loc-map-placeholder {
    height: 160px;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    background: #f8fafc;
}

.loc-info {
    background: var(--body-bg, #f8fafc);
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.loc-row {
    display: flex;
    align-items: baseline;
    gap: 16px;
    font-size: 14px;
}

.loc-label {
    width: 110px;
    flex-shrink: 0;
    color: var(--secondary-font-color, #6b7280);
    font-size: 13px;
}

.loc-value {
    font-weight: 600;
    color: var(--font-color);
    word-break: break-all;
}
</style>
