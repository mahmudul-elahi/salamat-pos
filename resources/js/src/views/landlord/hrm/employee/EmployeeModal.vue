<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_EMPLOYEES, HRM_DESIGNATIONS, HRM_EMPLOYEE_STATUSES, HRM_DEPARTMENTS } from "@/services/endpoints/hrm.js";
import { SELECTED_SHOPS, LANDLORD_SELECTED_ROLES } from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    name: null,
    email: null,
    phone: null,
    gender: 'male',
    designation_id: null,
    employee_status_id: null,
    department_id: null,
    joining_date: null,
    salary: null,
    tenant_ids: [],
    role_id: null,
};

const genderOptions = [
    { id: 'male', name: 'Male' },
    { id: 'female', name: 'Female' },
];

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_EMPLOYEES, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="selectedUrl ? $t('update_employee') : $t('add_employee')"
        :preloader="preloader" @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-6">
                        <app-input id="name" type="text" :label="$t('name')" label-required
                            :placeholder="$t('enter_employee_name')" v-model="formData.name"
                            :errors="$errors(errors, 'name')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="email" type="email" :label="$t('email')" label-required
                            :placeholder="$t('enter_email')" v-model="formData.email"
                            :errors="$errors(errors, 'email')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="phone" type="text" :label="$t('phone_number')" placeholder="+880 1XXX-XXXXXX"
                            v-model="formData.phone" :errors="$errors(errors, 'phone')" />
                    </div>

                    <div class="col-md-6">
                        <div> <label class="form-label mb-2">{{ $t('gender') }} <span
                                    class="text-danger">*</span></label></div>
                        <app-input type="radio" id="gender" radio-checkbox-name="gender" name="gender"
                            list-value-field="name" v-model="formData.gender" :options="genderOptions"
                            :errors="$errors(errors, 'gender')" />
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
                        <app-input id="joining_date" type="date" :label="$t('joining_date')" label-required
                            :placeholder="$t('joining_date')" v-model="formData.joining_date"
                            :enable-time-picker="false" auto-apply :errors="$errors(errors, 'joining_date')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="salary" type="number" :label="$t('salary')" label-required
                            :placeholder="$t('enter_salary')" v-model="formData.salary"
                            :errors="$errors(errors, 'salary')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="advance-multi-select" id="tenant_ids" :label="$t('shop')" label-required
                            list-value-field="name" :loading-length="10" :fetch-url="SELECTED_SHOPS"
                            :choose-label="$t('choose_shop')" v-model="formData.tenant_ids"
                            :errors="$errors(errors, 'tenant_ids')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="role_id" :label="$t('role')" label-required
                            list-value-field="name" :loading-length="10" :fetch-url="LANDLORD_SELECTED_ROLES"
                            :choose-label="$t('choose_role')" v-model="formData.role_id"
                            :errors="$errors(errors, 'role_id')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
