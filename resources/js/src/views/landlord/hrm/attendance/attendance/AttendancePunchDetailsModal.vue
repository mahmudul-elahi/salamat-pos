<script setup>
import { ref, computed, onMounted } from "vue";
import { LogIn, LogOut, Clock, List, Calendar } from "@iconoir/vue";
import Axios from "@/services/axios";
import { HRM_ATTENDANCES } from "@/services/endpoints/hrm.js";
import LocationViewModal from "./LocationViewModal.vue";

const props = defineProps({
    modalId: String,
    attendanceId: { type: [Number, String], required: true },
});

const emit = defineEmits(["close"]);

const loading = ref(true);
const data = ref({});

const initials = (name) => {
    const parts = (name || '').trim().split(' ').filter(Boolean);
    const first = parts[0] ? parts[0][0] : '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return `${first}${last}`.toUpperCase();
};

const title = computed(() => `${data.value.employee ?? ''} — ${data.value.in_date ?? ''}`);

const isLocationModalActive = ref(false);
const locationModalData     = ref(null);
const locationModalTitle    = ref('');

const viewLocation = (location, title) => {
    locationModalData.value     = location;
    locationModalTitle.value    = title;
    isLocationModalActive.value = true;
};

const enlargedSelfie = ref(null);

onMounted(() => {
    Axios.get(`${HRM_ATTENDANCES}/${props.attendanceId}/punch-details`)
        .then(({ data: response }) => {
            data.value = response;
        })
        .finally(() => {
            loading.value = false;
        });
});
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="title" :show-footer="false"
        @close="$emit('close')">
        <template #body>
            <app-loader v-if="loading" />
            <template v-else>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="punch-avatar-lg">{{ initials(data.employee) }}</div>
                        <div>
                            <h5 class="mb-1">{{ data.employee }}</h5>
                            <p class="text-muted mb-0 d-flex align-items-center gap-1">
                                <Calendar width="16" height="16" /> {{ data.in_date }}
                            </p>
                        </div>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border px-3 py-2 d-flex align-items-center gap-1">
                        <List width="14" height="14" /> {{ data.records_count }} {{ $t('records') }}
                    </span>
                </div>

                <div class="row g-3 bg-light rounded p-3 mb-4 text-center">
                    <div class="col-md-4">
                        <div class="stat-icon bg-success-subtle text-success mx-auto"><LogIn /></div>
                        <p class="text-muted small mb-0 mt-2 text-uppercase">{{ $t('first_in') }}</p>
                        <p class="fw-bold mb-0">{{ data.first_in ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-icon bg-danger-subtle text-danger mx-auto"><LogOut /></div>
                        <p class="text-muted small mb-0 mt-2 text-uppercase">{{ $t('last_out') }}</p>
                        <p class="fw-bold mb-0">{{ data.last_out ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-icon bg-info-subtle text-info mx-auto"><Clock /></div>
                        <p class="text-muted small mb-0 mt-2 text-uppercase">{{ $t('total_worked') }}</p>
                        <p class="fw-bold mb-0">{{ data.total_worked }}</p>
                    </div>
                </div>

                <div v-for="session in data.sessions" :key="session.index" class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="session-index">{{ session.index }}</span>
                            <span class="badge bg-success-subtle text-success d-flex align-items-center gap-1">
                                <LogIn width="14" height="14" /> {{ $t('punch_in') }}
                            </span>
                            <strong>{{ session.in_time }}</strong>
                            <img v-if="session.in_selfie" :src="session.in_selfie" class="session-selfie"
                                alt="Punch in selfie" @click="enlargedSelfie = session.in_selfie" />
                            <span class="text-muted">→</span>
                            <template v-if="session.out_time">
                                <span class="badge bg-danger-subtle text-danger d-flex align-items-center gap-1">
                                    <LogOut width="14" height="14" /> {{ $t('punch_out') }}
                                </span>
                                <strong>{{ session.out_time }}</strong>
                                <img v-if="session.out_selfie" :src="session.out_selfie" class="session-selfie"
                                    alt="Punch out selfie" @click="enlargedSelfie = session.out_selfie" />
                            </template>
                        </div>
                        <span class="badge bg-info-subtle text-info d-flex align-items-center gap-1">
                            <Clock width="14" height="14" /> {{ session.duration }}
                        </span>
                    </div>
                    <div class="d-flex gap-4 mt-2 small text-muted">
                        <button v-if="session.in_ip" class="btn btn-link btn-sm p-0 text-muted"
                            @click="viewLocation(session.in_location, 'Punch In Location')">
                            📍 in: <span style="text-decoration:underline dotted">{{ session.in_ip }}</span>
                        </button>
                        <button v-if="session.out_ip" class="btn btn-link btn-sm p-0 text-muted"
                            @click="viewLocation(session.out_location, 'Punch Out Location')">
                            📍 out: <span style="text-decoration:underline dotted">{{ session.out_ip }}</span>
                        </button>
                    </div>
                </div>
            </template>
        </template>
    </app-modal>

    <location-view-modal v-if="isLocationModalActive" modal-id="session-location-modal"
        :title="locationModalTitle" :location="locationModalData"
        @close="isLocationModalActive = false" />

    <div v-if="enlargedSelfie" class="selfie-lightbox" @click="enlargedSelfie = null">
        <img :src="enlargedSelfie" alt="Selfie" />
    </div>
</template>

<style scoped lang="scss">
.punch-avatar-lg {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #ede9fe;
    color: #4338ca;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.session-index {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #f1f1f4;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
}

.session-selfie {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    cursor: pointer;
}

.selfie-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    cursor: zoom-out;

    img {
        max-width: 90vw;
        max-height: 90vh;
        border-radius: 8px;
    }
}
</style>
