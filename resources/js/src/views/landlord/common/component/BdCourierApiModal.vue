<script setup>
import { ref, computed, onMounted } from 'vue';
import { BDCOURIER_CHECK_FRAUD_API } from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";

const props = defineProps({
    modalId: String,
    phone: { type: String, required: true },
    customerName: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const toast = useToast();
const loading = ref(true);
const result = ref(null);

const summary = computed(() => result.value?.summary ?? null);
const reports = computed(() => result.value?.reports ?? []);

const genuineRatio = computed(() => summary.value?.success_ratio ?? 0);
const fraudRatio = computed(() => {
    const total = summary.value?.total_parcel ?? 0;
    if (!total) return 0;
    return Math.round(((summary.value?.cancelled_parcel ?? 0) / total) * 100);
});

const courierRows = computed(() => {
    if (!result.value) return [];

    return Object.entries(result.value)
        .filter(([key, value]) => key !== 'summary' && key !== 'reports' && value && typeof value === 'object')
        .map(([key, stats]) => ({
            key,
            name: stats.name ?? key,
            logo: stats.logo ?? null,
            total: stats.total_parcel ?? 0,
            success: stats.success_parcel ?? 0,
            cancel: stats.cancelled_parcel ?? 0,
            ratio: stats.success_ratio ?? 0,
        }));
});

const load = async () => {
    loading.value = true;
    try {
        const { data } = await Axios.get(BDCOURIER_CHECK_FRAUD_API, { params: { phone: props.phone } });
        result.value = data;
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Failed to check fraud status');
        emit('close');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    load();
});
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" title="🛡️ BD Courier Fraud Check" :show-footer="false"
        @close="$emit('close')">
        <template #body>
            <div class="mb-3 text-muted">
                <strong class="text-dark">{{ customerName || 'Customer' }}</strong> — {{ phone }}
            </div>

            <div v-if="loading" class="text-center py-5">
                Checking...
            </div>

            <template v-else-if="result">
                <div v-if="summary" class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="fraud-summary-card fraud-summary-card--genuine">
                            <div class="fraud-summary-label">
                                <span class="fraud-dot fraud-dot--success"></span>
                                Genuine Probability
                            </div>
                            <div class="fraud-summary-value text-success">{{ genuineRatio }}%</div>
                            <div class="fraud-summary-sub">
                                Successful Deliveries: {{ summary.success_parcel ?? 0 }} / {{ summary.total_parcel ?? 0 }}
                            </div>
                            <div class="fraud-progress">
                                <div class="fraud-progress-bar bg-success" :style="{ width: genuineRatio + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fraud-summary-card fraud-summary-card--fraud">
                            <div class="fraud-summary-label">
                                <span class="fraud-dot fraud-dot--danger"></span>
                                Fraud Probability
                            </div>
                            <div class="fraud-summary-value text-danger">{{ fraudRatio }}%</div>
                            <div class="fraud-summary-sub">
                                Cancelled Orders: {{ summary.cancelled_parcel ?? 0 }} / {{ summary.total_parcel ?? 0 }}
                            </div>
                            <div class="fraud-progress">
                                <div class="fraud-progress-bar bg-danger" :style="{ width: fraudRatio + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="courierRows.length" class="fraud-breakdown mb-4">
                    <div class="fraud-breakdown-title">Courier Breakdown</div>
                    <hr class="mt-2 mb-3" />

                    <div class="row g-3">
                        <div v-for="row in courierRows" :key="row.key" class="col-md-4">
                            <div class="fraud-courier-card">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <img v-if="row.logo" :src="row.logo" :alt="row.name" class="fraud-courier-logo" />
                                    <span class="fw-bold fs-6">{{ row.name }}</span>
                                </div>

                                <div class="d-flex align-items-baseline justify-content-between">
                                    <span class="text-muted text-uppercase fs-6">Success</span>
                                    <span class="fw-bold text-success">
                                        {{ row.success }} <span class="fs-6 fw-normal">/ {{ row.total }}</span>
                                    </span>
                                </div>
                                <div class="fraud-progress fraud-progress--sm">
                                    <div class="fraud-progress-bar bg-success" :style="{ width: row.ratio + '%' }"></div>
                                </div>

                                <div class="d-flex align-items-baseline justify-content-between mt-2">
                                    <span class="text-muted text-uppercase fs-6">Cancelled</span>
                                    <span class="fw-bold text-danger">{{ row.cancel }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="reports.length" class="fraud-reports">
                    <div class="fraud-breakdown-title text-danger">Merchant-Reported Fraud</div>
                    <hr class="mt-2 mb-3" />

                    <div v-for="report in reports" :key="report.id" class="fraud-report-row">
                        <img v-if="report.courierLogo" :src="report.courierLogo" :alt="report.courierName" class="fraud-courier-logo" />
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-6">{{ report.name }} <span class="text-muted fw-normal">— {{ report.courierName }}</span></div>
                            <div class="text-muted fs-6">{{ report.details }}</div>
                        </div>
                        <div class="text-muted fs-6 text-nowrap">{{ report.created_at }}</div>
                    </div>
                </div>
            </template>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.fraud-summary-card {
    border-radius: 12px;
    padding: 1.25rem;
    height: 100%;

    &--genuine {
        background: linear-gradient(135deg, rgba(25, 135, 84, 0.08), rgba(25, 135, 84, 0.02));
    }

    &--fraud {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.08), rgba(220, 53, 69, 0.02));
    }
}

.fraud-summary-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.fraud-summary-value {
    font-size: 2.5rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.fraud-summary-sub {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.75rem;
}

.fraud-progress {
    height: 6px;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.06);
    overflow: hidden;

    &--sm {
        height: 4px;
        margin-top: 0.35rem;
    }
}

.fraud-progress-bar {
    height: 100%;
    border-radius: 999px;
    transition: width 0.3s ease;
}

.fraud-breakdown-title {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #6f42c1;
}

.fraud-courier-card {
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    padding: 1rem;
    height: 100%;
}

.fraud-courier-logo {
    height: 20px;
    width: auto;
    object-fit: contain;
}

.fraud-report-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);

    &:last-child {
        border-bottom: none;
    }
}

.fraud-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;

    &--success {
        background: #198754;
    }

    &--danger {
        background: #dc3545;
    }
}
</style>
