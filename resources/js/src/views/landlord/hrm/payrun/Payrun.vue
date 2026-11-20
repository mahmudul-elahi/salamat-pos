<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { SendDiagonal, CheckCircle, Trash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import router from "@/router/index.js";
import Axios from "@/services/axios";
import { HRM_PAYRUNS } from "@/services/endpoints/hrm.js";
import ConfirmationModal from "@/views/global/modal/ConfirmationModal.vue";
import DefaultPayrunModal from "./DefaultPayrunModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const toast = useToast();

const loading = ref(false);
const payruns = ref([]);
const isModalActive = ref(false);
let pollTimer = null;

const statusBadgeClass = {
    processing: 'badge bg-warning text-dark',
    generated: 'badge bg-success',
    failed: 'badge bg-danger',
    sent: 'badge bg-info text-dark',
};

const fetchPayruns = () => {
    loading.value = true;

    Axios.get(HRM_PAYRUNS, { params: { per_page: 100 } })
        .then(({ data }) => {
            payruns.value = data.data ?? [];
            const stillProcessing = payruns.value.some((p) => p.status === 'processing');

            if (stillProcessing && !pollTimer) {
                pollTimer = setInterval(fetchPayruns, 3000);
            } else if (!stillProcessing && pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        })
        .finally(() => {
            loading.value = false;
        });
};

const onRunSuccess = () => {
    isModalActive.value = false;
    fetchPayruns();
};

const sendAllTarget = ref(null);
const sendAll = () => {
    Axios.post(`${HRM_PAYRUNS}/${sendAllTarget.value}/send-all`)
        .then((response) => {
            toast.success(response.data.message);
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message);
        })
        .finally(() => {
            sendAllTarget.value = null;
        });
};

const deleteTarget = ref(null);
const deletePayrun = () => {
    Axios.delete(`${HRM_PAYRUNS}/${deleteTarget.value}`)
        .then((response) => {
            toast.success(response.data.message);
            payruns.value = payruns.value.filter((p) => p.id !== deleteTarget.value);
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message ?? 'Failed to delete payrun');
        })
        .finally(() => {
            deleteTarget.value = null;
        });
};

const goToPayslips = (payrunId) => {
    router.push({ path: '/hrm/payslips', query: { payrun_id: payrunId } });
};

onMounted(fetchPayruns);
onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <app-breadcrumb :page-title="$t('payrun_list')" />
            <div class="d-flex gap-2">
                <button v-if="canAccess('run_default_payruns')" type="button" class="btn btn-outline-primary"
                    @click="router.push('/hrm/payruns/manual')">
                    {{ $t('manual_payrun') }}
                </button>
                <button v-if="canAccess('run_default_payruns')" type="button" class="btn btn-primary"
                    @click="isModalActive = true">
                    {{ $t('default_payrun') }}
                </button>
            </div>
        </div>

        <app-loader v-if="loading && !payruns.length" />

        <div v-else class="row g-4">
            <div v-for="payrun in payruns" :key="payrun.id" class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-default h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="fw-bold mb-0">{{ payrun.date_range }}</h6>
                            <div class="d-flex gap-1">
                                <button v-if="canAccess('send_all_payruns')" type="button"
                                    class="btn btn-sm btn-primary rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 32px; height: 32px"
                                    :title="$t('send_all_payslips')" @click="sendAllTarget = payrun.id">
                                    <SendDiagonal width="16" height="16" />
                                </button>
                                <button type="button"
                                    class="btn btn-sm btn-danger rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 32px; height: 32px"
                                    title="Delete Payrun" @click="deleteTarget = payrun.id">
                                    <Trash width="16" height="16" />
                                </button>
                            </div>
                        </div>

                        <div class="bg-light rounded p-2 mb-3 border-start border-4 border-primary">
                            <div class="d-flex align-items-center gap-2 text-success small mb-1">
                                <CheckCircle width="14" height="14" />
                                {{ $t('includes_payslips', { count: payrun.payslips_count }) }}
                            </div>
                            <div class="progress" style="height: 6px">
                                <div class="progress-bar" :style="{ width: payrun.included_percent + '%' }"></div>
                            </div>
                            <div class="text-end small text-muted mt-1">
                                {{ payrun.included_percent }}% {{ $t('included') }}
                            </div>
                        </div>

                        <p class="fw-semibold small mb-1">{{ $t('configuration') }}</p>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">{{ $t('payrun') }}:</span>
                            <span>{{ payrun.payrun_period ?? '-' }} - {{ payrun.consider_type ?? '-' }} Based</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">{{ $t('method') }}:</span>
                            <span>{{ payrun.method ?? '-' }}</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">{{ $t('followed_by') }}:</span>
                            <span>{{ payrun.type }}</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-3">
                            <span class="text-muted">ID:</span>
                            <span>{{ payrun.uuid }}</span>
                        </div>

                        <p class="fw-semibold small mb-1">{{ $t('status') }}</p>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">{{ $t('generated_on') }}:</span>
                            <span>{{ payrun.generated_on }}</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-3">
                            <span class="text-muted">{{ $t('status') }}:</span>
                            <span :class="statusBadgeClass[payrun.status] ?? 'badge bg-light text-dark'">
                                {{ payrun.status }}
                            </span>
                        </div>

                        <button type="button" class="btn btn-outline-primary w-100 rounded-pill"
                            @click="goToPayslips(payrun.id)">
                            {{ $t('view_payslips') }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!loading && !payruns.length" class="col-12 text-center text-muted py-5">
                {{ $t('no_data_found') }}
            </div>
        </div>

        <default-payrun-modal v-if="isModalActive" @close="isModalActive = false" @success="onRunSuccess" />

<confirmation-modal v-if="sendAllTarget" modal-id="send-all-payslips-modal"
            :title="$t('send_all_payslips')" :message="$t('confirm_send_all_payslips')"
            @cancel="sendAllTarget = null" @confirm="sendAll" />

        <confirmation-modal v-if="deleteTarget" modal-id="delete-payrun-modal"
            title="Delete Payrun"
            message="Are you sure you want to delete this payrun? All payslips and their items will be permanently deleted."
            @cancel="deleteTarget = null" @confirm="deletePayrun" />
    </div>
</template>

<style scoped lang="scss"></style>
