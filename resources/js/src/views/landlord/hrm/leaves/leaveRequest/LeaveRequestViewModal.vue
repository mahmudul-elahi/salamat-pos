<script setup>
import { ref, onMounted } from "vue";
import Axios from "@/services/axios";

const props = defineProps({
    modalId: String,
    selectedUrl: String,
});
const emit = defineEmits(["close"]);

const leaveRequest = ref(null);
const loading = ref(true);
const lightboxSrc = ref(null);

onMounted(async () => {
    try {
        const { data } = await Axios.get(props.selectedUrl);
        leaveRequest.value = data;
    } finally {
        loading.value = false;
    }
});

const isImage = (url) => {
    const ext = url.split('?')[0].split('#')[0].split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
};

const durationLabel = (val) => {
    return { single_day: 'Single Day', multi_day: 'Multi Day', hours: 'Hours' }[val] ?? val;
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="$t('leave_request')" :show-footer="false"
        @close="$emit('close')">
        <template #body>
            <app-loader v-if="loading" />
            <template v-else-if="leaveRequest">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">{{ $t('leave_type') }}</p>
                        <p class="fw-semibold mb-0">{{ leaveRequest.leave_type?.name ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">{{ $t('leave_duration') }}</p>
                        <p class="fw-semibold mb-0">{{ durationLabel(leaveRequest.leave_duration) }}</p>
                    </div>

                    <template v-if="leaveRequest.date">
                        <div class="col-md-6">
                            <p class="text-muted mb-1 small">{{ $t('date') }}</p>
                            <p class="fw-semibold mb-0">{{ leaveRequest.date }}</p>
                        </div>
                    </template>

                    <template v-if="leaveRequest.start_date">
                        <div class="col-md-6">
                            <p class="text-muted mb-1 small">{{ $t('start_date') }}</p>
                            <p class="fw-semibold mb-0">{{ leaveRequest.start_date }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1 small">{{ $t('end_date') }}</p>
                            <p class="fw-semibold mb-0">{{ leaveRequest.end_date }}</p>
                        </div>
                    </template>

                    <template v-if="leaveRequest.start_time">
                        <div class="col-md-6">
                            <p class="text-muted mb-1 small">{{ $t('start_time') }}</p>
                            <p class="fw-semibold mb-0">{{ leaveRequest.start_time }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1 small">{{ $t('end_time') }}</p>
                            <p class="fw-semibold mb-0">{{ leaveRequest.end_time }}</p>
                        </div>
                    </template>

                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">{{ $t('status') }}</p>
                        <span class="badge"
                            :class="{
                                'bg-warning text-dark': leaveRequest.status === 'pending',
                                'bg-success': leaveRequest.status === 'approved',
                                'bg-danger': leaveRequest.status === 'rejected',
                            }">
                            {{ leaveRequest.status }}
                        </span>
                    </div>

                    <div v-if="leaveRequest.note" class="col-md-12">
                        <p class="text-muted mb-1 small">{{ $t('note') }}</p>
                        <p class="fw-semibold mb-0">{{ leaveRequest.note }}</p>
                    </div>

                    <!-- Attachments -->
                    <div v-if="leaveRequest.attachments?.length" class="col-md-12">
                        <p class="text-muted mb-2 small">{{ $t('attachment') }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <div v-for="att in leaveRequest.attachments" :key="att.id"
                                class="position-relative border rounded-3 overflow-hidden"
                                style="width:100px; height:100px; cursor:pointer;"
                                @click="lightboxSrc = att.url">
                                <img v-if="isImage(att.url)" :src="att.url" :alt="att.name"
                                    style="width:100%; height:100%; object-fit:cover;" />
                                <a v-else :href="att.url" target="_blank"
                                    class="d-flex flex-column align-items-center justify-content-center h-100 text-decoration-none text-muted gap-1"
                                    @click.stop>
                                    <i class="bi bi-file-earmark fs-3"></i>
                                    <small class="text-truncate px-1" style="max-width:90px;">{{ att.name }}</small>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div v-else class="col-md-12">
                        <p class="text-muted mb-1 small">{{ $t('attachment') }}</p>
                        <p class="fw-semibold mb-0">-</p>
                    </div>
                </div>

                <!-- Lightbox -->
                <div v-if="lightboxSrc"
                    class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                    style="background:rgba(0,0,0,0.85); z-index:9999;"
                    @click="lightboxSrc = null">
                    <img :src="lightboxSrc" class="img-fluid rounded"
                        style="max-height:90vh; max-width:90vw; object-fit:contain;" />
                </div>
            </template>
        </template>
    </app-modal>
</template>
