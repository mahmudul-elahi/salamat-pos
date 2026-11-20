<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { useI18n } from "vue-i18n";
import { formatDateToLocal } from "@/utils/helper.js";
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
        :title="$t('product_verify_details')"
        modal-size="large"
        :preloader="preloader"
        @close="closeModal">

        <template v-slot:body>
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="title">{{ t('product_name') }}:</h6>
                    <p class="mb-1">{{ props.rowData.product.product_name }}
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="title">{{ t('shop_name') }}:</h6>
                    <p class="mb-1">{{ props.rowData.shope_name }}
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="title">{{ t('qr_code_number') }}:</h6>
                    <p class="mb-1">{{ props.rowData.barcode_number }}
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="title">{{ t('verified_date') }}:</h6>
                    <p>{{ formatDateToLocal(props.rowData?.created_at) }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="title">{{ t('user_name') }}:</h6>
                    <p>{{ props.rowData?.name }}</p>
                </div>
                <div class="col-md-6">
                    <h6>{{ t('phone') }}:</h6>
                    <p>{{ props.rowData?.phone }}</p>
                </div>
                <div class="col-md-12">
                    <h6 class="title">{{ t('full_address') }}:</h6>
                    <p class="mb-1">{{ props.rowData.full_address }}
                    </p>
                </div>
                <div class="col-md-12">
                    <h6>{{ t('attachments') }}:</h6>
                    <p>
                        <a
                            :href="props.rowData?.attachments"
                            target="_blank"
                            rel="noopener noreferrer"
                            download
                            class="text-primary">
                            {{ t('download_attachment') }}
                        </a>
                    </p>

                </div>
            </div>


        </template>

    </app-modal>
</template>

<style scoped lang="scss"></style>
