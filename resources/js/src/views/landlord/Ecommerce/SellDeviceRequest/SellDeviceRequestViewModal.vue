<script setup>
import { ref, onMounted } from "vue";
import Axios from "@/services/axios";

const props = defineProps({
    modalId: String,
    selectedUrl: String,
});
const emit = defineEmits(["close"]);

const request = ref(null);
const loading = ref(true);
const lightboxSrc = ref(null);

onMounted(async () => {
    try {
        const { data } = await Axios.get(props.selectedUrl);
        request.value = data;
    } finally {
        loading.value = false;
    }
});

const statusLabel = (status) => {
    return { 2: 'Pending', 3: 'Rejected', 4: 'Approved' }[status] ?? status;
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" title="Sell Device Request Details" :show-footer="false"
        @close="$emit('close')">
        <template #body>
            <app-loader v-if="loading" />
            <template v-else-if="request">
                <div class="row g-3">
                    <div class="col-md-12">
                        <p class="text-muted mb-1 small">Ad Title</p>
                        <p class="fw-semibold mb-0">{{ request.ad_title }}</p>
                    </div>

                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">Customer</p>
                        <p class="fw-semibold mb-0">{{ request.contact?.name ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">Phone Number</p>
                        <p class="fw-semibold mb-0">{{ request.phone_number }}</p>
                    </div>

                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">Category</p>
                        <p class="fw-semibold mb-0">{{ request.category?.name ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">Brand / Model</p>
                        <p class="fw-semibold mb-0">{{ [request.brand?.name, request.model].filter(Boolean).join(' — ') || '-' }}</p>
                    </div>

                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">Condition</p>
                        <p class="fw-semibold mb-0 text-capitalize">{{ request.condition?.replaceAll('_', ' ') ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">Location</p>
                        <p class="fw-semibold mb-0">{{ request.new_district?.name ?? '-' }}</p>
                    </div>

                    <div class="col-md-6" v-if="request.ram || request.storage || request.color">
                        <p class="text-muted mb-1 small">Specs</p>
                        <p class="fw-semibold mb-0">{{ [request.ram, request.storage, request.color].filter(Boolean).join(' · ') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">Asking Price</p>
                        <p class="fw-semibold mb-0">৳{{ request.asking_price }}
                            <span v-if="request.is_negotiable" class="text-muted small">(Negotiable)</span>
                        </p>
                    </div>

                    <div class="col-md-6" v-if="request.is_exchange_possible">
                        <p class="text-muted mb-1 small">Exchange</p>
                        <p class="fw-semibold mb-0">Possible</p>
                    </div>
                    <div class="col-md-6" v-if="request.video_url">
                        <p class="text-muted mb-1 small">Video</p>
                        <a :href="request.video_url" target="_blank" class="fw-semibold">{{ request.video_url }}</a>
                    </div>

                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">Status</p>
                        <span class="badge"
                            :class="{
                                'bg-warning text-dark': statusLabel(request.status) === 'Pending',
                                'bg-success': statusLabel(request.status) === 'Approved',
                                'bg-danger': statusLabel(request.status) === 'Rejected',
                            }">
                            {{ statusLabel(request.status) }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted mb-1 small">Submitted</p>
                        <p class="fw-semibold mb-0">{{ request.created_at }}</p>
                    </div>

                    <div class="col-md-12">
                        <p class="text-muted mb-1 small">Description</p>
                        <p class="fw-semibold mb-0">{{ request.description }}</p>
                    </div>

                    <!-- Key Features -->
                    <div v-if="request.features?.length" class="col-md-12">
                        <p class="text-muted mb-2 small">Key Features</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span v-for="feature in request.features" :key="feature.id"
                                class="badge bg-light text-dark border">
                                {{ feature.feature_name }}: {{ feature.feature_value }}
                            </span>
                        </div>
                    </div>

                    <!-- Photos -->
                    <div v-if="request.images?.length" class="col-md-12">
                        <p class="text-muted mb-2 small">Photos</p>
                        <div class="d-flex flex-wrap gap-2">
                            <div v-for="(img, idx) in request.images" :key="idx"
                                class="position-relative border rounded-3 overflow-hidden"
                                style="width:100px; height:100px; cursor:pointer;"
                                @click="lightboxSrc = img">
                                <img :src="img" :alt="`Photo ${idx + 1}`"
                                    style="width:100%; height:100%; object-fit:cover;" />
                            </div>
                        </div>
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
