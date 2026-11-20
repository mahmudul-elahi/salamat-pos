<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { useI18n } from "vue-i18n";

const props = defineProps({
    modalId: String,
    tableId: String,
    rowData: {},
    paymentUrl: String,

})
const { t } = useI18n();
const emit = defineEmits(['close'])
const { preloader, closeModal } = useSubmitForm(props, emit)

</script>

<template>
    <app-modal :modal-id="modalId"
        :title="$t('product_details')"
        modal-size="large"
        :preloader="preloader"
        @close="closeModal">

        <template v-slot:body>
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="title">{{ t('product_name') }}:</h6>
                    <p class="mb-1">{{ props.rowData.product_name }} 
                    </p>
                    <div class="badge bg-success badge-square-light">{{ props.rowData.attribute_value }}</div>
                </div>
                <div class="col-md-6">
                    <h6 class="title">{{ t('sku') }}:</h6>
                    <p>{{ props.rowData?.sku }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="title">{{ t('serial_number') }}:</h6>
                    <p>{{ props.rowData?.serial_number }}</p>
                </div>
                <div class="col-md-6">
                    <h6>{{ t('purchase_price') }}:</h6>
                    <p>{{ props.rowData?.purchase_price }}</p>
                </div>
            </div>


        </template>

    </app-modal>
</template>

<style scoped lang="scss"></style>
