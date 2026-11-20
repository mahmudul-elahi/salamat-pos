<script setup>
import { ref, computed, onMounted } from 'vue';
import Axios from "@/services/axios/index.js";
import { DAMAGE_PRODUCTS } from "@/services/endpoints/landlord.js";
import { useI18n } from "vue-i18n";

const props = defineProps({
    modalId: String,
    damageProductId: [String, Number],
});

const emit = defineEmits(['close']);
const { t } = useI18n();

const isLoading = ref(false);
const items = ref([]);
const summary = ref({});

const fetchItems = async () => {
    try {
        isLoading.value = true;
        const { data } = await Axios.get(`${DAMAGE_PRODUCTS}/${props.damageProductId}`);
        items.value = data.items || [];
        summary.value = {
            referenceNo: data.reference_no,
            warehouse: data.tenant?.name,
            date: data.damage_date,
            status: data.status,
            createdBy: data.created_by,
            approvedBy: data.approved_by,
        };
    } catch {
        items.value = [];
    } finally {
        isLoading.value = false;
    }
};

const totalQuantity = computed(() => items.value.length);

const statusBadgeClass = computed(() => ({
    draft: 'bg-warning',
    approved: 'bg-success',
    cancelled: 'bg-danger',
}[summary.value.status] || 'bg-secondary'));

onMounted(fetchItems);
</script>

<template>
    <app-modal :modal-id="modalId" title="Damaged Items" modal-size="extra-large"
        :show-footer="false" scrollable header-classes="items-modal-header" body-classes="items-modal-body"
        @close="emit('close')">
        <template v-slot:body>
            <app-loader v-if="isLoading" />
            <div v-else class="items-modal">
                <div class="summary-strip">
                    <div class="summary-cell">
                        <span class="summary-label">Reference No</span>
                        <span class="summary-value">{{ summary.referenceNo || '-' }}</span>
                    </div>
                    <div class="summary-cell">
                        <span class="summary-label">Warehouse</span>
                        <span class="summary-value">{{ summary.warehouse || '-' }}</span>
                    </div>
                    <div class="summary-cell">
                        <span class="summary-label">{{ t('date') }}</span>
                        <span class="summary-value">{{ summary.date || '-' }}</span>
                    </div>
                    <div class="summary-cell">
                        <span class="summary-label">{{ t('status') }}</span>
                        <span class="badge-square-light text-capitalize" :class="statusBadgeClass">{{ summary.status }}</span>
                    </div>
                    <div class="summary-cell">
                        <span class="summary-label">Created By</span>
                        <span class="summary-value">{{ summary.createdBy || '-' }}</span>
                    </div>
                    <div class="summary-cell text-end">
                        <span class="summary-label">Approved By</span>
                        <span class="summary-value fw-bold">{{ summary.approvedBy || 'Not approved yet' }}</span>
                    </div>
                </div>

                <div class="table-responsive items-table-wrap">
                    <table class="table items-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ t('name') }}</th>
                                <th>SKU</th>
                                <th>Variant</th>
                                <th>{{ t('serial_number') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!items.length">
                                <td colspan="4" class="text-center text-muted py-4">{{ t('no_data_found') }}</td>
                            </tr>
                            <tr v-for="item in items" :key="item.id">
                                <td>
                                    <div class="item-name">{{ item.product_name || '-' }}</div>
                                </td>
                                <td class="text-muted">{{ item.sku || '-' }}</td>
                                <td class="text-muted">{{ item.attributes || '-' }}</td>
                                <td class="text-muted">{{ item.serial_number || '-' }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="items.length">
                            <tr>
                                <td colspan="3" class="fw-bold">{{ t('total') }}</td>
                                <td class="fw-bold">{{ totalQuantity }}</td>
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
</style>
