<script setup>
import { ref, computed, onMounted } from "vue";
import Axios from "@/services/axios/index.js";
import { ORDER_SSLCOMMERZ_CHECK_STATUS } from "@/services/endpoints/Ecommerce.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import useEmitter from "@/composable/emitter/useEmitter.js";

const props = defineProps({
    modalId: String,
    order: Object,
    tableId: String,
});
const emit = defineEmits(["close"]);
const emitter = useEmitter();


const loading = ref(true);
const result = ref(null);
const errorMessage = ref(null);

const checkStatus = async () => {
    loading.value = true;
    errorMessage.value = null;
    try {
        const { data } = await Axios.get(ORDER_SSLCOMMERZ_CHECK_STATUS, {
            params: { transaction_id: props.order?.transaction_id },
        });
        result.value = data;
        emitter.emit('reload-' + props.tableId);
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to check payment status';
    } finally {
        loading.value = false;
    }
};

// The real SSLCommerz transaction-status API nests the actual record under data.element[0].
const transaction = computed(() => result.value?.data?.element?.[0] ?? null);

const statusBadgeClass = (status) => {
    if (['VALID', 'VALIDATED'].includes(status)) return 'bg-success';
    if (status === 'FAILED') return 'bg-danger';
    if (status === 'PENDING' || status === 'PROCESSING') return 'bg-warning';
    return 'bg-secondary';
};

onMounted(checkStatus);
</script>

<template>
    <app-modal :modal-id="modalId" title="SSLCommerz Payment Status" modal-size="large" :show-footer="false"
        @close="$emit('close')">
        <template #body>
            <app-loader v-if="loading" />

            <div v-else-if="errorMessage" class="text-center py-4">
                <p class="text-danger fw-semibold mb-0">{{ errorMessage }}</p>
            </div>

            <div v-else-if="result">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge-square-light" :class="result.success ? 'bg-success' : 'bg-danger'">
                        {{ result.success ? 'Success' : 'Failed' }}
                    </span>
                    <span class="fw-semibold">{{ result.message }}</span>
                </div>

                <!-- Nicely grouped transaction summary -->
                <template v-if="transaction">
                    <div class="ssl-amount-card mb-3">
                        <div>
                            <p class="ssl-label mb-1">Amount Paid</p>
                            <p class="ssl-amount mb-0">{{ numberWithCurrencySymbol(transaction.amount) }}</p>
                        </div>
                        <span class="badge-square-light" :class="statusBadgeClass(transaction.status)">
                            {{ transaction.status }}
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="ssl-label mb-1">Transaction ID</p>
                            <p class="ssl-value mb-0">{{ transaction.tran_id }}</p>
                        </div>
                        <div class="col-md-6" v-if="transaction.bank_tran_id">
                            <p class="ssl-label mb-1">Bank Transaction ID</p>
                            <p class="ssl-value mb-0">{{ transaction.bank_tran_id }}</p>
                        </div>
                        <div class="col-md-6" v-if="transaction.val_id">
                            <p class="ssl-label mb-1">Validation ID</p>
                            <p class="ssl-value mb-0">{{ transaction.val_id }}</p>
                        </div>
                        <div class="col-md-6" v-if="transaction.tran_date">
                            <p class="ssl-label mb-1">Transaction Date</p>
                            <p class="ssl-value mb-0">{{ transaction.tran_date }}</p>
                        </div>
                        <div class="col-md-6" v-if="transaction.validated_on">
                            <p class="ssl-label mb-1">Validated On</p>
                            <p class="ssl-value mb-0">{{ transaction.validated_on }}</p>
                        </div>
                        <div class="col-md-6" v-if="transaction.card_type">
                            <p class="ssl-label mb-1">Payment Method</p>
                            <p class="ssl-value mb-0">{{ transaction.card_type }}</p>
                        </div>
                        <div class="col-md-6" v-if="transaction.bank_gw">
                            <p class="ssl-label mb-1">Gateway</p>
                            <p class="ssl-value mb-0">{{ transaction.bank_gw }}</p>
                        </div>
                        <div class="col-md-6" v-if="transaction.store_amount">
                            <p class="ssl-label mb-1">Store Amount (after fee)</p>
                            <p class="ssl-value mb-0">{{ numberWithCurrencySymbol(transaction.store_amount) }}</p>
                        </div>
                        <div class="col-md-6" v-if="transaction.risk_title">
                            <p class="ssl-label mb-1">Risk Level</p>
                            <p class="ssl-value mb-0">
                                <span class="badge-square-light"
                                    :class="transaction.risk_level == 0 ? 'bg-success' : 'bg-warning'">
                                    {{ transaction.risk_title }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6" v-if="transaction.discount_amount > 0">
                            <p class="ssl-label mb-1">Discount Applied</p>
                            <p class="ssl-value mb-0">
                                {{ numberWithCurrencySymbol(transaction.discount_amount) }}
                                <span v-if="transaction.discount_percentage" class="text-muted small">
                                    ({{ transaction.discount_percentage }}%)
                                </span>
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Fallback: unexpected shape, just dump whatever came back -->
                <div v-else-if="result.data" class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody>
                            <tr v-for="(value, key) in result.data" :key="key">
                                <th class="text-nowrap">{{ key }}</th>
                                <td class="text-break">{{ typeof value === 'object' ? JSON.stringify(value) : value }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary mt-3" @click="checkStatus">
                    Re-check
                </button>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.ssl-amount-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-radius: 12px;
    background: #f8f9fb;
    border: 1px solid #eef0f4;
}

.ssl-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #8a94a6;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.ssl-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #3d4a5c;
    word-break: break-all;
}

.ssl-amount {
    font-size: 1.4rem;
    font-weight: 800;
    color: #16a34a;
}
</style>
