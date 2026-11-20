<script setup>
import {ref} from 'vue';
import {useI18n} from 'vue-i18n';
import {
    SELECTED_PAYMENT_METHODS,
    CUSTOMER_PAYMENT,
    SUPPLIER_PAYMENT_SEND,
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
    contact_id: '',
    amount: '',
    payment_method_id: '',
    date: new Date(),
    note: '',
    current_balance_amount : 0
});

const preloader = ref(false);
const errors = ref({});
const toast = useToast();

const submit = async () => {
    preloader.value = true;
    errors.value = {};

    try {
        const payload = {
            ...formData.value,
            contact_id: props.rowData.id,
            date: serverDateFormat(formData.value.date),
            current_balance_amount : props.rowData.balance
        };

        // Validation: Cannot pay more than due
        // const dueAmount = Math.abs(parseFloat(props.rowData.balance));
        // const payAmount = parseFloat(formData.value.amount);
        //
        // if (payAmount > dueAmount) {
        //     errors.value = {
        //         amount: [
        //             t(
        //                 'payment_amount_cannot_exceed_due_amount',
        //                 'Payment cannot be greater than due amount'
        //             )
        //         ]
        //     };
        //     preloader.value = false;
        //     return;
        // }
        await Axios.post(`${SUPPLIER_PAYMENT_SEND}`, payload).then((response) => {
            toast.success(response.data?.message);
            closeModal();
            emitter.emit("reload-" + props.tableId);
        });

    } catch (e) {
        if (e.response && e.response.status === 422) {
            errors.value = e.response.data.errors;
        } else {
            toast.error(e.response?.message);
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
               :title="$t('supplier_payment')"
               modal-size="large"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">

        <template v-slot:body>
            <div class="row g-4">
                <!-- Customer Info Display -->
                <div class="col-12" v-if="rowData">
                    <div class="alert alert-info">
                        <strong>{{ $t('supplier') }}:</strong> {{ rowData.name }}<br>
                        <strong>{{ $t('current_due') }}:</strong>
                        <span class="m-2">{{ numberWithCurrencySymbol(rowData.balance) }}</span>
                    </div>
                </div>

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
                               :placeholder="$t('date')"
                               v-model="formData.date"
                               :enable-time-picker="false"
                               auto-apply
                               :errors="$errors(errors, 'date')"/>
                </div>

                <div class="col-sm-6">
                    <app-input type="advance-search-select"
                               id="payment_method"
                               :label="$t('payment_method')"
                               label-required
                               listValueField="name"
                               list-key-field="id"
                               v-model="formData.payment_method_id"
                               :loading-length="10"
                               :fetch-url="`${SELECTED_PAYMENT_METHODS}`"
                               :choose-label="$t('choose_a_payment_method')"
                               :errors="$errors(errors, 'payment_method_id')"
                    />
                </div>

                <div class="col-sm-6">
                    <app-input type="number"
                               :label="$t('paying_amount')"
                               id="paying_amount"
                               label-required
                               :placeholder="$t('paying_amount')"
                               v-model="formData.amount"
                               :errors="$errors(errors, 'amount')"/>
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
