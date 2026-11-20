<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import { CheckCircle } from '@iconoir/vue';
import Axios from '@/services/axios/index.js';
import { LANDLORD_DELIVERY_CHARGE_SETTINGS } from '@/services/endpoints/landlord.js';

const toast = useToast();
const pageLoader = ref(false);
const preloader = ref(false);
const errors = ref({});

const formData = ref({
    inside_dhaka_charge: null,
    outside_dhaka_charge: null,
});

const fetchSettings = () => {
    pageLoader.value = true;
    Axios.get(LANDLORD_DELIVERY_CHARGE_SETTINGS)
        .then(({ data }) => {
            formData.value.inside_dhaka_charge = data.inside_dhaka_charge ?? null;
            formData.value.outside_dhaka_charge = data.outside_dhaka_charge ?? null;
        })
        .finally(() => { pageLoader.value = false; });
};

const submit = () => {
    preloader.value = true;
    errors.value = {};
    Axios.post(LANDLORD_DELIVERY_CHARGE_SETTINGS, formData.value)
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
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('delivery_charge_settings')" />

        <div class="dc-area-link">
            These are the default charges used when an area doesn't have its own shipping charge configured.
            <router-link to="/area-shipping-charges">Manage per-area shipping charges &rarr;</router-link>
        </div>

        <app-loader v-if="pageLoader" />

        <div v-else class="dc-wrap">
            <!-- Inside Dhaka -->
            <div class="dc-row">
                <div class="dc-row__label">
                    <p class="dc-row__title">
                        Inside Dhaka City
                        <span class="dc-unit">(BDT)</span>
                        <span class="dc-required">*</span>
                    </p>
                    <p class="dc-row__sub">Flat delivery fee charged for orders shipped inside Dhaka city.</p>
                </div>
                <div class="dc-row__body">
                    <app-input id="inside_dhaka_charge" type="number" v-model="formData.inside_dhaka_charge"
                        :errors="$errors(errors, 'inside_dhaka_charge')" />
                </div>
            </div>

            <div class="dc-divider"></div>

            <!-- Outside Dhaka -->
            <div class="dc-row">
                <div class="dc-row__label">
                    <p class="dc-row__title">
                        Outside Dhaka City
                        <span class="dc-unit">(BDT)</span>
                        <span class="dc-required">*</span>
                    </p>
                    <p class="dc-row__sub">Flat delivery fee charged for orders shipped outside Dhaka city.</p>
                </div>
                <div class="dc-row__body">
                    <app-input id="outside_dhaka_charge" type="number" v-model="formData.outside_dhaka_charge"
                        :errors="$errors(errors, 'outside_dhaka_charge')" />
                </div>
            </div>

            <!-- Save -->
            <div class="dc-footer">
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2" :disabled="preloader"
                    @click="submit">
                    <app-button-loader v-if="preloader" />
                    <CheckCircle v-else width="16" height="16" />
                    Save
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.dc-area-link {
    font-size: 0.8rem;
    color: var(--secondary-font-color, #6b7280);
    background: var(--dashboard-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;

    a {
        margin-left: 4px;
        font-weight: 600;
    }
}

.dc-wrap {
    background: var(--white, #fff);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
}

.dc-row {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 32px;
    padding: 28px 32px;
    align-items: start;
}

.dc-row__label {
    padding-top: 2px;
}

.dc-row__title {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 5px;
    line-height: 1.4;
}

.dc-unit {
    font-weight: 400;
    color: var(--secondary-font-color, #6b7280);
    margin-left: 3px;
}

.dc-required {
    color: #ef4444;
    margin-left: 2px;
}

.dc-row__sub {
    font-size: 0.775rem;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
    line-height: 1.55;
}

.dc-row__body {
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-width: 260px;
}

.dc-divider {
    height: 1px;
    background: var(--border-color);
    margin: 0 32px;
}

.dc-footer {
    padding: 20px 32px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    background: var(--dashboard-bg);
    border-radius: 0 0 12px 12px;
}
</style>
