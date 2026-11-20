<script setup>
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { CheckCircle } from "@iconoir/vue";
import Axios from "@/services/axios";
import { LANDLORD_PAYRUN_SETTINGS } from "@/services/endpoints/landlord.js";
import { HRM_DEPARTMENTS, HRM_EMPLOYEES, HRM_EMPLOYEE_STATUSES } from "@/services/endpoints/hrm.js";

const { t } = useI18n();
const toast = useToast();

const pageLoader = ref(false);
const preloader = ref(false);
const errors = ref({});

const formData = ref({
    restricted_departments: [],
    restricted_employees: [],
    restricted_employee_statuses: [],
});

const fetchSettings = () => {
    pageLoader.value = true;

    Axios.get(LANDLORD_PAYRUN_SETTINGS)
        .then(({ data }) => {
            formData.value = {
                restricted_departments: data.restricted_departments ?? [],
                restricted_employees: data.restricted_employees ?? [],
                restricted_employee_statuses: data.restricted_employee_statuses ?? [],
            };
        })
        .finally(() => {
            pageLoader.value = false;
        });
};

const submit = () => {
    preloader.value = true;
    errors.value = {};

    Axios.post(LANDLORD_PAYRUN_SETTINGS, formData.value)
        .then((response) => {
            toast.success(response.data.message);
        })
        .catch(({ response }) => {
            if (response?.status === 422) {
                errors.value = response.data.errors;
            } else {
                toast.error(response?.data?.message);
            }
        })
        .finally(() => {
            preloader.value = false;
        });
};

onMounted(fetchSettings);
</script>

<template>
    <app-loader v-if="pageLoader" />
    <form v-else @submit.prevent="submit">
        <div class="alert alert-warning rounded-default mb-4">
            <p class="fw-semibold mb-1">{{ $t('restriction_note') }}</p>
            <p class="mb-0">{{ $t('restriction_note_1') }}</p>
            <p class="mb-0">{{ $t('restriction_note_2') }}</p>
        </div>

        <div class="row g-4 align-items-start mb-4">
            <div class="col-md-3">
                <p class="fw-semibold mb-0">{{ $t('restricted_user') }}</p>
                <p class="text-muted small mb-0">{{ $t('for_payrun') }}</p>
            </div>
            <div class="col-md-9">
                <div class="mb-3">
                    <app-input type="advance-multi-select" id="restricted_departments"
                        :label="$t('department')" label-required list-value-field="name"
                        :loading-length="10" :fetch-url="HRM_DEPARTMENTS"
                        :choose-label="$t('choose_a_department')"
                        v-model="formData.restricted_departments"
                        :errors="$errors(errors, 'restricted_departments')" />
                </div>
                <div>
                    <app-input type="advance-multi-select" id="restricted_employees"
                        :label="$t('employee')" label-required list-value-field="name"
                        :loading-length="10" :fetch-url="HRM_EMPLOYEES"
                        :choose-label="$t('choose_a_employee')"
                        v-model="formData.restricted_employees"
                        :errors="$errors(errors, 'restricted_employees')" />
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start mb-4">
            <div class="col-md-3">
                <p class="fw-semibold mb-0">{{ $t('restricted_user') }}</p>
                <p class="text-muted small mb-0">{{ $t('for_beneficiary_badge') }}</p>
            </div>
            <div class="col-md-9">
                <app-input type="advance-multi-select" id="restricted_employee_statuses"
                    :label="$t('employee_status')" label-required list-value-field="name"
                    :loading-length="10" :fetch-url="HRM_EMPLOYEE_STATUSES"
                    :choose-label="$t('choose_a_employee_status')"
                    v-model="formData.restricted_employee_statuses"
                    :errors="$errors(errors, 'restricted_employee_statuses')" />
            </div>
        </div>

        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" :disabled="preloader">
            <app-button-loader v-if="preloader" />
            <CheckCircle />
            {{ $t('save') }}
        </button>
    </form>
</template>

<style scoped lang="scss"></style>
