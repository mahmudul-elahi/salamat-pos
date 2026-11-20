<script setup>
import { ref, nextTick } from "vue";
import { CARES } from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import router from "@/router/index.js";

const toast = useToast();
const formData = ref({ name: '', value: '' });
const errors = ref({});
const preloader = ref(false);

const clampValue = async () => {
    const val = parseFloat(formData.value.value);
    if (!isNaN(val)) {
        const clamped = Math.min(100, Math.max(1, val));
        if (clamped !== val) {
            formData.value.value = '';
            await nextTick();
            formData.value.value = clamped;
        }
    }
};

const validate = () => {
    const errs = {};
    if (!formData.value.name || !formData.value.name.trim()) {
        errs.name = ['Name is required.'];
    }
    const val = parseFloat(formData.value.value);
    if (formData.value.value === '' || formData.value.value === null || formData.value.value === undefined) {
        errs.value = ['Value is required.'];
    } else if (isNaN(val)) {
        errs.value = ['Value must be a number.'];
    } else if (val < 1) {
        errs.value = ['Value must be at least 1%.'];
    } else if (val > 100) {
        errs.value = ['Value may not be greater than 100%.'];
    }
    return errs;
};

const submit = async () => {
    errors.value = validate();
    if (Object.keys(errors.value).length) return;

    preloader.value = true;
    try {
        const response = await Axios.post(CARES, formData.value);
        toast.success(response.data?.message);
        await router.push({ name: 'cares' });
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || 'Care creation failed');
        }
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Add Care" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-xl-10">
                        <app-input type="textarea" id="name" :label="$t('name')" label-required :placeholder="$t('name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>
                    <div class="col-xl-2">
                        <app-input type="number" id="value" label="Value (%)" label-required placeholder="1 - 100"
                            v-model="formData.value" @input="clampValue" :errors="$errors(errors, 'value')" />
                    </div>
                    <div class="col-xl-12">
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary" @click="submit" :disabled="preloader">
                                <span v-if="preloader" class="spinner-border spinner-border-sm me-2" role="status"
                                    aria-hidden="true" />
                                {{ $t('submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
