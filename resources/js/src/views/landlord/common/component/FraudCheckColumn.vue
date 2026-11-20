<script setup>
import { ref, computed } from 'vue';
import { useToast } from "vue-toastification";
import usePermission from "@/composable/permission/usePermission.js";
import FraudCheckModal from './FraudCheckModal.vue';
import BdCourierApiModal from './BdCourierApiModal.vue';

const props = defineProps({
    tableId: {},
    rowData: {
        type: Object,
        required: true,
    },
});

const { canAccess } = usePermission();
const toast = useToast();
const isModalActive = ref(false);
const isBdCourierModalActive = ref(false);

const phone = computed(() => props.rowData.customer?.phone || props.rowData.contact?.phone || null);
const customerName = computed(() => props.rowData.customer?.name || props.rowData.contact?.name || '');

const isPending = computed(() => {
    const status = props.rowData.status?.name ?? props.rowData.status;
    return (status ?? '').toString().toLowerCase() === 'pending';
});

const canCheckFraud = computed(() => canAccess('view_bdcourier_check_fraud'));
const canCheckBdCourierApi = computed(() => canAccess('view_bdcourier_check_fraud_api'));
const showActions = computed(() => isPending.value && (canCheckFraud.value || canCheckBdCourierApi.value));

const openModal = () => {
    if (!phone.value) {
        toast.error('No phone number found for this customer');
        return;
    }
    isModalActive.value = true;
};

const openBdCourierModal = () => {
    if (!phone.value) {
        toast.error('No phone number found for this customer');
        return;
    }
    isBdCourierModalActive.value = true;
};
</script>

<template>
    <span>
        <div v-if="showActions" class="d-flex gap-2">
            <button v-if="canCheckFraud" type="button" class="btn btn-sm btn-outline-danger" @click="openModal">
                Check Fraud
            </button>
            <button v-if="canCheckBdCourierApi" type="button" class="btn btn-sm btn-outline-primary" @click="openBdCourierModal">
                BD Courier
            </button>
        </div>
        <span v-else class="text-muted">-</span>

        <fraud-check-modal v-if="isModalActive" :modal-id="`fraud-check-modal-${rowData.id}`" :phone="phone"
            :customer-name="customerName" @close="isModalActive = false" />

        <bd-courier-api-modal v-if="isBdCourierModalActive" :modal-id="`bd-courier-api-modal-${rowData.id}`"
            :phone="phone" :customer-name="customerName" @close="isBdCourierModalActive = false" />
    </span>
</template>
