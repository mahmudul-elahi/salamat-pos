<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import { CheckCircle, Refresh, WarningTriangle } from '@iconoir/vue';
import Axios from '@/services/axios/index.js';
import { LANDLORD_LEAVE_SETTINGS, LANDLORD_LEAVE_SETTINGS_ASSIGN } from '@/services/endpoints/landlord.js';
import { HRM_EMPLOYEE_STATUSES } from '@/services/endpoints/hrm.js';
import ConfirmationModal from '@/views/global/modal/ConfirmationModal.vue';
import usePermission from '@/composable/permission/usePermission.js';

const { canAccess } = usePermission();
const toast = useToast();
const pageLoader = ref(false);
const preloader = ref(false);
const errors = ref({});

const MONTHS = [
    { id: '1', value: 'January' },
    { id: '2', value: 'February' },
    { id: '3', value: 'March' },
    { id: '4', value: 'April' },
    { id: '5', value: 'May' },
    { id: '6', value: 'June' },
    { id: '7', value: 'July' },
    { id: '8', value: 'August' },
    { id: '9', value: 'September' },
    { id: '10', value: 'October' },
    { id: '11', value: 'November' },
    { id: '12', value: 'December' },
];

const formData = ref({
    start_month: '1',
    statuses_for_paid_leave: [],
    statuses_for_unpaid_leave: [],
});

const fetchSettings = () => {
    pageLoader.value = true;
    Axios.get(LANDLORD_LEAVE_SETTINGS)
        .then(({ data }) => {
            formData.value = {
                start_month: String(data.start_month ?? '1'),
                statuses_for_paid_leave: data.statuses_for_paid_leave ?? [],
                statuses_for_unpaid_leave: data.statuses_for_unpaid_leave ?? [],
            };
        })
        .finally(() => { pageLoader.value = false; });
};

const assigning = ref(false);
const showAssignConfirm = ref(false);

const assignLeaves = () => {
    showAssignConfirm.value = false;
    assigning.value = true;
    Axios.post(LANDLORD_LEAVE_SETTINGS_ASSIGN)
        .then(({ data }) => toast.success(data.message))
        .catch(({ response }) => toast.error(response?.data?.message ?? 'Failed to dispatch job.'))
        .finally(() => { assigning.value = false; });
};

const submit = () => {
    preloader.value = true;
    errors.value = {};

    Axios.post(LANDLORD_LEAVE_SETTINGS, formData.value)
        .then(({ data }) => toast.success(data.message))
        .catch(({ response }) => {
            if (response?.status === 422) errors.value = response.data.errors;
            else toast.error(response?.data?.message);
        })
        .finally(() => { preloader.value = false; });
};

onMounted(fetchSettings);
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('leave_settings')" />

        <app-loader v-if="pageLoader" />

        <form v-else @submit.prevent="submit" class="ls-form">

            <!-- Leave Year Start Month -->
            <div class="ls-section">
                <div class="ls-section-label">
                    <p class="ls-section-title">Leave Year</p>
                    <p class="ls-section-sub">Define when the leave year begins for this organisation</p>
                </div>
                <div class="ls-section-body">
                    <app-input id="start_month" type="select" label="Start Month" :options="MONTHS" list-key-field="id"
                        list-value-field="value" v-model="formData.start_month"
                        :errors="$errors(errors, 'start_month')" />
                </div>
            </div>

            <div class="ls-divider"></div>

            <!-- Paid Leave Statuses -->
            <div class="ls-section">
                <div class="ls-section-label">
                    <p class="ls-section-title">Paid Leave Eligibility</p>
                    <p class="ls-section-sub">Employee statuses that qualify for paid leave</p>
                </div>
                <div class="ls-section-body">
                    <app-input id="statuses_for_paid_leave" type="advance-multi-select" label="Employee Statuses"
                        list-value-field="name" :loading-length="10" :fetch-url="HRM_EMPLOYEE_STATUSES"
                        choose-label="Choose statuses for paid leave" v-model="formData.statuses_for_paid_leave"
                        :errors="$errors(errors, 'statuses_for_paid_leave')" />
                </div>
            </div>

            <div class="ls-divider"></div>

            <!-- Unpaid Leave Statuses -->
            <div class="ls-section">
                <div class="ls-section-label">
                    <p class="ls-section-title">Unpaid Leave Eligibility</p>
                    <p class="ls-section-sub">Employee statuses that qualify for unpaid leave</p>
                </div>
                <div class="ls-section-body">
                    <app-input id="statuses_for_unpaid_leave" type="advance-multi-select" label="Employee Statuses"
                        list-value-field="name" :loading-length="10" :fetch-url="HRM_EMPLOYEE_STATUSES"
                        choose-label="Choose statuses for unpaid leave" v-model="formData.statuses_for_unpaid_leave"
                        :errors="$errors(errors, 'statuses_for_unpaid_leave')" />
                </div>
            </div>

            <!-- Footer: Save + Assign Leaves -->
            <div class="ls-footer">
                <button type="submit" class="btn ls-save-btn d-flex align-items-center gap-2" :disabled="preloader">
                    <app-button-loader v-if="preloader" />
                    <CheckCircle v-else />
                    Save Settings
                </button>

                <div class="ls-assign-wrap" v-if="canAccess('assign_leave_settings')">
                    <div class="ls-assign-info">
                        <p class="ls-assign-title">Assign Leaves to Employees</p>
                        <p class="ls-assign-sub">Dispatches a background job that creates or refreshes leave
                            entitlements for all eligible employees based on the settings above. Records manually edited
                            will not be overwritten.</p>
                    </div>
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2"
                        :disabled="assigning" @click="showAssignConfirm = true">
                        <app-button-loader v-if="assigning" />
                        <Refresh v-else />
                        Assign Leaves
                    </button>
                </div>
            </div>

        </form>
    </div>

    <confirmation-modal v-if="showAssignConfirm" modal-id="assign-leaves-confirm-modal" :icon="WarningTriangle"
        modal-class="text-warning" title="Assign Leaves to All Employees?"
        message="This will dispatch a background job to create or refresh leave entitlements for <strong>all eligible employees</strong>. Are you sure you want to proceed?"
        first-button-name="Yes, Assign" first-button-class="btn btn-warning" second-button-name="Cancel"
        second-button-class="btn btn-outline-secondary" :preloader="assigning" @confirm="assignLeaves"
        @cancel="showAssignConfirm = false" />
</template>

<style scoped lang="scss">
.ls-form {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

/* ── Section row ──────────────────────────────── */
.ls-section {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 32px;
    padding: 28px 32px;
    align-items: start;

    @media (max-width: 768px) {
        grid-template-columns: 1fr;
        gap: 12px;
        padding: 20px;
    }
}

.ls-section-label {
    padding-top: 4px;
}

.ls-section-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}

.ls-section-sub {
    font-size: 12px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
    line-height: 1.5;
}

.ls-section-body {
    max-width: 480px;
}

/* ── Divider ──────────────────────────────────── */
.ls-divider {
    height: 1px;
    background: var(--border-color);
    margin: 0 32px;

    @media (max-width: 768px) {
        margin: 0 20px;
    }
}

/* ── Footer ───────────────────────────────────── */
.ls-footer {
    padding: 24px 32px;
    border-top: 1px solid var(--border-color);
    background: var(--dashboard-bg);
    display: flex;
    flex-direction: column;
    gap: 20px;

    @media (max-width: 768px) {
        padding: 16px 20px;
    }
}

.ls-assign-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px 20px;

    @media (max-width: 768px) {
        flex-direction: column;
        align-items: flex-start;
    }
}

.ls-assign-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}

.ls-assign-sub {
    font-size: 12px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
    line-height: 1.5;
    max-width: 540px;
}

/* ── Save button ──────────────────────────────── */
.ls-save-btn {
    background: linear-gradient(135deg, var(--primary-color, #4f46e5) 0%, color-mix(in srgb, var(--primary-color, #4f46e5) 75%, #7c3aed) 100%);
    color: #fff;
    border: none;
    padding: 10px 24px;
    font-weight: 600;
    font-size: 14px;
    border-radius: 10px;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
    transition: box-shadow 0.2s ease, transform 0.15s ease, opacity 0.15s ease;

    &:hover:not(:disabled) {
        box-shadow: 0 6px 20px rgba(79, 70, 229, 0.45);
        transform: translateY(-1px);
        color: #fff;
    }

    &:active:not(:disabled) {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
    }

    &:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }
}
</style>
