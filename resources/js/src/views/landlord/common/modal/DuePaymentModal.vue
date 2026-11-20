<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {PURCHASES_PAYMENT, SELECTED_PAYMENT_METHODS} from "@/services/endpoints/landlord.js";
import {numberFormatter, serverDateFormat} from "@/utils/helper.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    rowData: {},
    paymentUrl: String,

})
const emit = defineEmits(['close'])

const {preloader, formData, errors, save, closeModal} = useSubmitForm(props, emit)
const submit = () => {

    const data = {
        ...formData.value,
        date: serverDateFormat(formData.value.date)
    }

    save(props.paymentUrl, data)
}


</script>

<template>
    <app-modal :modal-id="modalId"
               :title="$t('due_payment')"
               modal-size="large"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">

        <template v-slot:body>
            <div class="row g-4">
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
                               id="categories"
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
                    <app-input type="text"
                               id="due_amount"
                               :label="$t('due_amount')"
                               label-required
                               :placeholder="$t('due_amount')"
                               v-model="rowData.due_amount"
                               disabled/>
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

<style scoped lang="scss">

</style>


