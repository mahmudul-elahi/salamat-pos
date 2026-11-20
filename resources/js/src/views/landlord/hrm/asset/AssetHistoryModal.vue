<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import Axios from "@/services/axios/index.js";
import { HRM_ASSETS } from "@/services/endpoints/hrm.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { useTimezone } from "@/utils/useTimezone.js";

const props = defineProps({
    modalId: String,
    asset: Object,
});

const emit = defineEmits(["close"]);
const { t } = useI18n();
const { formatDateOnly } = useTimezone();

const pageLoader = ref(false);
const history = ref(null);

const conditionLabels = {
    good: 'Good — back in circulation',
    needs_repair: 'Needs Repair',
    damaged: 'Damaged',
    lost: 'Lost / Not Returned',
};

const fetchHistory = async () => {
    pageLoader.value = true;
    try {
        const { data } = await Axios.get(`${HRM_ASSETS}/${props.asset.id}/history`);
        history.value = data;
    } finally {
        pageLoader.value = false;
    }
};

onMounted(fetchHistory);
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="$t('asset_history')" :show-footer="false"
        @close="emit('close')">
        <template #body>
            <app-loader v-if="pageLoader" />
            <template v-else-if="history">
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="text-muted text-uppercase small">{{ t('asset_name') }}</div>
                            <div class="fw-bold">{{ history.asset_name }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="text-muted text-uppercase small">{{ t('asset_code') }}</div>
                            <div class="fw-bold">{{ history.asset_code }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="text-muted text-uppercase small">{{ t('category') }}</div>
                            <div class="fw-bold">{{ history.category?.name ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="text-muted text-uppercase small">{{ t('status') }}</div>
                            <span class="badge-square-light" :class="history.status === 'assigned' ? 'bg-success' : 'bg-primary'">
                                {{ history.status === 'assigned' ? t('assigned') : t('available') }}
                            </span>
                        </div>
                    </div>
                    <div v-if="history.purchase_cost" class="col-md-3 col-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="text-muted text-uppercase small">{{ t('purchase_cost') }}</div>
                            <div class="fw-bold">{{ numberWithCurrencySymbol(history.purchase_cost) }}</div>
                        </div>
                    </div>
                    <div v-if="history.serial_number" class="col-md-3 col-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="text-muted text-uppercase small">{{ t('serial_number') }}</div>
                            <div class="fw-bold">{{ history.serial_number }}</div>
                        </div>
                    </div>
                </div>

                <h6 class="text-uppercase fw-bold mb-2">{{ t('custody_history') }}</h6>
                <hr class="mt-0" />

                <div v-if="!history.assignments?.length" class="text-center text-muted py-5">
                    {{ t('asset_never_assigned') }}
                </div>

                <div v-else class="d-flex flex-column gap-2">
                    <div v-for="assignment in history.assignments" :key="assignment.id"
                        class="d-flex justify-content-between align-items-start p-3 rounded-3 bg-light">
                        <div>
                            <div class="fw-bold">{{ assignment.employee?.name ?? '-' }}</div>
                            <div class="text-muted small" v-if="assignment.note">{{ assignment.note }}</div>
                            <div class="text-muted small" v-if="assignment.return_note">{{ assignment.return_note }}</div>
                        </div>
                        <div class="text-end small">
                            <div>{{ formatDateOnly(assignment.assigned_date) }}
                                &rarr;
                                {{ assignment.returned_date ? formatDateOnly(assignment.returned_date) : t('present') }}
                            </div>
                            <div v-if="assignment.condition" class="text-muted mt-1">
                                {{ conditionLabels[assignment.condition] ?? assignment.condition }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
