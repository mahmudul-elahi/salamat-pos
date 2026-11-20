<script setup>
import {ref} from 'vue';
import {useI18n} from 'vue-i18n';
import {
    ADVANCE_CREDIT_STORE,
    SELECTED_CONTACTS,
    SELECTED_PAYMENT_METHODS,
    SELECTED_SHOPS
} from "@/services/endpoints/landlord.js";
import {numberWithCurrencySymbol, serverDateFormat} from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import usePermission from "@/composable/permission/usePermission.js";

const props = defineProps({
    modalId: String,
    rowData: Object,
    tableId: String
})

const {t} = useI18n();
const emit = defineEmits(['close'])
const emitter = useEmitter();
const { isSupperAdmin, canFilterByShop } = usePermission();

const formData = ref({
    tenant_id: '',
    amount: '',
    note: '',
    date: new Date(),
    payment_type: '',  // ← add this
});

const paymentTypeOptions = [
    { id: 'receivable', name: t('receivable') },
    { id: 'payable',    name: t('payable') },
];


const preloader = ref(false);
const errors = ref({});
const toast = useToast();


const submit = async () => {
    preloader.value = true;
    errors.value = {};

    try {
        const payload = {
            ...formData.value,
            tenant_id: formData.value.tenant_id || null,
            contact_id: props.rowData.id,
            amount: parseFloat(formData.value.amount),
            date: serverDateFormat(formData.value.date),
        };

        const response = await Axios.post(ADVANCE_CREDIT_STORE, payload);
        toast.success(response.data?.message);
        closeModal();
        emitter.emit("reload-" + props.tableId);

    } catch (e) {
        if (e.response && e.response.status === 422) {
            errors.value = e.response.data.errors;
        } else {
            toast.error(e.response?.data?.message || e.message);
        }
    } finally {
        preloader.value = false;
    }
}

const closeModal = () => {
    emit('close');
}
</script>

<template>
    <app-modal :modal-id="modalId"
               :title="$t('advance_payment')"
               modal-size="large"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">

        <template v-slot:body>
            <div class="row g-4">

                <div class="col-sm-6" v-if="isSupperAdmin()">
                    <app-input type="advance-search-select"
                               id="shop_id"
                               :label="$t('warehouse_or_store_name')"
                               label-required
                               list-value-field="name"
                               v-model="formData.tenant_id"
                               :loading-length="10"
                               :fetch-url="SELECTED_SHOPS"
                               :choose-label="$t('choose_a_shop')"
                               :errors="$errors(errors, 'tenant_id')"/>
                </div>


                <div class="col-sm-6">
                    <app-input type="date"
                               :label="$t('date')"
                               id="date"
                               label-required
                               v-model="formData.date"
                               :enable-time-picker="false"
                               auto-apply
                               :errors="$errors(errors, 'date')"/>
                </div>

                <div class="col-sm-6">
                    <app-input type="number"
                               :label="$t('amount')"
                               id="amount"
                               label-required
                               :placeholder="$t('amount')"
                               v-model="formData.amount"
                               :errors="$errors(errors, 'amount')"/>
                </div>

                <div class="col-sm-6">
                    <app-input type="select"
                               id="payment_type"
                               :label="$t('payment_type')"
                               label-required
                               v-model="formData.type"
                               :options="paymentTypeOptions"
                               list-value-field="name"
                               :choose-label="$t('choose_payment_type')"
                               :errors="$errors(errors, 'type')"/>
                </div>

                <div class="col-md-12">
                    <app-input v-model="formData.note"
                               id="note"
                               :placeholder="$t('note')"
                               :label="$t('note')"
                               type="textarea"/>
                </div>

            </div>
        </template>
    </app-modal>
</template>
