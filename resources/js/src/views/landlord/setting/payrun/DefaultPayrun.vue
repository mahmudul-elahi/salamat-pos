<script setup>
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { CheckCircle } from "@iconoir/vue";
import Axios from "@/services/axios";
import { HRM_DEFAULT_PAYRUN_TYPE } from "@/services/endpoints/hrm.js";

const { t } = useI18n();
const toast = useToast();

const pageLoader = ref(false);
const preloader = ref(false);
const errors = ref({});

const formData = ref({
    payrun_period: 'monthly',
    consider_type: 'hour',
    consider_overtime: true,
});

const payrunPeriodOptions = [
    { id: 'weekly', name: t('weekly') },
    { id: 'monthly', name: t('monthly') },
];

const considerTypeOptions = [
    { id: 'hour', name: t('hour') },
    { id: 'daily_log', name: t('daily_log') },
    { id: 'none', name: t('none') },
];

const fetchSettings = () => {
    pageLoader.value = true;

    Axios.get(HRM_DEFAULT_PAYRUN_TYPE)
        .then(({ data }) => {
            formData.value = {
                payrun_period: data.payrun_period,
                consider_type: data.consider_type,
                consider_overtime: !!data.consider_overtime,
            };
        })
        .finally(() => {
            pageLoader.value = false;
        });
};

const submit = () => {
    preloader.value = true;
    errors.value = {};

    Axios.post(HRM_DEFAULT_PAYRUN_TYPE, formData.value)
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
            <ol class="mb-0 ps-3">
                <li>
                    {{ $t('payrun_works_note_1') }}
                    <router-link to="/hrm/attendance-report" class="fw-semibold">{{ $t('payrun') }}</router-link>
                    {{ $t('module') }}.
                </li>
                <li>
                    {{ $t('payrun_works_note_2') }}
                    <router-link to="/hrm/employees" class="fw-semibold">{{ $t('employees') }}</router-link>
                    {{ $t('details') }}.
                </li>
            </ol>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <app-input id="payrun_period" type="select" :label="$t('payrun_period')" label-required
                    :options="payrunPeriodOptions" list-value-field="name" :selected-disable="false"
                    v-model="formData.payrun_period" :errors="$errors(errors, 'payrun_period')" />
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label mb-2 d-block">
                {{ $t('payrun_generating_type') }} <span class="text-danger">*</span>
            </label>
            <app-input type="radio" id="consider_type" radio-checkbox-name="consider_type"
                list-value-field="name" v-model="formData.consider_type"
                :options="considerTypeOptions" :errors="$errors(errors, 'consider_type')" />
        </div>

        <div class="mb-4">
            <label class="form-label mb-2 d-block">{{ $t('consider_overtime') }}</label>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="consider_overtime"
                    v-model="formData.consider_overtime" />
            </div>
            <p class="text-muted small mb-0">{{ $t('overtime_will_calculate') }}</p>
        </div>

        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" :disabled="preloader">
            <app-button-loader v-if="preloader" />
            <CheckCircle />
            {{ $t('save') }}
        </button>
    </form>
</template>

<style scoped lang="scss"></style>
