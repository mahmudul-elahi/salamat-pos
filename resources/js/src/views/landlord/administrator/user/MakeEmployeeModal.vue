<script setup>
import { ref } from "vue";
import { useToast } from "vue-toastification";
import Axios from "@/services/axios/index.js";
import { HRM_EMPLOYEES, HRM_DESIGNATIONS, HRM_DEPARTMENTS, HRM_EMPLOYEE_STATUSES } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    user: Object,
});

const emit = defineEmits(["close", "success"]);

const toast = useToast();
const preloader = ref(false);

const formData = ref({
    gender: 'male',
    designation_id: null,
    department_id: null,
    employee_status_id: null,
    joining_date: null,
    salary: null,
});

const errors = ref({});

const genderOptions = [
    { id: 'male', name: 'Male' },
    { id: 'female', name: 'Female' },
];

const submit = () => {
    preloader.value = true;
    errors.value = {};

    Axios.post(`${HRM_EMPLOYEES}/${props.user.id}/make-employee`, formData.value)
        .then((response) => {
            toast.success(response.data.message);
            emit('success');
        })
        .catch(({ response }) => {
            if (response?.status === 422) {
                errors.value = response.data.errors ?? {};
            } else {
                toast.error(response?.data?.message ?? 'Something went wrong');
            }
        })
        .finally(() => {
            preloader.value = false;
        });
};

const closeModal = () => emit('close');
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" title="Make Employee" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <div class="mb-3 p-3 bg-light rounded border">
                <p class="mb-0 fw-semibold">{{ user.name }}</p>
                <p class="mb-0 text-muted small">{{ user.email }}</p>
                <p v-if="user.phone" class="mb-0 text-muted small">{{ user.phone }}</p>
            </div>

            <form>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div>
                            <label class="form-label mb-2">{{ $t('gender') }} <span class="text-danger">*</span></label>
                        </div>
                        <app-input type="radio" id="gender" radio-checkbox-name="make-emp-gender" name="gender"
                            list-value-field="name" v-model="formData.gender" :options="genderOptions"
                            :errors="$errors(errors, 'gender')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="joining_date" type="date" :label="$t('joining_date')" label-required
                            :placeholder="$t('joining_date')" v-model="formData.joining_date"
                            :enable-time-picker="false" auto-apply :errors="$errors(errors, 'joining_date')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="designation_id" :label="$t('designation')"
                            label-required list-value-field="name" :loading-length="10" :fetch-url="HRM_DESIGNATIONS"
                            :choose-label="$t('choose_designation')" v-model="formData.designation_id"
                            :errors="$errors(errors, 'designation_id')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="department_id" :label="$t('department')"
                            label-required list-value-field="name" :loading-length="10" :fetch-url="HRM_DEPARTMENTS"
                            :choose-label="$t('choose_department')" v-model="formData.department_id"
                            :errors="$errors(errors, 'department_id')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="employee_status_id" :label="$t('employee_status')"
                            label-required list-value-field="name" :loading-length="10"
                            :fetch-url="HRM_EMPLOYEE_STATUSES" :choose-label="$t('choose_employee_status')"
                            v-model="formData.employee_status_id" :errors="$errors(errors, 'employee_status_id')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="salary" type="number" :label="$t('salary')" label-required
                            :placeholder="$t('enter_salary')" v-model="formData.salary"
                            :errors="$errors(errors, 'salary')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
