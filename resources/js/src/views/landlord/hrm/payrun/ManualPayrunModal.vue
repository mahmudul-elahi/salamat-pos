<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { Plus, Trash } from "@iconoir/vue";
import Axios from "@/services/axios";
import {
    HRM_DEPARTMENTS,
    HRM_EMPLOYEES,
    HRM_BENEFICIARIES,
    HRM_DEFAULT_BENEFICIARY_VALUES,
    HRM_PAYRUN_RUN_MANUAL,
} from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: { type: String, default: 'manual-payrun-modal' },
});

const emit = defineEmits(["close", "success"]);

const { t } = useI18n();
const toast = useToast();

const submitting = ref(false);
const errors = ref({});

// ── lists ──────────────────────────────────────────────────────────────
const allBeneficiaries = ref([]);   // for the "add" dropdown

const months = [
    { id: 1, name: 'January' }, { id: 2, name: 'February' }, { id: 3, name: 'March' },
    { id: 4, name: 'April' }, { id: 5, name: 'May' }, { id: 6, name: 'June' },
    { id: 7, name: 'July' }, { id: 8, name: 'August' }, { id: 9, name: 'September' },
    { id: 10, name: 'October' }, { id: 11, name: 'November' }, { id: 12, name: 'December' },
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 6 }, (_, i) => ({ id: currentYear - i, name: String(currentYear - i) }));

const considerTypeOptions = [
    { id: 'hour', name: t('hour') },
    { id: 'daily_log', name: t('daily_log') },
    { id: 'none', name: t('none') },
];

const periodOptions = [
    { id: 'monthly', name: t('monthly') },
    { id: 'customized', name: t('customized') },
];

// ── form state ─────────────────────────────────────────────────────────
const formData = ref({
    department_ids: [],
    employee_ids: [],
    payrun_period: 'monthly',
    month: new Date().getMonth() + 1,
    year: currentYear,
    start_date: null,
    end_date: null,
    consider_type: 'hour',
    consider_overtime: true,
    note: null,
    beneficiary_values: [],   // [{ beneficiary_id, name, type, amount, is_percentage }]
});

// ── add-beneficiary row state ──────────────────────────────────────────
const addRow = ref({ beneficiary_id: null, amount: '', is_percentage: false });
const addRowError = ref('');

// ── computed ───────────────────────────────────────────────────────────
const showOvertimeOption = computed(() => formData.value.consider_type !== 'none');
const isMonthly = computed(() => formData.value.payrun_period === 'monthly');

const employeeFetchUrl = computed(() => {
    if (!formData.value.department_ids.length) return HRM_EMPLOYEES;
    const params = formData.value.department_ids.map(id => `department_id[]=${id}`).join('&');
    return `${HRM_EMPLOYEES}?${params}`;
});

const allowances = computed(() =>
    formData.value.beneficiary_values.filter(b => b.type === 'allowance')
);
const deductions = computed(() =>
    formData.value.beneficiary_values.filter(b => b.type === 'deduction')
);

// beneficiaries not yet added (for the add-row dropdown)
const availableToAdd = computed(() => {
    const usedIds = new Set(formData.value.beneficiary_values.map(b => b.beneficiary_id));
    return allBeneficiaries.value.filter(b => !usedIds.has(b.id));
});

// ── watchers ───────────────────────────────────────────────────────────
watch(() => formData.value.consider_type, (val) => {
    if (val === 'none') formData.value.consider_overtime = false;
    else formData.value.consider_overtime = true;
});

watch(() => formData.value.department_ids, () => {
    formData.value.employee_ids = [];
});

// ── data fetch ─────────────────────────────────────────────────────────
const loadData = async () => {
    const [benefRes, defaultsRes] = await Promise.all([
        Axios.get(HRM_BENEFICIARIES, { params: { per_page: 100 } }),
        Axios.get(HRM_DEFAULT_BENEFICIARY_VALUES),
    ]);

    allBeneficiaries.value = benefRes.data.data ?? [];

    const defaults = defaultsRes.data;
    const buildRow = (item, type) => ({
        beneficiary_id: item.beneficiary?.id ?? item.beneficiary_id,
        name: item.beneficiary?.name ?? item.name ?? '',
        type,
        amount: item.amount,
        is_percentage: !!item.is_percentage,
    });

    formData.value.beneficiary_values = [
        ...(defaults.allowances ?? []).map(i => buildRow(i, 'allowance')),
        ...(defaults.deductions ?? []).map(i => buildRow(i, 'deduction')),
    ];
};

// ── beneficiary table actions ──────────────────────────────────────────
const removeBeneficiary = (beneficiaryId) => {
    formData.value.beneficiary_values = formData.value.beneficiary_values.filter(
        b => b.beneficiary_id !== beneficiaryId
    );
};

const addBeneficiary = () => {
    addRowError.value = '';

    if (!addRow.value.beneficiary_id) {
        addRowError.value = t('please_select_beneficiary');
        return;
    }
    if (addRow.value.amount === '' || isNaN(Number(addRow.value.amount)) || Number(addRow.value.amount) < 0) {
        addRowError.value = t('please_enter_valid_amount');
        return;
    }

    const beneficiary = allBeneficiaries.value.find(b => b.id == addRow.value.beneficiary_id);
    if (!beneficiary) return;

    formData.value.beneficiary_values.push({
        beneficiary_id: beneficiary.id,
        name: beneficiary.name,
        type: beneficiary.type,
        amount: Number(addRow.value.amount),
        is_percentage: addRow.value.is_percentage,
    });

    addRow.value = { beneficiary_id: null, amount: '', is_percentage: false };
};

// ── submit ─────────────────────────────────────────────────────────────
const submit = () => {
    errors.value = {};
    submitting.value = true;

    const payload = {
        department_ids: formData.value.department_ids,
        employee_ids: formData.value.employee_ids.length ? formData.value.employee_ids : undefined,
        payrun_period: formData.value.payrun_period,
        consider_type: formData.value.consider_type,
        consider_overtime: formData.value.consider_overtime,
        note: formData.value.note || undefined,
        beneficiary_values: formData.value.beneficiary_values.map(b => ({
            beneficiary_id: b.beneficiary_id,
            amount: b.amount,
            is_percentage: b.is_percentage,
        })),
    };

    if (formData.value.payrun_period === 'monthly') {
        payload.month = formData.value.month;
        payload.year = formData.value.year;
    } else {
        payload.start_date = formData.value.start_date;
        payload.end_date = formData.value.end_date;
    }

    Axios.post(HRM_PAYRUN_RUN_MANUAL, payload)
        .then((response) => {
            toast.success(response.data.message);
            emit('success');
        })
        .catch(({ response }) => {
            if (response?.status === 422) {
                errors.value = response.data.errors ?? {};
            }
            toast.error(response?.data?.message ?? 'Something went wrong');
        })
        .finally(() => {
            submitting.value = false;
        });
};

onMounted(loadData);
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="extra-large" :title="$t('manual_payrun')"
        :save-button-label="$t('run')" :preloader="submitting" @submit="submit" @close="$emit('close')">
        <template #body>
            <div class="row g-4">

                <!-- Departments (mandatory) -->
                <div class="col-md-12">
                    <app-input type="advance-multi-select" id="department_ids"
                        :label="$t('departments')" label-required
                        list-value-field="name" list-key-field="id" :loading-length="10"
                        :fetch-url="HRM_DEPARTMENTS" :choose-label="$t('choose_departments')"
                        v-model="formData.department_ids"
                        :errors="$errors(errors, 'department_ids')" />
                </div>

                <!-- Employees (optional) -->
                <div class="col-md-12">
                    <app-input type="advance-multi-select" id="employee_ids"
                        :label="$t('employees')"
                        list-value-field="name" list-key-field="id" :loading-length="10"
                        :fetch-url="employeeFetchUrl" :choose-label="$t('choose_employees')"
                        v-model="formData.employee_ids"
                        :errors="$errors(errors, 'employee_ids')" />
                    <small class="text-muted">{{ $t('manual_payrun_employee_hint') }}</small>
                </div>

                <!-- Payrun Period -->
                <div class="col-md-4">
                    <app-input id="payrun_period" type="select" :label="$t('payrun_period')" label-required
                        :options="periodOptions" list-value-field="name"
                        v-model="formData.payrun_period" :selected-disable="false"
                        :errors="$errors(errors, 'payrun_period')" />
                </div>

                <!-- Monthly: month + year -->
                <template v-if="isMonthly">
                    <div class="col-md-4">
                        <app-input id="month" type="select" :label="$t('month')" label-required
                            :options="months" list-value-field="name"
                            v-model="formData.month" :selected-disable="false"
                            :errors="$errors(errors, 'month')" />
                    </div>
                    <div class="col-md-4">
                        <app-input id="year" type="select" :label="$t('year')" label-required
                            :options="years" list-value-field="name"
                            v-model="formData.year" :selected-disable="false"
                            :errors="$errors(errors, 'year')" />
                    </div>
                </template>

                <!-- Customized: start/end date -->
                <template v-else>
                    <div class="col-md-4">
                        <app-input id="start_date" type="date" :label="$t('start_date')" label-required
                            v-model="formData.start_date" auto-apply
                            :errors="$errors(errors, 'start_date')" />
                    </div>
                    <div class="col-md-4">
                        <app-input id="end_date" type="date" :label="$t('end_date')" label-required
                            v-model="formData.end_date" auto-apply
                            :errors="$errors(errors, 'end_date')" />
                    </div>
                </template>

                <!-- Consider Type + Overtime -->
                <div class="col-md-4">
                    <app-input id="consider_type" type="select" :label="$t('consider_type')" label-required
                        :options="considerTypeOptions" list-value-field="name"
                        v-model="formData.consider_type" :selected-disable="false"
                        :errors="$errors(errors, 'consider_type')" />
                </div>

                <div v-if="showOvertimeOption" class="col-md-4 d-flex align-items-end pb-1">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="consider_overtime"
                            v-model="formData.consider_overtime" />
                        <label class="form-check-label fw-semibold" for="consider_overtime">
                            {{ $t('consider_overtime') }}
                        </label>
                    </div>
                </div>

                <!-- ── Beneficiary Values ── -->
                <div class="col-md-12">
                    <p class="fw-semibold mb-3">{{ $t('beneficiaries') }}</p>

                    <!-- Allowances -->
                    <p class="small text-success fw-semibold mb-2">{{ $t('earning') }}</p>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ $t('name') }}</th>
                                    <th style="width:130px">{{ $t('amount') }}</th>
                                    <th style="width:80px" class="text-center">%</th>
                                    <th style="width:44px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in allowances" :key="item.beneficiary_id">
                                    <td>{{ item.name }}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm"
                                            v-model.number="item.amount" min="0" step="0.01" />
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input"
                                            v-model="item.is_percentage" />
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                            @click="removeBeneficiary(item.beneficiary_id)">
                                            <Trash width="16" height="16" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!allowances.length">
                                    <td colspan="4" class="text-muted small text-center py-2">{{ $t('no_data_found') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Deductions -->
                    <p class="small text-danger fw-semibold mb-2">{{ $t('deduction') }}</p>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ $t('name') }}</th>
                                    <th style="width:130px">{{ $t('amount') }}</th>
                                    <th style="width:80px" class="text-center">%</th>
                                    <th style="width:44px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in deductions" :key="item.beneficiary_id">
                                    <td>{{ item.name }}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm"
                                            v-model.number="item.amount" min="0" step="0.01" />
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input"
                                            v-model="item.is_percentage" />
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                            @click="removeBeneficiary(item.beneficiary_id)">
                                            <Trash width="16" height="16" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!deductions.length">
                                    <td colspan="4" class="text-muted small text-center py-2">{{ $t('no_data_found') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Add Beneficiary Row -->
                    <div class="add-beneficiary-row p-3 rounded border">
                        <p class="small fw-semibold mb-2">{{ $t('add_beneficiary') }}</p>
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <label class="form-label small mb-1">{{ $t('beneficiary') }}</label>
                                <select class="form-select form-select-sm" v-model="addRow.beneficiary_id">
                                    <option :value="null" disabled>{{ $t('select') }}...</option>
                                    <optgroup label="Allowance">
                                        <option v-for="b in availableToAdd.filter(x => x.type === 'allowance')"
                                            :key="b.id" :value="b.id">{{ b.name }}</option>
                                    </optgroup>
                                    <optgroup label="Deduction">
                                        <option v-for="b in availableToAdd.filter(x => x.type === 'deduction')"
                                            :key="b.id" :value="b.id">{{ b.name }}</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-auto" style="width:130px">
                                <label class="form-label small mb-1">{{ $t('amount') }}</label>
                                <input type="number" class="form-control form-control-sm"
                                    v-model="addRow.amount" min="0" step="0.01" placeholder="0" />
                            </div>
                            <div class="col-auto d-flex align-items-end gap-1 pb-1">
                                <input type="checkbox" class="form-check-input" id="add_row_pct"
                                    v-model="addRow.is_percentage" />
                                <label class="form-check-label small" for="add_row_pct">%</label>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                    @click="addBeneficiary">
                                    <Plus width="14" height="14" />
                                    {{ $t('add') }}
                                </button>
                            </div>
                        </div>
                        <p v-if="addRowError" class="text-danger small mt-1 mb-0">{{ addRowError }}</p>
                    </div>
                </div>

                <!-- Note -->
                <div class="col-md-12">
                    <label class="form-label mb-2">{{ $t('note') }}</label>
                    <textarea class="form-control" rows="3" v-model="formData.note"
                        :placeholder="$t('add_payslip_note')"></textarea>
                </div>

            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.add-beneficiary-row {
    background: #f8fafc;
    border-color: #e2e8f0 !important;
}
</style>
