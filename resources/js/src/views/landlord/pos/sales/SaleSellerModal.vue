<script setup>
import { ref } from 'vue';
import { SALES, SELECTED_SELLERS } from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    saleId: { type: [Number, String], required: true },
    tenantId: { type: [Number, String], default: null },
    currentSellerId: { type: [Number, String], default: null },
});

const emit = defineEmits(['close']);

const toast = useToast();
const emitter = useEmitter();
const sellerId = ref(props.currentSellerId);
const errors = ref({});
const preloader = ref(false);


const submit = () => {
    preloader.value = true;
    errors.value = {};

    Axios.patch(`${SALES}/${props.saleId}/update-seller`, { seller_id: sellerId.value })
        .then(({ data }) => {
            toast.success(data.message);
            emitter.emit('reload-' + props.tableId);
            emit('close');
        })
        .catch(({ response }) => {
            if (response?.status === 422) {
                errors.value = response.data.errors || {};
            }
            toast.error(response?.data?.message || 'Failed to update seller');
        })
        .finally(() => {
            preloader.value = false;
        });
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="$t('seller')" :save-button-label="$t('save')"
        :preloader="preloader" @submit="submit" @close="$emit('close')">
        <template #body>
            <app-input type="advance-search-select" id="sale_seller_id" :label="$t('seller')" list-key-field="id"
                list-value-field="name" v-model="sellerId" :loading-length="10" :fetch-url="`${SELECTED_SELLERS}?active_only=1`"
                :choose-label="$t('choose_a_seller')" :errors="$errors(errors, 'seller_id')" />
        </template>
    </app-modal>
</template>
