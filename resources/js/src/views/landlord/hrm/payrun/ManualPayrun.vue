<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { Search, Xmark, Plus, Trash, Check, NavArrowLeft } from "@iconoir/vue";
import Axios from "@/services/axios";
import {
    HRM_DEPARTMENTS,
    HRM_EMPLOYEES,
    HRM_BENEFICIARIES,
    HRM_DEFAULT_BENEFICIARY_VALUES,
    HRM_PAYRUN_RUN_MANUAL,
} from "@/services/endpoints/hrm.js";

const { t } = useI18n();
const router = useRouter();
const toast = useToast();

// ── steps ──────────────────────────────────────────────────────────────
const steps = [
    { key: 'payee', label: t('payee'), desc: t('select_payees') },
    { key: 'period', label: t('payrun_period'), desc: t('set_period_type') },
    { key: 'note', label: t('payslip_note'), desc: t('optional_note') },
    { key: 'beneficiary', label: t('beneficiary_badge'), desc: t('configure_badges') },
];
const currentStep = ref(0);

// ── master data ────────────────────────────────────────────────────────
const allDepartments = ref([]);
const allEmployees = ref([]);
const allBeneficiaries = ref([]);
const dataLoading = ref(true);

// ── form ───────────────────────────────────────────────────────────────
const form = ref({
    department_ids: [],
    employee_ids: [],
    payrun_period: 'monthly',
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
    start_date: null,
    end_date: null,
    consider_type: 'hour',
    consider_overtime: true,
    note: '',
    beneficiary_values: [],
});

// ── tag-selector dropdown state ────────────────────────────────────────
const deptSearch = ref('');
const empSearch = ref('');
const showDeptDropdown = ref(false);
const showEmpDropdown = ref(false);

// ── computed ───────────────────────────────────────────────────────────
const isMonthly = computed(() => form.value.payrun_period === 'monthly');
const showOvertime = computed(() => form.value.consider_type !== 'none');

const selectedDepts = computed(() =>
    allDepartments.value.filter(d => form.value.department_ids.includes(d.id))
);
const selectedEmps = computed(() =>
    allEmployees.value.filter(e => form.value.employee_ids.includes(e.id))
);
const filteredDepts = computed(() =>
    allDepartments.value.filter(
        d => !form.value.department_ids.includes(d.id) &&
            d.name.toLowerCase().includes(deptSearch.value.toLowerCase())
    )
);
const filteredEmps = computed(() =>
    allEmployees.value.filter(
        e => !form.value.employee_ids.includes(e.id) &&
            e.name.toLowerCase().includes(empSearch.value.toLowerCase())
    )
);
const allowances = computed(() => form.value.beneficiary_values.filter(b => b.type === 'allowance'));
const deductions = computed(() => form.value.beneficiary_values.filter(b => b.type === 'deduction'));
const availableToAdd = computed(() => {
    const used = new Set(form.value.beneficiary_values.map(b => b.beneficiary_id));
    return allBeneficiaries.value.filter(b => !used.has(b.id));
});

const currentYear = new Date().getFullYear();
const months = [
    { id: 1, name: 'January' }, { id: 2, name: 'February' }, { id: 3, name: 'March' },
    { id: 4, name: 'April' }, { id: 5, name: 'May' }, { id: 6, name: 'June' },
    { id: 7, name: 'July' }, { id: 8, name: 'August' }, { id: 9, name: 'September' },
    { id: 10, name: 'October' }, { id: 11, name: 'November' }, { id: 12, name: 'December' },
];
const years = Array.from({ length: 6 }, (_, i) => ({ id: currentYear - i, name: String(currentYear - i) }));
const considerTypeOptions = [
    { id: 'hour', name: t('hour'), icon: '⏱' },
    { id: 'daily_log', name: t('daily_log'), icon: '📋' },
    { id: 'none', name: t('none'), icon: '—' },
];

// ── add-beneficiary row ────────────────────────────────────────────────
const addRow = ref({ beneficiary_id: null, amount: '', is_percentage: false });
const addRowError = ref('');

// ── watchers ───────────────────────────────────────────────────────────
watch(() => form.value.consider_type, val => {
    form.value.consider_overtime = val !== 'none';
});
watch(() => form.value.department_ids, () => {
    form.value.employee_ids = [];
});

// ── data load ──────────────────────────────────────────────────────────
const loadData = async () => {
    dataLoading.value = true;
    try {
        const [deptsRes, empsRes, benefRes, defaultsRes] = await Promise.all([
            Axios.get(HRM_DEPARTMENTS, { params: { per_page: 200 } }),
            Axios.get(HRM_EMPLOYEES, { params: { per_page: 500 } }),
            Axios.get(HRM_BENEFICIARIES, { params: { per_page: 100 } }),
            Axios.get(HRM_DEFAULT_BENEFICIARY_VALUES),
        ]);

        allDepartments.value = deptsRes.data.data ?? [];
        allEmployees.value = empsRes.data.data ?? [];
        allBeneficiaries.value = benefRes.data.data ?? [];

        const defaults = defaultsRes.data;
        const buildRow = (item, type) => ({
            beneficiary_id: item.beneficiary?.id ?? item.beneficiary_id,
            name: item.beneficiary?.name ?? item.name ?? '',
            type,
            amount: item.amount,
            is_percentage: !!item.is_percentage,
        });
        form.value.beneficiary_values = [
            ...(defaults.allowances ?? []).map(i => buildRow(i, 'allowance')),
            ...(defaults.deductions ?? []).map(i => buildRow(i, 'deduction')),
        ];
    } finally {
        dataLoading.value = false;
    }
};

// ── tag-selector ───────────────────────────────────────────────────────
const selectDept = d => { form.value.department_ids.push(d.id); deptSearch.value = ''; showDeptDropdown.value = false; };
const removeDept = id => { form.value.department_ids = form.value.department_ids.filter(x => x !== id); };
const selectEmp = e => { form.value.employee_ids.push(e.id); empSearch.value = ''; showEmpDropdown.value = false; };
const removeEmp = id => { form.value.employee_ids = form.value.employee_ids.filter(x => x !== id); };

// ── beneficiary ────────────────────────────────────────────────────────
const removeBeneficiary = id => {
    form.value.beneficiary_values = form.value.beneficiary_values.filter(b => b.beneficiary_id !== id);
};
const addBeneficiary = () => {
    addRowError.value = '';
    if (!addRow.value.beneficiary_id) { addRowError.value = t('please_select_beneficiary'); return; }
    const amount = Number(addRow.value.amount);
    if (addRow.value.amount === '' || isNaN(amount) || amount < 0) { addRowError.value = t('please_enter_valid_amount'); return; }
    const b = allBeneficiaries.value.find(x => x.id == addRow.value.beneficiary_id);
    if (!b) return;
    form.value.beneficiary_values.push({ beneficiary_id: b.id, name: b.name, type: b.type, amount, is_percentage: addRow.value.is_percentage });
    addRow.value = { beneficiary_id: null, amount: '', is_percentage: false };
};

// ── step navigation ────────────────────────────────────────────────────
const errors = ref({});
const submitting = ref(false);

const validateStep = () => {
    if (currentStep.value === 0 && !form.value.department_ids.length) {
        toast.warning(t('please_select_at_least_one_department'));
        return false;
    }
    return true;
};
const next = () => { if (validateStep()) currentStep.value++; };
const back = () => { currentStep.value > 0 ? currentStep.value-- : router.push('/hrm/payruns'); };
const goToStep = idx => { if (idx < currentStep.value) currentStep.value = idx; };

// ── submit ─────────────────────────────────────────────────────────────
const submit = () => {
    errors.value = {};
    submitting.value = true;

    const payload = {
        department_ids: form.value.department_ids,
        employee_ids: form.value.employee_ids.length ? form.value.employee_ids : undefined,
        payrun_period: form.value.payrun_period,
        consider_type: form.value.consider_type,
        consider_overtime: form.value.consider_overtime,
        note: form.value.note || undefined,
        beneficiary_values: form.value.beneficiary_values.map(b => ({
            beneficiary_id: b.beneficiary_id, amount: b.amount, is_percentage: b.is_percentage,
        })),
    };
    if (form.value.payrun_period === 'monthly') {
        payload.month = form.value.month;
        payload.year = form.value.year;
    } else {
        payload.start_date = form.value.start_date;
        payload.end_date = form.value.end_date;
    }

    Axios.post(HRM_PAYRUN_RUN_MANUAL, payload)
        .then(res => { toast.success(res.data.message); router.push('/hrm/payruns'); })
        .catch(({ response }) => {
            if (response?.status === 422) errors.value = response.data.errors ?? {};
            toast.error(response?.data?.message ?? 'Something went wrong');
        })
        .finally(() => { submitting.value = false; });
};

onMounted(loadData);
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('manual_payrun')">
            <template #breadcrumb-actions>
                <button type="button" class="mp-back-btn" @click="router.push('/hrm/payruns')">
                    <NavArrowLeft width="15" height="15" />
                    {{ $t('back_to_payrun') }}
                </button>
            </template>
        </app-breadcrumb>

        <div class="mp-wrapper">



            <!-- ── Stepper ── -->
            <div class="mp-stepper">
                <div v-for="(step, idx) in steps" :key="step.key" class="mp-step" :class="{
                    'mp-step--active': idx === currentStep,
                    'mp-step--done': idx < currentStep,
                }" @click="goToStep(idx)">

                    <div class="mp-step__circle">
                        <Check v-if="idx < currentStep" width="14" height="14" />
                        <span v-else>{{ idx + 1 }}</span>
                    </div>

                    <div class="mp-step__label">
                        <span class="mp-step__name">{{ step.label }}</span>
                        <span class="mp-step__desc">{{ step.desc }}</span>
                    </div>

                    <div v-if="idx < steps.length - 1" class="mp-step__line"
                        :class="{ 'mp-step__line--done': idx < currentStep }"></div>
                </div>
            </div>

            <!-- ── Card ── -->
            <div class="mp-card">

                <app-loader v-if="dataLoading" />

                <template v-else>

                    <!-- ════ Step 1 — Payee ════ -->
                    <div v-if="currentStep === 0" class="mp-content">
                        <div class="mp-desc-col">
                            <div class="mp-desc-icon">👥</div>
                            <h6 class="mp-desc-title">{{ $t('who_are_allowed_for_payrun') }}</h6>
                            <p class="mp-desc-text">{{ $t('payrun_payee_description') }}</p>
                        </div>
                        <div class="mp-form-col">

                            <!-- By Department -->
                            <div class="mp-field-group">
                                <div class="mp-field-label">
                                    {{ $t('by_department') }}
                                    <span class="mp-required">*</span>
                                </div>
                                <div class="tag-box" :class="{ 'tag-box--focus': showDeptDropdown }">
                                    <span v-for="d in selectedDepts" :key="d.id" class="tag-chip tag-chip--dept">
                                        {{ d.name }}
                                        <button type="button" @click="removeDept(d.id)">
                                            <Xmark width="11" height="11" />
                                        </button>
                                    </span>
                                    <div class="tag-add-wrap">
                                        <button type="button" class="tag-add-btn"
                                            @click="showDeptDropdown = !showDeptDropdown; showEmpDropdown = false">
                                            <Plus width="13" height="13" /> {{ $t('add') }}
                                        </button>
                                        <Transition name="dropdown">
                                            <div v-if="showDeptDropdown" class="tag-drop">
                                                <div class="tag-drop__search">
                                                    <Search width="13" height="13" />
                                                    <input v-model="deptSearch" type="text"
                                                        :placeholder="$t('search') + '...'" autofocus />
                                                </div>
                                                <ul class="tag-drop__list">
                                                    <li v-for="d in filteredDepts" :key="d.id" @click="selectDept(d)">{{
                                                        d.name }}</li>
                                                    <li v-if="!filteredDepts.length" class="tag-drop__empty">
                                                        {{ $t('no_data_found') }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>
                            </div>

                            <!-- By User -->
                            <div class="mp-field-group">
                                <div class="mp-field-label">{{ $t('by_user') }}</div>
                                <div class="tag-box" :class="{ 'tag-box--focus': showEmpDropdown }">
                                    <span v-for="e in selectedEmps" :key="e.id" class="tag-chip tag-chip--emp">
                                        {{ e.name }}
                                        <button type="button" @click="removeEmp(e.id)">
                                            <Xmark width="11" height="11" />
                                        </button>
                                    </span>
                                    <div class="tag-add-wrap">
                                        <button type="button" class="tag-add-btn"
                                            @click="showEmpDropdown = !showEmpDropdown; showDeptDropdown = false">
                                            <Plus width="13" height="13" /> {{ $t('add') }}
                                        </button>
                                        <Transition name="dropdown">
                                            <div v-if="showEmpDropdown" class="tag-drop">
                                                <div class="tag-drop__search">
                                                    <Search width="13" height="13" />
                                                    <input v-model="empSearch" type="text"
                                                        :placeholder="$t('search') + '...'" autofocus />
                                                </div>
                                                <ul class="tag-drop__list">
                                                    <li v-for="e in filteredEmps" :key="e.id" @click="selectEmp(e)">{{
                                                        e.name }}</li>
                                                    <li v-if="!filteredEmps.length" class="tag-drop__empty">
                                                        {{ $t('no_data_found') }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>
                                <p class="mp-hint">{{ $t('manual_payrun_employee_hint') }}</p>
                            </div>

                        </div>
                    </div>

                    <!-- ════ Step 2 — Payrun Period ════ -->
                    <div v-if="currentStep === 1" class="mp-content">
                        <div class="mp-desc-col">
                            <div class="mp-desc-icon">📅</div>
                            <h6 class="mp-desc-title">{{ $t('payrun_period') }}</h6>
                            <p class="mp-desc-text">{{ $t('payrun_period_description') }}</p>
                        </div>
                        <div class="mp-form-col">

                            <!-- Period toggle -->
                            <div class="mp-field-group">
                                <div class="mp-field-label">{{ $t('period_type') }} <span class="mp-required">*</span>
                                </div>
                                <div class="seg-control">
                                    <button type="button" :class="['seg-btn', { 'seg-btn--active': isMonthly }]"
                                        @click="form.payrun_period = 'monthly'">
                                        {{ $t('monthly') }}
                                    </button>
                                    <button type="button" :class="['seg-btn', { 'seg-btn--active': !isMonthly }]"
                                        @click="form.payrun_period = 'customized'">
                                        {{ $t('customized') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Monthly -->
                            <Transition name="fade-slide" mode="out-in">
                                <div v-if="isMonthly" key="monthly" class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <app-input id="month" type="select" :label="$t('month')" label-required
                                            :options="months" list-value-field="name" v-model="form.month"
                                            :selected-disable="false" :errors="$errors(errors, 'month')" />
                                    </div>
                                    <div class="col-md-6">
                                        <app-input id="year" type="select" :label="$t('year')" label-required
                                            :options="years" list-value-field="name" v-model="form.year"
                                            :selected-disable="false" :errors="$errors(errors, 'year')" />
                                    </div>
                                </div>

                                <!-- Customized -->
                                <div v-else key="custom" class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <app-input id="start_date" type="date" :label="$t('start_date')" label-required
                                            v-model="form.start_date" auto-apply
                                            :errors="$errors(errors, 'start_date')" />
                                    </div>
                                    <div class="col-md-6">
                                        <app-input id="end_date" type="date" :label="$t('end_date')" label-required
                                            v-model="form.end_date" auto-apply :errors="$errors(errors, 'end_date')" />
                                    </div>
                                </div>
                            </Transition>

                            <!-- Consider Type -->
                            <div class="mp-field-group">
                                <div class="mp-field-label">{{ $t('payrun_generating_type') }} <span
                                        class="mp-required">*</span></div>
                                <div class="type-cards">
                                    <button v-for="opt in considerTypeOptions" :key="opt.id" type="button"
                                        :class="['type-card', { 'type-card--active': form.consider_type === opt.id }]"
                                        @click="form.consider_type = opt.id">
                                        <span class="type-card__icon">{{ opt.icon }}</span>
                                        <span class="type-card__name">{{ opt.name }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Overtime -->
                            <Transition name="fade">
                                <div v-if="showOvertime" class="overtime-toggle">
                                    <div class="overtime-toggle__text">
                                        <span class="fw-semibold small">{{ $t('consider_overtime') }}</span>
                                        <span class="text-muted" style="font-size:0.78rem">{{
                                            $t('overtime_will_calculate') }}</span>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="ot_switch"
                                            v-model="form.consider_overtime" />
                                        <label class="form-check-label" for="ot_switch"></label>
                                    </div>
                                </div>
                            </Transition>

                        </div>
                    </div>

                    <!-- ════ Step 3 — Note ════ -->
                    <div v-if="currentStep === 2" class="mp-content">
                        <div class="mp-desc-col">
                            <div class="mp-desc-icon">📝</div>
                            <h6 class="mp-desc-title">{{ $t('payslip_note') }}</h6>
                            <p class="mp-desc-text">{{ $t('payslip_note_description') }}</p>
                        </div>
                        <div class="mp-form-col">
                            <div class="mp-field-group">
                                <label class="mp-field-label">{{ $t('note') }}</label>
                                <textarea class="form-control mp-textarea" rows="6" v-model="form.note"
                                    :placeholder="$t('add_payslip_note')"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ════ Step 4 — Beneficiary Badge ════ -->
                    <div v-if="currentStep === 3" class="mp-content mp-content--wide">
                        <div class="mp-desc-col">
                            <div class="mp-desc-icon">🏷</div>
                            <h6 class="mp-desc-title">{{ $t('beneficiary_badge') }}</h6>
                            <p class="mp-desc-text">{{ $t('beneficiary_badge_description') }}</p>
                        </div>
                        <div class="mp-form-col">

                            <!-- Allowances -->
                            <div class="ben-section ben-section--allow">
                                <div class="ben-section__head">
                                    <span class="ben-badge ben-badge--allow">{{ $t('earning') }}</span>
                                    <span class="text-muted small">{{ allowances.length }} {{ $t('item') }}</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table ben-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ $t('name') }}</th>
                                                <th style="width:150px">{{ $t('amount') }}</th>
                                                <th style="width:80px" class="text-center">{{ $t('percentage') }}</th>
                                                <th style="width:48px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in allowances" :key="item.beneficiary_id">
                                                <td class="fw-medium">{{ item.name }}</td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm ben-input"
                                                        v-model.number="item.amount" min="0" step="0.01" />
                                                </td>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input"
                                                        v-model="item.is_percentage" />
                                                </td>
                                                <td>
                                                    <button type="button" class="ben-del-btn"
                                                        @click="removeBeneficiary(item.beneficiary_id)">
                                                        <Trash width="14" height="14" />
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="!allowances.length">
                                                <td colspan="4" class="text-center text-muted py-3 small">
                                                    {{ $t('no_data_found') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Deductions -->
                            <div class="ben-section ben-section--deduct">
                                <div class="ben-section__head">
                                    <span class="ben-badge ben-badge--deduct">{{ $t('deduction') }}</span>
                                    <span class="text-muted small">{{ deductions.length }} {{ $t('item') }}</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table ben-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ $t('name') }}</th>
                                                <th style="width:150px">{{ $t('amount') }}</th>
                                                <th style="width:80px" class="text-center">{{ $t('percentage') }}</th>
                                                <th style="width:48px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in deductions" :key="item.beneficiary_id">
                                                <td class="fw-medium">{{ item.name }}</td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm ben-input"
                                                        v-model.number="item.amount" min="0" step="0.01" />
                                                </td>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input"
                                                        v-model="item.is_percentage" />
                                                </td>
                                                <td>
                                                    <button type="button" class="ben-del-btn"
                                                        @click="removeBeneficiary(item.beneficiary_id)">
                                                        <Trash width="14" height="14" />
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="!deductions.length">
                                                <td colspan="4" class="text-center text-muted py-3 small">
                                                    {{ $t('no_data_found') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Add row -->
                            <div class="ben-add-panel">
                                <p class="ben-add-panel__title">
                                    <Plus width="14" height="14" /> {{ $t('add_beneficiary') }}
                                </p>
                                <div class="row g-2 align-items-end">
                                    <div class="col">
                                        <select class="form-control" v-model="addRow.beneficiary_id">
                                            <option :value="null" disabled>{{ $t('select') }}…</option>
                                            <optgroup :label="$t('earning')">
                                                <option v-for="b in availableToAdd.filter(x => x.type === 'allowance')"
                                                    :key="b.id" :value="b.id">{{ b.name }}</option>
                                            </optgroup>
                                            <optgroup :label="$t('deduction')">
                                                <option v-for="b in availableToAdd.filter(x => x.type === 'deduction')"
                                                    :key="b.id" :value="b.id">{{ b.name }}</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="col-auto" style="width:130px">
                                        <input type="number" class="form-control form-control-sm"
                                            v-model="addRow.amount" min="0" step="0.01" placeholder="0" />
                                    </div>
                                    <div class="col-auto d-flex align-items-center gap-1 pb-1">
                                        <input type="checkbox" class="form-check-input" id="add_pct"
                                            v-model="addRow.is_percentage" />
                                        <label class="form-check-label small" for="add_pct">%</label>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button"
                                            class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                                            @click="addBeneficiary">
                                            <Plus width="13" height="13" /> {{ $t('add') }}
                                        </button>
                                    </div>
                                </div>
                                <p v-if="addRowError" class="text-danger small mt-2 mb-0">{{ addRowError }}</p>
                            </div>

                        </div>
                    </div>

                </template>
            </div>

            <!-- ── Navigation ── -->
            <div class="mp-nav">
                <button type="button" class="mp-nav__back" @click="back">
                    <NavArrowLeft width="15" height="15" />
                    {{ currentStep === 0 ? $t('cancel') : $t('back') }}
                </button>
                <div class="d-flex align-items-center gap-2">
                    <span class="mp-nav__step-count">{{ currentStep + 1 }} / {{ steps.length }}</span>
                    <button v-if="currentStep < steps.length - 1" type="button" class="mp-nav__next" @click="next">
                        {{ $t('next') }}
                    </button>
                    <button v-else type="button" class="mp-nav__run" :disabled="submitting" @click="submit">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $t('run_payrun') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
// ── Layout ─────────────────────────────────────────────────────────────
.mp-wrapper {
    max-width: 1040px;
    margin: 0 auto;
    padding: 8px 0 40px;
}

// ── Breadcrumb back button ──────────────────────────────────────────────
.mp-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    padding: 6px 14px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all .2s;

    &:hover {
        border-color: #3b82f6;
        color: #3b82f6;
        background: #eff6ff;
    }
}

// ── Stepper ────────────────────────────────────────────────────────────
.mp-stepper {
    display: flex;
    align-items: flex-start;
    margin-bottom: 32px;
    padding: 0 4px;
}

.mp-step {
    display: flex;
    align-items: center;
    flex: 1;
    position: relative;
    cursor: default;

    &--done {
        cursor: pointer;
    }

    &__circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        color: #9ca3af;
        flex-shrink: 0;
        transition: all .25s;
        z-index: 1;
    }

    &__label {
        display: flex;
        flex-direction: column;
        margin-left: 10px;
        flex-shrink: 0;
    }

    &__name {
        font-size: 0.82rem;
        font-weight: 600;
        color: #9ca3af;
        transition: color .25s;
        white-space: nowrap;
    }

    &__desc {
        font-size: 0.72rem;
        color: #d1d5db;
        transition: color .25s;
        white-space: nowrap;
    }

    &__line {
        flex: 1;
        height: 2px;
        background: #e5e7eb;
        margin: 0 14px;
        border-radius: 2px;
        transition: background .3s;

        &--done {
            background: #3b82f6;
        }
    }

    &--active {
        .mp-step__circle {
            background: #3b82f6;
            border-color: #3b82f6;
            color: #fff;
            box-shadow: 0 0 0 5px #dbeafe;
        }

        .mp-step__name {
            color: #1d4ed8;
        }

        .mp-step__desc {
            color: #93c5fd;
        }
    }

    &--done {
        .mp-step__circle {
            background: #10b981;
            border-color: #10b981;
            color: #fff;
        }

        .mp-step__name {
            color: #065f46;
        }

        .mp-step__desc {
            color: #6ee7b7;
        }

        &:hover .mp-step__circle {
            background: #059669;
            border-color: #059669;
        }
    }
}

// ── Card ───────────────────────────────────────────────────────────────
.mp-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, .07), 0 1px 4px rgba(0, 0, 0, .04);
    padding: 40px;
    margin-bottom: 20px;
    min-height: 360px;
}

// ── Content layout ─────────────────────────────────────────────────────
.mp-content {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 48px;
    align-items: start;

    &--wide {
        grid-template-columns: 220px 1fr;
        gap: 40px;
    }
}

// ── Description column ─────────────────────────────────────────────────
.mp-desc-col {
    padding: 20px 24px;
    background: linear-gradient(135deg, #f8faff 0%, #f0f4ff 100%);
    border-radius: 12px;
    border: 1px solid #e8eeff;
    position: sticky;
    top: 20px;
}

.mp-desc-icon {
    font-size: 1.8rem;
    margin-bottom: 12px;
    line-height: 1;
}

.mp-desc-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.mp-desc-text {
    font-size: 0.78rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

// ── Form column ────────────────────────────────────────────────────────
.mp-form-col {
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.mp-field-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.mp-field-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #374151;
}

.mp-required {
    color: #ef4444;
    margin-left: 3px;
}

.mp-hint {
    font-size: 0.75rem;
    color: #9ca3af;
    margin: 4px 0 0;
}

.mp-textarea {
    border-radius: 10px;
    border-color: #e5e7eb;
    resize: vertical;
    font-size: 0.875rem;

    &:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px #dbeafe;
    }
}

// ── Tag Selector ───────────────────────────────────────────────────────
.tag-box {
    min-height: 50px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    background: #fafafa;
    transition: border-color .2s, box-shadow .2s;

    &--focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px #dbeafe;
        background: #fff;
    }
}

.tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border-radius: 100px;
    padding: 4px 10px 4px 12px;
    font-size: 0.79rem;
    font-weight: 600;

    button {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        opacity: .6;
        transition: opacity .2s;

        &:hover {
            opacity: 1;
        }
    }

    &--dept {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;

        button {
            color: #1d4ed8;
        }
    }

    &--emp {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;

        button {
            color: #15803d;
        }
    }
}

.tag-add-wrap {
    position: relative;
}

.tag-add-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: none;
    border: 1.5px dashed #d1d5db;
    border-radius: 100px;
    padding: 4px 12px;
    font-size: 0.79rem;
    color: #6b7280;
    cursor: pointer;
    transition: all .2s;

    &:hover {
        border-color: #3b82f6;
        color: #3b82f6;
        background: #eff6ff;
    }
}

.tag-drop {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    z-index: 1000;
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, .12);
    min-width: 230px;
    overflow: hidden;

    &__search {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-bottom: 1px solid #f3f4f6;

        input {
            border: none;
            outline: none;
            font-size: 0.83rem;
            flex: 1;
            background: transparent;
        }
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 4px 0;
        max-height: 210px;
        overflow-y: auto;

        li {
            padding: 8px 16px;
            font-size: 0.83rem;
            cursor: pointer;
            transition: background .15s;

            &:hover {
                background: #eff6ff;
                color: #1d4ed8;
            }
        }
    }

    &__empty {
        color: #9ca3af !important;
        cursor: default !important;

        &:hover {
            background: transparent !important;
        }
    }
}

// ── Segmented control ──────────────────────────────────────────────────
.seg-control {
    display: inline-flex;
    background: #f3f4f6;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
}

.seg-btn {
    padding: 8px 22px;
    border: none;
    border-radius: 7px;
    font-size: 0.83rem;
    font-weight: 500;
    color: #6b7280;
    background: transparent;
    cursor: pointer;
    transition: all .2s;

    &--active {
        background: #fff;
        color: #1d4ed8;
        font-weight: 700;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .1);
    }
}

// ── Consider Type cards ────────────────────────────────────────────────
.type-cards {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.type-card {
    flex: 1;
    min-width: 90px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 16px 10px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    transition: all .2s;

    &__icon {
        font-size: 1.4rem;
    }

    &__name {
        font-size: 0.78rem;
        font-weight: 600;
        color: #6b7280;
    }

    &:hover {
        border-color: #93c5fd;
        background: #f8fbff;
    }

    &--active {
        border-color: #3b82f6;
        background: #eff6ff;
        box-shadow: 0 0 0 3px #dbeafe;

        .type-card__name {
            color: #1d4ed8;
        }
    }
}

// ── Overtime toggle ────────────────────────────────────────────────────
.overtime-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    background: #fafafa;
    gap: 12px;

    &__text {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
}

// ── Beneficiary tables ─────────────────────────────────────────────────
.ben-section {
    border-radius: 12px;
    border: 1.5px solid #e5e7eb;
    overflow: hidden;

    &__head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: #fafafa;
        border-bottom: 1px solid #f3f4f6;
    }

    &--allow {
        border-color: #bbf7d0;
    }

    &--deduct {
        border-color: #fecaca;
    }
}

.ben-badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 100px;

    &--allow {
        background: #d1fae5;
        color: #065f46;
    }

    &--deduct {
        background: #fee2e2;
        color: #991b1b;
    }
}

.ben-table {
    font-size: 0.83rem;

    th {
        background: #f9fafb;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        border-bottom: 1px solid #f3f4f6;
    }

    td {
        border-color: #f9fafb;
        vertical-align: middle;
    }

    tr:last-child td {
        border-bottom: none;
    }
}

.ben-input {
    border-radius: 8px;

    &:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px #dbeafe;
    }
}

.ben-del-btn {
    background: none;
    border: none;
    padding: 5px;
    color: #d1d5db;
    cursor: pointer;
    border-radius: 6px;
    display: flex;
    align-items: center;
    transition: all .2s;

    &:hover {
        color: #ef4444;
        background: #fee2e2;
    }
}

.ben-add-panel {
    padding: 16px 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1.5px dashed #cbd5e1;

    &__title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
}

// ── Navigation bar ─────────────────────────────────────────────────────
.mp-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
}

.mp-nav__step-count {
    font-size: 0.78rem;
    color: #9ca3af;
    font-weight: 600;
    background: #f3f4f6;
    padding: 4px 10px;
    border-radius: 100px;
}

.mp-nav__back {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: none;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    padding: 9px 18px;
    font-size: 0.83rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all .2s;

    &:hover {
        border-color: #9ca3af;
        color: #374151;
    }
}

.mp-nav__next {
    padding: 10px 28px;
    border-radius: 10px;
    background: #3b82f6;
    border: none;
    color: #fff;
    font-size: 0.83rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .2s;

    &:hover {
        background: #2563eb;
        box-shadow: 0 4px 14px rgba(59, 130, 246, .4);
    }
}

.mp-nav__run {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 28px;
    border-radius: 10px;
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    color: #fff;
    font-size: 0.83rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .2s;

    &:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(16, 185, 129, .4);
    }

    &:disabled {
        opacity: .65;
        cursor: not-allowed;
    }
}

// ── Transitions ────────────────────────────────────────────────────────
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all .18s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all .2s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity .2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
