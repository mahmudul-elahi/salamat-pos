<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES } from '@/services/endpoints/hrm.js';
import { useToast } from 'vue-toastification';
import { Download } from '@iconoir/vue';

const route = useRoute();
const toast = useToast();
const employeeId = route.params.id;

const formData = ref({
    name: '',
    email: '',
    phone_number: '',
    gender: 'male',
    date_of_birth: null,
    place_of_birth: '',
    married_status: '',
    religion: '',
    blood_type: '',
    nid_no: '',
    tin_no: '',
});

const loading = ref(true);
const saving = ref(false);
const errors = ref({});

const genderOptions = [
    { id: 'male', name: 'Male' },
    { id: 'female', name: 'Female' },
    { id: 'others', name: 'Others' },
];

const maritalStatusOptions = [
    { id: 'single', name: 'Single' },
    { id: 'married', name: 'Married' },
    { id: 'divorced', name: 'Divorced' },
    { id: 'widowed', name: 'Widowed' },
];

const religionOptions = [
    { id: 'islam', name: 'Islam' },
    { id: 'christianity', name: 'Christianity' },
    { id: 'hinduism', name: 'Hinduism' },
    { id: 'buddhism', name: 'Buddhism' },
    { id: 'judaism', name: 'Judaism' },
    { id: 'others', name: 'Others' },
];

const bloodTypeOptions = [
    { id: 'a+', name: 'A+' },
    { id: 'a-', name: 'A-' },
    { id: 'b+', name: 'B+' },
    { id: 'b-', name: 'B-' },
    { id: 'o+', name: 'O+' },
    { id: 'o-', name: 'O-' },
    { id: 'ab+', name: 'AB+' },
    { id: 'ab-', name: 'AB-' },
];

const fetchProfile = async () => {
    try {
        const { data } = await Axios.get(`${HRM_EMPLOYEES}/${employeeId}/profile`);
        formData.value = {
            name: data.name ?? '',
            email: data.email ?? '',
            phone_number: data.phone_number ?? '',
            gender: data.gender ?? 'male',
            date_of_birth: data.date_of_birth ?? null,
            place_of_birth: data.place_of_birth ?? '',
            married_status: data.married_status ?? '',
            religion: data.religion ?? '',
            blood_type: data.blood_type ?? '',
            nid_no: data.nid_no ?? '',
            tin_no: data.tin_no ?? '',
        };
    } catch {
        toast.error('Failed to load profile.');
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    try {
        errors.value = {};
        saving.value = true;
        const { data } = await Axios.patch(`${HRM_EMPLOYEES}/${employeeId}/profile`, formData.value);
        toast.success(data.message);
    } catch ({ response }) {
        if (response?.status === 422) {
            errors.value = response.data.errors;
        } else {
            toast.error(response?.data?.message ?? 'Something went wrong.');
        }
    } finally {
        saving.value = false;
    }
};

onMounted(fetchProfile);
</script>

<template>
    <div>
        <div class="detail-section-header">
            <div class="detail-section-title-wrap">
                <h6 class="detail-section-title">Personal Information</h6>
                <p class="detail-section-sub">Manage the employee's personal details</p>
            </div>
        </div>

        <app-loader v-if="loading" />

        <form v-else @submit.prevent="save">
            <div class="row g-4">
                <div class="col-md-6">
                    <app-input id="pi_name" type="text" label="Full Name" label-required placeholder="Enter full name"
                        v-model="formData.name" :errors="$errors(errors, 'name')" />
                </div>

                <div class="col-md-6">
                    <app-input id="pi_email" type="text" label="Email" placeholder="Enter email address"
                        v-model="formData.email" :errors="$errors(errors, 'email')" />
                </div>

                <div class="col-md-6">
                    <app-input id="pi_phone" type="text" label="Phone Number" placeholder="Enter phone number"
                        v-model="formData.phone_number" :errors="$errors(errors, 'phone_number')" />
                </div>

                <div class="col-md-6">
                    <app-input id="pi_dob" type="date" label="Date of Birth" v-model="formData.date_of_birth"
                        :enable-time-picker="false" auto-apply :errors="$errors(errors, 'date_of_birth')" />
                </div>

                <div class="col-md-3">
                    <app-input id="pi_place_of_birth" type="text" label="Place of Birth"
                        placeholder="Enter place of birth" v-model="formData.place_of_birth"
                        :errors="$errors(errors, 'place_of_birth')" />
                </div>

                <div class="col-md-4">
                    <div><label class="form-label mb-2">Gender</label></div>
                    <app-input type="radio" id="pi_gender" radio-checkbox-name="pi_gender" list-value-field="name"
                        v-model="formData.gender" :options="genderOptions" :errors="$errors(errors, 'gender')" />
                </div>

                <div class="col-md-5">
                    <div><label class="form-label mb-2">Marital Status</label></div>
                    <app-input type="radio" id="pi_married_status" radio-checkbox-name="pi_married_status"
                        list-value-field="name" v-model="formData.married_status" :options="maritalStatusOptions"
                        :errors="$errors(errors, 'married_status')" />
                </div>

                <div class="col-md-6">
                    <app-input id="pi_religion" type="select" label="Religion" list-value-field="name"
                        list-key-field="id" :options="religionOptions" v-model="formData.religion"
                        :errors="$errors(errors, 'religion')" />
                </div>

                <div class="col-md-6">
                    <app-input id="pi_blood_type" type="select" label="Blood Type" list-value-field="name"
                        list-key-field="id" :options="bloodTypeOptions" v-model="formData.blood_type"
                        :errors="$errors(errors, 'blood_type')" />
                </div>

                <div class="col-md-6">
                    <app-input id="pi_nid_no" type="text" label="NID No" placeholder="Enter NID number"
                        v-model="formData.nid_no" :errors="$errors(errors, 'nid_no')" />
                </div>

                <div class="col-md-6">
                    <app-input id="pi_tin_no" type="text" label="TIN No" placeholder="Enter TIN number"
                        v-model="formData.tin_no" :errors="$errors(errors, 'tin_no')" />
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <app-button-loader v-if="saving" />
                        <Download v-else />
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped lang="scss">
.detail-section-header {
    padding-bottom: 18px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 24px;
}

.detail-section-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}

.detail-section-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}
</style>
