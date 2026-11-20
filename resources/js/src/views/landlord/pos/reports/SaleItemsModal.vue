<script setup>
import { ref, computed, onMounted } from 'vue';
import Axios from "@/services/axios/index.js";
import { SALES } from "@/services/endpoints/landlord.js";
import { useI18n } from "vue-i18n";
import { numberWithCurrencySymbol } from "@/utils/helper.js";

const props = defineProps({
    modalId: String,
    saleId: [String, Number],
});

const emit = defineEmits(['close']);
const { t } = useI18n();

const isLoading = ref(false);
const items = ref([]);
const summary = ref({});

const fetchItems = async () => {
    try {
        isLoading.value = true;
        const { data } = await Axios.get(`${SALES}/${props.saleId}`);
        items.value = data.items || [];
        summary.value = {
            invoiceNumber: data.full_invoice_number,
            customerName: data.contact?.name,
            date: data.date,
            status: data.status,
            grandTotal: data.grand_total,
        };
    } catch {
        items.value = [];
    } finally {
        isLoading.value = false;
    }
};

const lineTotal = (item) => {
    const base = item.price * item.quantity;
    if (item.discount_type === 'percent') {
        return base - (base * (item.discount_amount || 0) / 100);
    }
    return base - (item.discount_amount || 0);
};

const totalQuantity = computed(() => items.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0));
const totalAmount = computed(() => items.value.reduce((sum, item) => sum + lineTotal(item), 0));

const statusBadgeClass = computed(() => {
    if (summary.value.status === 'due') return 'bg-danger';
    if (summary.value.status === 'partially_paid') return 'bg-info';
    if (summary.value.status === 'return') return 'bg-warning';
    return 'bg-success';
});

onMounted(fetchItems);
</script>

<template>
    <app-modal :modal-id="modalId" :title="t('sold_items')" modal-size="extra-large"
        :show-footer="false" scrollable header-classes="items-modal-header" body-classes="items-modal-body"
        @close="emit('close')">
        <template v-slot:body>
            <app-loader v-if="isLoading" />
            <div v-else class="items-modal">
                <div class="summary-strip">
                    <div class="summary-cell">
                        <span class="summary-label">{{ t('invoice_number') }}</span>
                        <span class="summary-value">{{ summary.invoiceNumber || '-' }}</span>
                    </div>
                    <div class="summary-cell">
                        <span class="summary-label">{{ t('customer') }}</span>
                        <span class="summary-value">{{ summary.customerName || '-' }}</span>
                    </div>
                    <div class="summary-cell">
                        <span class="summary-label">{{ t('date') }}</span>
                        <span class="summary-value">{{ summary.date || '-' }}</span>
                    </div>
                    <div class="summary-cell">
                        <span class="summary-label">{{ t('status') }}</span>
                        <span class="badge-square-light text-capitalize" :class="statusBadgeClass">{{ summary.status }}</span>
                    </div>
                    <div class="summary-cell text-end">
                        <span class="summary-label">{{ t('total_amount') }}</span>
                        <span class="summary-value fw-bold">{{ numberWithCurrencySymbol(summary.grandTotal || 0) }}</span>
                    </div>
                </div>

                <div class="table-responsive items-table-wrap">
                    <table class="table items-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ t('name') }}</th>
                                <th>{{ t('serial_number') }}</th>
                                <th class="text-center">{{ t('quantity') }}</th>
                                <th class="text-end">{{ t('price') }}</th>
                                <th class="text-end">{{ t('discount') }}</th>
                                <th class="text-end">{{ t('total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!items.length">
                                <td colspan="6" class="text-center text-muted py-4">{{ t('no_data_found') }}</td>
                            </tr>
                            <tr v-for="item in items" :key="item.id">
                                <td>
                                    <div class="item-name">{{ item.name }}</div>
                                    <div v-if="item.attribute_values" class="item-variant">{{ item.attribute_values }}</div>
                                </td>
                                <td class="text-muted">{{ item.serial_number || '-' }}</td>
                                <td class="text-center">
                                    <span class="qty-pill">{{ item.quantity }}</span>
                                </td>
                                <td class="text-end">{{ numberWithCurrencySymbol(item.price) }}</td>
                                <td class="text-end">
                                    <span v-if="item.discount_amount" class="discount-badge">
                                        {{ item.discount_type === 'percent' ? `${item.discount_amount}%` : numberWithCurrencySymbol(item.discount_amount) }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-end fw-semibold">{{ numberWithCurrencySymbol(lineTotal(item)) }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="items.length">
                            <tr>
                                <td colspan="2" class="fw-bold">{{ t('total') }}</td>
                                <td class="text-center fw-bold">{{ totalQuantity }}</td>
                                <td></td>
                                <td></td>
                                <td class="text-end fw-bold">{{ numberWithCurrencySymbol(totalAmount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.summary-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding: 14px 18px;
    margin-bottom: 16px;
    background: var(--dashboard-bg, #f5f6fa);
    border-radius: 10px;
}

.summary-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 110px;

    &.text-end {
        margin-left: auto;
        align-items: flex-end;
    }
}

.summary-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--font-color, #6b7280);
    opacity: 0.7;
}

.summary-value {
    font-size: 14px;
    font-weight: 500;
}

.items-table-wrap {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--border-color, #ececec);
}

.items-table {
    margin-bottom: 0;

    thead tr th {
        background: var(--dashboard-bg, #f5f6fa);
        border-bottom: none;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--font-color, #6b7280);
        opacity: 0.75;
        font-weight: 600;
        padding: 12px 14px;
    }

    tbody tr {
        transition: background-color 0.15s ease;

        &:hover {
            background-color: rgba(43, 135, 104, 0.04);
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-color, #ececec);
        }

        &:last-child td {
            border-bottom: none;
        }
    }

    tfoot td {
        padding: 12px 14px;
        background: var(--dashboard-bg, #f5f6fa);
        border-top: 2px solid var(--border-color, #ececec);
    }
}

.item-name {
    font-weight: 500;
}

.item-variant {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 2px;
}

.qty-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: rgba(43, 135, 104, 0.1);
    color: var(--primary, #2b8768);
    font-weight: 600;
    font-size: 12px;
}

.discount-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(220, 53, 69, 0.08);
    color: #dc3545;
    font-size: 12px;
    font-weight: 500;
}
</style>
