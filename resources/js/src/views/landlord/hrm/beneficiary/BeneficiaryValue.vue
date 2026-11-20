<script setup>
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { CheckCircle, Plus, Trash } from "@iconoir/vue";
import Axios from "@/services/axios";
import { HRM_BENEFICIARIES, HRM_DEFAULT_BENEFICIARY_VALUES } from "@/services/endpoints/hrm.js";

const { t } = useI18n();
const toast = useToast();

const pageLoader = ref(false);
const preloader = ref(false);

const allowanceRows = ref([]);
const deductionRows = ref([]);

const newRow = () => ({ beneficiary_id: null, amount: null, is_percentage: false });

const addAllowance = () => allowanceRows.value.push(newRow());
const addDeduction = () => deductionRows.value.push(newRow());

const removeAllowance = (index) => allowanceRows.value.splice(index, 1);
const removeDeduction = (index) => deductionRows.value.splice(index, 1);

const fetchValues = () => {
    pageLoader.value = true;

    Axios.get(HRM_DEFAULT_BENEFICIARY_VALUES)
        .then(({ data }) => {
            const mapRow = (value) => ({
                beneficiary_id: value.beneficiary_id,
                amount: value.amount,
                is_percentage: !!value.is_percentage,
            });

            allowanceRows.value = (data.allowances ?? []).map(mapRow);
            deductionRows.value = (data.deductions ?? []).map(mapRow);
        })
        .finally(() => {
            pageLoader.value = false;
        });
};

const submit = () => {
    preloader.value = true;

    const values = [...allowanceRows.value, ...deductionRows.value]
        .filter((row) => row.beneficiary_id && row.amount !== null && row.amount !== '');

    Axios.post(HRM_DEFAULT_BENEFICIARY_VALUES, { values })
        .then((response) => {
            toast.success(response.data.message);
            fetchValues();
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message ?? 'Something went wrong');
        })
        .finally(() => {
            preloader.value = false;
        });
};

onMounted(fetchValues);
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('badge_value')" />

        <app-loader v-if="pageLoader" />
        <form v-else @submit.prevent="submit">
            <div class="alert alert-warning rounded-default mb-4">
                <p class="fw-semibold mb-2">{{ $t('how_badge_value_works') }}</p>
                <ol class="mb-0 ps-3">
                    <li>
                        {{ $t('badge_value_note_1') }}
                        <router-link to="/hrm/beneficiaries" class="fw-semibold">{{ $t('beneficiary_badge') }}</router-link>
                        {{ $t('badge_value_note_1_suffix') }}
                    </li>
                    <li>{{ $t('badge_value_note_2') }}</li>
                    <li>
                        {{ $t('badge_value_note_3') }}
                        <router-link to="/hrm/employees" class="fw-semibold">{{ $t('employees') }}</router-link>
                        {{ $t('badge_value_note_3_suffix') }}
                    </li>
                    <li>
                        {{ $t('badge_value_note_4') }}
                        <span class="fw-semibold">{{ $t('payslips') }}</span>
                        {{ $t('badge_value_note_4_suffix') }}
                    </li>
                </ol>
            </div>

            <h5 class="mb-3">{{ $t('allowance') }}</h5>
            <div v-for="(row, index) in allowanceRows" :key="`allowance-${index}`"
                class="row g-3 align-items-center mb-3">
                <div class="col-md-6">
                    <app-input type="advance-search-select" :id="`allowance_${index}`"
                        list-value-field="name" :loading-length="10"
                        :fetch-url="`${HRM_BENEFICIARIES}?type=allowance`"
                        :choose-label="$t('choose_a_badge')"
                        v-model="row.beneficiary_id" />
                </div>
                <div class="col-md-3">
                    <app-input type="number" :id="`allowance_amount_${index}`"
                        :placeholder="$t('enter_value')" v-model="row.amount" />
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" :id="`allowance_percent_${index}`"
                            v-model="row.is_percentage" />
                        <label class="form-check-label" :for="`allowance_percent_${index}`">
                            {{ $t('in_percent') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger" @click="removeAllowance(index)">
                        <Trash />
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-primary rounded-pill d-flex align-items-center gap-2 mb-4"
                @click="addAllowance">
                <Plus /> {{ $t('add_allowance') }}
            </button>

            <h5 class="mb-3">{{ $t('deduction') }}</h5>
            <div v-for="(row, index) in deductionRows" :key="`deduction-${index}`"
                class="row g-3 align-items-center mb-3">
                <div class="col-md-6">
                    <app-input type="advance-search-select" :id="`deduction_${index}`"
                        list-value-field="name" :loading-length="10"
                        :fetch-url="`${HRM_BENEFICIARIES}?type=deduction`"
                        :choose-label="$t('choose_a_badge')"
                        v-model="row.beneficiary_id" />
                </div>
                <div class="col-md-3">
                    <app-input type="number" :id="`deduction_amount_${index}`"
                        :placeholder="$t('enter_value')" v-model="row.amount" />
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" :id="`deduction_percent_${index}`"
                            v-model="row.is_percentage" />
                        <label class="form-check-label" :for="`deduction_percent_${index}`">
                            {{ $t('in_percent') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger" @click="removeDeduction(index)">
                        <Trash />
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-primary rounded-pill d-flex align-items-center gap-2 mb-4"
                @click="addDeduction">
                <Plus /> {{ $t('add_deduction') }}
            </button>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" :disabled="preloader">
                    <app-button-loader v-if="preloader" />
                    <CheckCircle />
                    {{ $t('save') }}
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped lang="scss"></style>
