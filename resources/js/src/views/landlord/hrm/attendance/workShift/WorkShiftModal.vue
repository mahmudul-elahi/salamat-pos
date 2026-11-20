<script setup>
import { ref, watch, onMounted } from "vue";
import Axios from "@/services/axios/index.js";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_WORK_SHIFTS, HRM_BREAK_TIMES, HRM_DEPARTMENTS, HRM_EMPLOYEES } from "@/services/endpoints/hrm.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const defaultSchedules = () => weekDays.map(day => ({ day_name: day, start_time: null, end_time: null }));

// Both lists fetched once on mount — prevents reactive refetch auto-selecting new items
const breakTimeOptions = ref([]);
const fetchBreakTimes = () => {
    Axios.get(HRM_BREAK_TIMES, { params: { per_page: 500 } }).then(({ data }) => {
        breakTimeOptions.value = (data.data ?? []).map(b => ({ id: b.id, name: b.name }));
    });
};

const departmentOptions = ref([]);
const fetchDepartments = () => {
    Axios.get(HRM_DEPARTMENTS, { params: { per_page: 500 } }).then(({ data }) => {
        departmentOptions.value = (data.data ?? []).map(d => ({ id: d.id, name: d.name }));
    });
};

const employeeOptions = ref([]);
const fetchEmployees = () => {
    Axios.get(HRM_EMPLOYEES, { params: { per_page: 1000 } }).then(({ data }) => {
        employeeOptions.value = (data.data ?? []).map(e => ({ id: e.id, name: e.name }));
    });
};

formData.value = {
    shift_name: null,
    working_shift_type: 'regular',
    start_time: null,
    end_time: null,
    weekend_days: [],
    break_times: [],
    departments: [],
    employees: [],
    schedules: defaultSchedules(),
};

watch(() => pageLoader.value, (loading) => {
    if (!loading) {
        if (Array.isArray(formData.value.break_times)) {
            formData.value.break_times = formData.value.break_times.map(
                b => (typeof b === 'object' ? b.id : b)
            );
        }
        if (Array.isArray(formData.value.departments)) {
            formData.value.departments = formData.value.departments.map(
                d => (typeof d === 'object' ? d.id : d)
            );
        }
        if (Array.isArray(formData.value.employees)) {
            formData.value.employees = formData.value.employees.map(
                e => (typeof e === 'object' ? e.id : e)
            );
        }
        if (!Array.isArray(formData.value.weekend_days)) {
            formData.value.weekend_days = [];
        }
        if (!Array.isArray(formData.value.schedules) || formData.value.schedules.length === 0) {
            formData.value.schedules = defaultSchedules();
        }
    }
});

onMounted(() => { fetchBreakTimes(); fetchDepartments(); fetchEmployees(); });

const workShiftTypeOptions = [
    { id: 'regular', name: 'Regular' },
    { id: 'scheduled', name: 'Scheduled' },
];

const submit = () => save(props.selectedUrl ? props.selectedUrl : HRM_WORK_SHIFTS, formData.value);
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large"
        :title="selectedUrl ? $t('update_work_shift') : $t('add_work_shift')"
        :preloader="preloader" @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">

                    <!-- Shift Name -->
                    <div class="col-md-12">
                        <app-input id="shift_name" type="text" :label="$t('shift_name')" label-required
                            :placeholder="$t('enter_shift_name')"
                            v-model="formData.shift_name" :errors="$errors(errors, 'shift_name')" />
                    </div>

                    <!-- Working Shift Type -->
                    <div class="col-md-12">
                        <app-input id="working_shift_type" type="select" :label="$t('working_shift_type')" label-required
                            :options="workShiftTypeOptions" list-value-field="name"
                            :selected-disable="false"
                            v-model="formData.working_shift_type"
                            :errors="$errors(errors, 'working_shift_type')" />
                    </div>

                    <!-- Regular: start_time / end_time -->
                    <template v-if="formData.working_shift_type === 'regular'">
                        <div class="col-md-6">
                            <label class="form-label mb-2">{{ $t('start_time') }}</label>
                            <input type="time" class="form-control" v-model="formData.start_time" />
                            <small v-if="$errors(errors, 'start_time')" class="text-danger">{{ $errors(errors, 'start_time') }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2">{{ $t('end_time') }}</label>
                            <input type="time" class="form-control" v-model="formData.end_time" />
                            <small v-if="$errors(errors, 'end_time')" class="text-danger">{{ $errors(errors, 'end_time') }}</small>
                        </div>
                    </template>

                    <!-- Scheduled: 7-day grid -->
                    <template v-if="formData.working_shift_type === 'scheduled'">
                        <div class="col-md-12">
                            <label class="form-label mb-2">{{ $t('daily_schedule') }}</label>
                            <div class="schedule-grid">
                                <div v-for="(schedule, index) in formData.schedules" :key="schedule.day_name"
                                    class="schedule-row">
                                    <span class="day-label">{{ schedule.day_name }}</span>
                                    <div class="time-fields">
                                        <input type="time" class="form-control form-control-sm"
                                            v-model="schedule.start_time"
                                            :placeholder="$t('start_time')" />
                                        <span class="separator">—</span>
                                        <input type="time" class="form-control form-control-sm"
                                            v-model="schedule.end_time"
                                            :placeholder="$t('end_time')" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Weekend Days -->
                    <div class="col-md-12">
                        <label class="form-label mb-2">{{ $t('weekend_days') }}</label>
                        <div class="weekend-days-grid">
                            <div v-for="day in ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']"
                                :key="day" class="weekend-day-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        :id="`weekend_${day}`"
                                        :value="day"
                                        v-model="formData.weekend_days" />
                                    <label class="form-check-label" :for="`weekend_${day}`">{{ day }}</label>
                                </div>
                            </div>
                        </div>
                        <small v-if="$errors(errors, 'weekend_days')" class="text-danger">{{ $errors(errors, 'weekend_days') }}</small>
                    </div>

                    <!-- Break Times -->
                    <div class="col-md-12">
                        <app-input type="multi-select" id="break_times"
                            :label="$t('break_times')" list-value-field="name" list-key-field="id"
                            :options="breakTimeOptions"
                            :choose-label="$t('choose_break_times')"
                            v-model="formData.break_times"
                            :errors="$errors(errors, 'break_times')" />
                    </div>

                    <!-- Departments -->
                    <div class="col-md-12">
                        <app-input type="multi-select" id="departments"
                            :label="$t('departments')" list-value-field="name" list-key-field="id"
                            :options="departmentOptions"
                            :choose-label="$t('choose_departments')"
                            v-model="formData.departments"
                            :errors="$errors(errors, 'departments')" />
                    </div>

                    <!-- Employees -->
                    <div class="col-md-12">
                        <app-input type="multi-select" id="employees"
                            label="Employees" list-value-field="name" list-key-field="id"
                            :options="employeeOptions"
                            choose-label="Choose employees"
                            v-model="formData.employees"
                            :errors="$errors(errors, 'employees')" />
                    </div>

                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.schedule-grid {
    border: 1px solid #e8ecf0;
    border-radius: 8px;
    overflow: hidden;

    .schedule-row {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        gap: 16px;
        border-bottom: 1px solid #e8ecf0;

        &:last-child {
            border-bottom: none;
        }

        &:nth-child(even) {
            background: #f8f9fa;
        }

        .day-label {
            min-width: 90px;
            font-weight: 600;
            font-size: 0.875rem;
            color: #344054;
        }

        .time-fields {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;

            input {
                flex: 1;
                max-width: 140px;
            }

            .separator {
                color: #6c757d;
                font-weight: 600;
            }
        }
    }
}

.weekend-days-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;

    .weekend-day-item {
        .form-check {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid #e8ecf0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin: 0;

            &:hover {
                border-color: #6366f1;
                background: #f5f5ff;
            }

            .form-check-input:checked + .form-check-label {
                color: #6366f1;
                font-weight: 600;
            }

            .form-check-input {
                margin-top: 0;
                cursor: pointer;
            }

            .form-check-label {
                font-size: 0.875rem;
                cursor: pointer;
            }
        }
    }
}
</style>
