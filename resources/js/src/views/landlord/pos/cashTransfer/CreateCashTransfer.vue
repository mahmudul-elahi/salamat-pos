<script setup>
import { ref } from 'vue';
import { CASH_TRANSFERS, SELECTED_WAREHOUSES } from '@/services/endpoints/landlord.js';
import Axios from '@/services/axios/index.js';
import { useToast } from 'vue-toastification';
import router from '@/router/index.js';
import { serverDateFormat } from '@/utils/helper.js';

const toast = useToast();
const preloader = ref(false);
const errors = ref({});

const formData = ref({
    from_tenant_id: null,
    to_tenant_id: null,
    amount: '',
    transfer_date: new Date(),
    note: '',
});

const submit = async () => {
    if (!formData.value.from_tenant_id || !formData.value.to_tenant_id) {
        toast.warning('Please select both From and To shops.');
        return;
    }
    if (!formData.value.amount || parseFloat(formData.value.amount) <= 0) {
        toast.warning('Please enter a valid amount.');
        return;
    }

    errors.value = {};
    preloader.value = true;
    try {
        await Axios.post(CASH_TRANSFERS, {
            ...formData.value,
            transfer_date: serverDateFormat(formData.value.transfer_date),
        });
        toast.success('Cash transfer created successfully.');
        router.push({ name: 'cashTransfers' });
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors;
        } else {
            toast.error(e.response?.data?.message ?? 'Failed to create transfer.');
        }
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Create Cash Transfer" />

        <div class="card">
            <div class="card-body">

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <app-input type="advance-search-select" id="from_tenant" label="From Shop *"
                            list-value-field="name" v-model="formData.from_tenant_id" :loading-length="10"
                            :fetch-url="SELECTED_WAREHOUSES" choose-label="Select source shop"
                            :errors="$errors(errors, 'from_tenant_id')" />
                    </div>
                    <div class="col-md-3">
                        <app-input type="advance-search-select" id="to_tenant" label="To Shop *" list-value-field="name"
                            v-model="formData.to_tenant_id" :loading-length="10" :fetch-url="SELECTED_WAREHOUSES"
                            choose-label="Select destination shop" :errors="$errors(errors, 'to_tenant_id')" />
                    </div>
                    <div class="col-md-3">
                        <app-input type="number" id="amount" label="Amount *" v-model="formData.amount"
                            placeholder="0.00" :errors="$errors(errors, 'amount')" />
                    </div>
                    <div class="col-md-3">
                        <app-input type="date" id="transfer_date" label="Transfer Date *"
                            v-model="formData.transfer_date" :errors="$errors(errors, 'transfer_date')"
                            :enable-time-picker="false" auto-apply />
                    </div>
                    <div class="col-md-6">
                        <app-input type="textarea" id="note" label="Note" v-model="formData.note"
                            :errors="$errors(errors, 'note')" />
                    </div>
                </div>

                <!-- Summary preview -->
                <div v-if="formData.from_tenant_id && formData.to_tenant_id && formData.amount" class="ct-summary mb-4">
                    <div class="ct-summary__icon">💸</div>
                    <div class="ct-summary__text">
                        Cash transfer of
                        <strong>{{ formData.amount }}</strong>
                        will be recorded from the selected source shop to the destination shop.
                        Status will be <strong>Draft</strong> until approved.
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-light btn-sm px-4"
                        @click="router.push({ name: 'cashTransfers' })">Cancel</button>
                    <button class="btn btn-primary btn-sm px-5" @click="submit" :disabled="preloader">
                        <span v-if="preloader" class="spinner-border spinner-border-sm me-1"></span>
                        Create Transfer
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
.ct-summary {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 10px;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    font-size: 0.875rem;
    color: #0369a1;
}

.ct-summary__icon {
    font-size: 1.4rem;
    flex-shrink: 0;
}

.ct-summary__text strong {
    color: #0c4a6e;
}
</style>
