<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import { CheckCircle } from '@iconoir/vue';
import Axios from '@/services/axios/index.js';
import { LANDLORD_ATTENDANCE_SETTINGS } from '@/services/endpoints/landlord.js';

const toast      = useToast();
const pageLoader = ref(false);
const preloader  = ref(false);
const errors     = ref({});

const formData = ref({
    punch_in_tolerance:           null,
    work_availability_percentage: null,
});

const fetchSettings = () => {
    pageLoader.value = true;
    Axios.get(LANDLORD_ATTENDANCE_SETTINGS)
        .then(({ data }) => {
            formData.value.punch_in_tolerance           = data.punch_in_tolerance ?? null;
            formData.value.work_availability_percentage = data.work_availability_percentage ?? null;
        })
        .finally(() => { pageLoader.value = false; });
};

const submit = () => {
    preloader.value = true;
    errors.value = {};
    Axios.post(LANDLORD_ATTENDANCE_SETTINGS, formData.value)
        .then(({ data }) => toast.success(data.message))
        .catch(({ response }) => {
            if (response?.status === 422) errors.value = response.data.errors ?? {};
            else toast.error(response?.data?.message ?? 'Something went wrong');
        })
        .finally(() => { preloader.value = false; });
};

onMounted(fetchSettings);
</script>

<template>
    <div class="ad-wrap">
        <app-loader v-if="pageLoader" />

        <template v-else>
            <!-- Punch in tolerance -->
            <div class="ad-row">
                <div class="ad-row__label">
                    <p class="ad-row__title">
                        Punch in time tolerance
                        <span class="ad-unit">(Minutes)</span>
                        <span class="ad-required">*</span>
                    </p>
                    <p class="ad-row__sub">The adjustment considers the punch in time based on a work shift.</p>
                </div>
                <div class="ad-row__body">
                    <app-input id="punch_in_tolerance" type="number"
                        v-model="formData.punch_in_tolerance"
                        :errors="$errors(errors, 'punch_in_tolerance')" />
                    <div class="ad-badges">
                        <div class="ad-badge-item">
                            <span class="ad-badge ad-badge--early">Early</span>
                            <span class="ad-badge-hint">Before on time</span>
                        </div>
                        <div class="ad-badge-item">
                            <span class="ad-badge ad-badge--regular">Regular</span>
                            <span class="ad-badge-hint">On time to tolerance Late</span>
                        </div>
                        <div class="ad-badge-item">
                            <span class="ad-badge ad-badge--late">Late</span>
                            <span class="ad-badge-hint">After tolerance</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ad-divider"></div>

            <!-- Work availability -->
            <div class="ad-row">
                <div class="ad-row__label">
                    <p class="ad-row__title">
                        Work availability definition
                        <span class="ad-unit">(Percentage)</span>
                        <span class="ad-required">*</span>
                    </p>
                    <p class="ad-row__sub">The attendance percentage that defines an employee Good or Bad.</p>
                </div>
                <div class="ad-row__body">
                    <app-input id="work_availability_percentage" type="number"
                        v-model="formData.work_availability_percentage"
                        :errors="$errors(errors, 'work_availability_percentage')" />
                    <div class="ad-badges">
                        <div class="ad-badge-item">
                            <span class="ad-badge ad-badge--good">Good</span>
                            <span class="ad-badge-hint">Equal or above of the percent</span>
                        </div>
                        <div class="ad-badge-item">
                            <span class="ad-badge ad-badge--bad">Bad</span>
                            <span class="ad-badge-hint">Below the percent</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save -->
            <div class="ad-footer">
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2"
                    :disabled="preloader" @click="submit">
                    <app-button-loader v-if="preloader" />
                    <CheckCircle v-else width="16" height="16" />
                    Save
                </button>
            </div>
        </template>
    </div>
</template>

<style scoped lang="scss">
.ad-wrap { display: flex; flex-direction: column; }

.ad-row {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 32px;
    padding: 28px 32px;
    align-items: start;
}

.ad-row__label { padding-top: 2px; }

.ad-row__title {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 5px;
    line-height: 1.4;
}

.ad-unit    { font-weight: 400; color: var(--secondary-font-color, #6b7280); margin-left: 3px; }
.ad-required { color: #ef4444; margin-left: 2px; }

.ad-row__sub {
    font-size: 0.775rem;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
    line-height: 1.55;
}

.ad-row__body { display: flex; flex-direction: column; gap: 14px; }

.ad-badges {
    display: flex;
    gap: 28px;
    align-items: flex-start;
}

.ad-badge-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.ad-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 16px;
    border-radius: 100px;
    border: 1.5px solid;
    white-space: nowrap;

    &--early   { color: #d97706; border-color: #d97706; background: #fffbeb; }
    &--regular { color: #16a34a; border-color: #16a34a; background: #f0fdf4; }
    &--late    { color: #dc2626; border-color: #dc2626; background: #fef2f2; }
    &--good    { color: #16a34a; border-color: #16a34a; background: #f0fdf4; }
    &--bad     { color: #dc2626; border-color: #dc2626; background: #fef2f2; }
}

.ad-badge-hint {
    font-size: 0.7rem;
    color: var(--secondary-font-color, #9ca3af);
    text-align: center;
    white-space: nowrap;
}

.ad-divider {
    height: 1px;
    background: var(--border-color);
    margin: 0 32px;
}

.ad-footer {
    padding: 20px 32px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    background: var(--dashboard-bg);
}
</style>
